<?php
require_once dirname(__DIR__) . '/config/config.php';

$imagesDir = dirname(__DIR__) . '/images/';

$stmt = $pdo->query("SELECT id FROM products");
$products = $stmt->fetchAll(PDO::FETCH_COLUMN);

$removedCount = 0;
$updatedCount = 0;

foreach ($products as $productId) {
    $imgStmt = $pdo->prepare("SELECT id, image_url FROM product_images WHERE product_id = ?");
    $imgStmt->execute([$productId]);
    $images = $imgStmt->fetchAll();

    if (empty($images)) continue;

    $seenHashes = [];
    $imagesToKeep = [];

    foreach ($images as $img) {
        $filePath = $imagesDir . $img['image_url'];
        if (file_exists($filePath)) {
            $hash = md5_file($filePath);
            if (in_array($hash, $seenHashes)) {
                // It's a duplicate! Delete from DB
                $pdo->prepare("DELETE FROM product_images WHERE id = ?")->execute([$img['id']]);
                $removedCount++;
            } else {
                $seenHashes[] = $hash;
                $imagesToKeep[] = $img;
            }
        } else {
            // File doesn't exist, remove from db
            $pdo->prepare("DELETE FROM product_images WHERE id = ?")->execute([$img['id']]);
        }
    }

    if (empty($imagesToKeep)) continue;

    // Now sort $imagesToKeep to find the best front image
    // Keywords for front image: 'front', 'main', '1\.', 'plain'
    usort($imagesToKeep, function($a, $b) {
        $aScore = 0;
        $bScore = 0;
        
        $aUrl = strtolower($a['image_url']);
        $bUrl = strtolower($b['image_url']);

        if (preg_match('/(front|main|plain|-1\.)/i', $aUrl)) $aScore = 10;
        elseif (preg_match('/-2\./i', $aUrl)) $aScore = -5;
        elseif (preg_match('/(desc|info|ingredient|science|use|target)/i', $aUrl)) $aScore = -10;

        if (preg_match('/(front|main|plain|-1\.)/i', $bUrl)) $bScore = 10;
        elseif (preg_match('/-2\./i', $bUrl)) $bScore = -5;
        elseif (preg_match('/(desc|info|ingredient|science|use|target)/i', $bUrl)) $bScore = -10;

        return $bScore <=> $aScore; // Descending order
    });

    // Update sort_order and main product image
    $sort = 0;
    $mainImage = null;
    foreach ($imagesToKeep as $img) {
        if ($sort === 0) {
            $mainImage = $img['image_url'];
        }
        $pdo->prepare("UPDATE product_images SET sort_order = ? WHERE id = ?")->execute([$sort, $img['id']]);
        $sort++;
    }

    if ($mainImage) {
        $pdo->prepare("UPDATE products SET image = ? WHERE id = ?")->execute([$mainImage, $productId]);
        $updatedCount++;
    }
}

echo "Removed $removedCount duplicate images. Updated main images for $updatedCount products.\n";

