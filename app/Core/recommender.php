<?php
/**
 * Product Recommendation Engine - Urban Glow Salon
 */

function getRecommendedProducts($pdo, $userId, $limit = 4) {
    // 1. Check user's past purchased item tags
    $historyStmt = $pdo->prepare("
        SELECT p.tags, p.id
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        JOIN products p ON oi.product_id = p.id
        WHERE o.user_id = ? AND p.is_active = 1
        ORDER BY o.created_at DESC
        LIMIT 10
    ");
    $historyStmt->execute([$userId]);
    $history = $historyStmt->fetchAll();

    $excludeIds = [];
    $tagCounts = [];

    if (count($history) > 0) {
        // User has purchase history
        foreach ($history as $item) {
            $excludeIds[] = $item['id'];

            // Tally tags
            if (!empty($item['tags'])) {
                $tags = array_map('trim', explode(',', $item['tags']));
                foreach ($tags as $tag) {
                    if (!isset($tagCounts[$tag])) $tagCounts[$tag] = 0;
                    $tagCounts[$tag] += 1;
                }
            }
        }
    }

    // 2. Prepare fallback logic if still no history
    if (empty($tagCounts)) {
        // Just return the newest/best products if no profile data exists
        $stmt = $pdo->prepare("SELECT * FROM products WHERE is_active = 1 ORDER BY created_at DESC LIMIT ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // 3. Score all active products
    $allProductsStmt = $pdo->query("SELECT * FROM products WHERE is_active = 1");
    $allProducts = $allProductsStmt->fetchAll();
    
    $scoredProducts = [];

    foreach ($allProducts as $p) {
        if (in_array($p['id'], $excludeIds)) continue; // Skip already bought items

        $score = 0;

        // Add score for matching tags
        if (!empty($p['tags'])) {
            $pTags = array_map('trim', explode(',', $p['tags']));
            foreach ($pTags as $tag) {
                if (isset($tagCounts[$tag])) {
                    $score += $tagCounts[$tag];
                }
            }
        }

        if ($score > 0) {
            $p['relevancy_score'] = $score;
            $scoredProducts[] = $p;
        }
    }

    // Sort by score descending
    usort($scoredProducts, function($a, $b) {
        return $b['relevancy_score'] <=> $a['relevancy_score'];
    });

    // Take top N
    $recommendations = array_slice($scoredProducts, 0, $limit);

    // If we didn't get enough recommendations, backfill with random active products
    if (count($recommendations) < $limit) {
        $needed = $limit - count($recommendations);
        $recIds = array_column($recommendations, 'id');
        $excludeIds = array_merge($excludeIds, $recIds);
        
        $placeholders = str_repeat('?,', count($excludeIds) - 1) . '?';
        $sql = "SELECT * FROM products WHERE is_active = 1";
        if (!empty($excludeIds)) {
            $sql .= " AND id NOT IN ($placeholders)";
        }
        $sql .= " ORDER BY RAND() LIMIT ?";
        
        $stmt = $pdo->prepare($sql);
        $params = $excludeIds;
        $params[] = $needed;
        
        // Need to bind individually due to LIMIT with ?
        $idx = 1;
        foreach ($excludeIds as $id) {
            $stmt->bindValue($idx++, $id, PDO::PARAM_INT);
        }
        $stmt->bindValue($idx, $needed, PDO::PARAM_INT);
        
        $stmt->execute();
        $backfill = $stmt->fetchAll();
        
        $recommendations = array_merge($recommendations, $backfill);
    }

    return $recommendations;
}

/**
 * Cross-Sell Recommendation Engine
 * Phase 1: Jaccard Similarity on Tags
 * Phase 2: Apriori Confidence on Transaction History
 */
function getCrossSellRecommendations($pdo, $serviceId, $topN = 3) {
    // 1. Get Service Tags
    $stmt = $pdo->prepare("SELECT tags FROM services WHERE id = ?");
    $stmt->execute([$serviceId]);
    $serviceRow = $stmt->fetch();
    
    $serviceTags = [];
    if ($serviceRow && !empty($serviceRow['tags'])) {
        $serviceTags = array_map('trim', array_map('strtolower', explode(',', $serviceRow['tags'])));
    }

    // 2. Apriori Confidence (Phase 2)
    // Get total bookings for this service
    $stmt = $pdo->prepare("SELECT COUNT(id) FROM bookings WHERE service_id = ? AND status != 'Cancelled'");
    $stmt->execute([$serviceId]);
    $totalBookings = (int)$stmt->fetchColumn();

    // Get co-occurrences: How many times a user who booked this service bought each product
    $coOccurrences = [];
    if ($totalBookings > 0) {
        $stmt = $pdo->prepare("
            SELECT oi.product_id, COUNT(DISTINCT o.id) as co_occurrences
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            JOIN bookings b ON o.user_id = b.user_id 
            WHERE b.service_id = ? 
              AND o.status != 'Cancelled' 
              AND b.status != 'Cancelled'
            GROUP BY oi.product_id
        ");
        $stmt->execute([$serviceId]);
        $coOccurrences = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    // 3. Process all active products
    $stmt = $pdo->query("SELECT id, name, price, discount_price, image, tags FROM products WHERE is_active = 1");
    $products = $stmt->fetchAll();

    $scoredProducts = [];

    foreach ($products as $p) {
        // Phase 1: Jaccard Similarity
        $jaccardScore = 0.0;
        if (!empty($serviceTags) && !empty($p['tags'])) {
            $pTags = array_map('trim', array_map('strtolower', explode(',', $p['tags'])));
            $intersect = count(array_intersect($serviceTags, $pTags));
            $union = count(array_unique(array_merge($serviceTags, $pTags)));
            if ($union > 0) {
                $jaccardScore = $intersect / $union;
            }
        }

        // Phase 2: Apriori Confidence
        $confidenceScore = 0.0;
        if ($totalBookings > 0 && isset($coOccurrences[$p['id']])) {
            $confidenceScore = min($coOccurrences[$p['id']] / $totalBookings, 1.0);
        }

        // Final Hybrid Score
        // If no transaction history exists globally for this service, rely solely on Jaccard
        if ($totalBookings == 0) {
            $finalScore = $jaccardScore;
        } else {
            $finalScore = (0.4 * $jaccardScore) + (0.6 * $confidenceScore);
        }

        if ($finalScore > 0) {
            $p['cross_sell_score'] = $finalScore;
            
            // Generate reason
            if ($confidenceScore > $jaccardScore && $confidenceScore > 0.1) {
                $p['reason'] = "Popular choice among customers who booked this service";
            } else if ($jaccardScore > 0) {
                $p['reason'] = "Perfect accompaniment for your treatment";
            } else {
                $p['reason'] = "Recommended addition";
            }

            $scoredProducts[] = $p;
        }
    }

    // Sort by final score descending
    usort($scoredProducts, function($a, $b) {
        return $b['cross_sell_score'] <=> $a['cross_sell_score'];
    });

    return array_slice($scoredProducts, 0, $topN);
}
?>
