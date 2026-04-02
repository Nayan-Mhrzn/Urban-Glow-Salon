<?php
/**
 * Adaptive Slot Recommendation — Scoring Engine
 * Urban Glow Salon
 *
 * scoreSlots($pdo, $customerId, $serviceId, $date)
 *   → Returns ['slots' => [...scored...], 'recommended' => [...top3...], 'fully_booked' => bool]
 */

// ─── Time bucket helper ───
function getTimeBucket($timeStr) {
    $hour = (int) date('G', strtotime($timeStr));
    if ($hour >= 9 && $hour < 12) return 'morning';
    if ($hour >= 12 && $hour < 16) return 'afternoon';
    return 'evening'; // 16–20
}

// ─── Main scoring function ───
function scoreSlots(PDO $pdo, ?int $customerId, int $serviceId, string $date): array {
    // 1. Fetch service duration
    $svcStmt = $pdo->prepare("SELECT duration_mins FROM services WHERE id = ? AND is_active = 1");
    $svcStmt->execute([$serviceId]);
    $service = $svcStmt->fetch();
    if (!$service) return ['slots' => [], 'recommended' => [], 'fully_booked' => false];
    $durationMins = (int) $service['duration_mins'];

    // 2. Generate raw time slots
    $start = strtotime(WORKING_HOURS_START);
    $end   = strtotime(WORKING_HOURS_END);
    $interval = SLOT_DURATION_MINUTES * 60;
    $closingTs = $end;

    // 3. Get all booked slots for this date (across all stylists)
    $bookedStmt = $pdo->prepare(
        "SELECT booking_time, staff_id, service_id, s.duration_mins
         FROM bookings b
         JOIN services s ON b.service_id = s.id
         WHERE b.booking_date = ? AND b.status != 'Cancelled'
         ORDER BY booking_time"
    );
    $bookedStmt->execute([$date]);
    $dayBookings = $bookedStmt->fetchAll();

    $bookedTimes = array_column($dayBookings, 'booking_time');
    $dayOfWeek = (int) date('w', strtotime($date)) + 1; // 1=Sun..7=Sat (matches MySQL DAYOFWEEK)

    // 4. Build available slots, applying duration fit filter
    $rawSlots = [];
    for ($t = $start; $t < $end; $t += $interval) {
        $timeStr = date('H:i:s', $t);
        $slotEndTs = $t + ($durationMins * 60);

        // DURATION FIT: exclude if service wouldn't finish before closing
        if ($slotEndTs > $closingTs) continue;

        // Check if slot is taken
        if (in_array($timeStr, $bookedTimes)) continue;

        $rawSlots[] = [
            'time'    => $timeStr,
            'display' => date('h:i A', $t),
            'ts'      => $t,
        ];
    }

    // Fully booked?
    if (empty($rawSlots)) {
        return ['slots' => [], 'recommended' => [], 'fully_booked' => true];
    }

    // 5. Determine if new customer
    $isNewCustomer = true;
    $customerHistory = [];
    $noShowRate = 0;
    $highRisk = false;
    $stylistCounts = [];
    $interactionData = ['skipped' => [], 'selected' => []];

    if ($customerId) {
        // Fetch customer's completed booking history
        $histStmt = $pdo->prepare(
            "SELECT b.booking_time, b.day_of_week, b.staff_id, b.outcome, b.status
             FROM bookings b
             WHERE b.user_id = ? AND b.status IN ('Completed','Confirmed','Pending','Cancelled')
             ORDER BY b.booking_date DESC
             LIMIT 100"
        );
        $histStmt->execute([$customerId]);
        $customerHistory = $histStmt->fetchAll();

        if (count($customerHistory) > 0) {
            $isNewCustomer = false;
        }

        // Fetch interaction logs for this customer
        $intStmt = $pdo->prepare(
            "SELECT slot_time, action, COUNT(*) as cnt
             FROM slot_interaction_logs
             WHERE customer_id = ?
             GROUP BY slot_time, action"
        );
        $intStmt->execute([$customerId]);
        foreach ($intStmt->fetchAll() as $row) {
            $interactionData[$row['action']][$row['slot_time']] = (int) $row['cnt'];
        }
    }

    // ═══════════════════════════════════════
    // Pre-compute reusable data for scoring
    // ═══════════════════════════════════════

    // ── History preference data ──
    $dowCounts = [];
    $bucketCounts = [];
    $totalCompleted = 0;
    $totalCancelled = 0;
    $totalNoShow = 0;
    $totalBookings = 0;

    foreach ($customerHistory as $h) {
        $totalBookings++;
        if ($h['outcome'] === 'completed' || $h['status'] === 'Completed') {
            $totalCompleted++;
            $dow = (int) $h['day_of_week'];
            $bucket = getTimeBucket($h['booking_time']);
            $dowCounts[$dow] = ($dowCounts[$dow] ?? 0) + 1;
            $bucketCounts[$bucket] = ($bucketCounts[$bucket] ?? 0) + 1;
        }
        if ($h['outcome'] === 'cancelled' || $h['status'] === 'Cancelled') {
            $totalCancelled++;
        }
        if ($h['outcome'] === 'no_show') {
            $totalNoShow++;
        }

        // Stylist counts (from ALL bookings, not just completed)
        if ($h['staff_id']) {
            $stylistCounts[$h['staff_id']] = ($stylistCounts[$h['staff_id']] ?? 0) + 1;
        }
    }

    $maxDowCount = max(1, max($dowCounts ?: [1]));
    $maxBucketCount = max(1, max($bucketCounts ?: [1]));

    // ── Stylist affinity data ──
    $preferredStylistId = null;
    $maxStylistCount = 0;
    if (!empty($stylistCounts)) {
        arsort($stylistCounts);
        $preferredStylistId = array_key_first($stylistCounts);
        $maxStylistCount = $stylistCounts[$preferredStylistId];
    }

    // Build a map of stylist availability per slot from day bookings
    $stylistBookedSlots = []; // [staff_id => [time => true]]
    foreach ($dayBookings as $db) {
        if ($db['staff_id']) {
            $stylistBookedSlots[$db['staff_id']][$db['booking_time']] = true;
        }
    }

    // ── No-show risk ──
    if ($totalBookings > 0) {
        $noShowRate = ($totalCancelled + $totalNoShow) / $totalBookings;
    }
    $highRisk = $noShowRate > 0.50;

    $noShowPenalty = 0;
    if ($noShowRate > 0.70) {
        $noShowPenalty = 15;
    } elseif ($noShowRate > 0.50) {
        $noShowPenalty = 10;
    } elseif ($noShowRate > 0.30) {
        $noShowPenalty = 5;
    }

    // ── Demand data (last 90 days) ──
    $demandStmt = $pdo->prepare(
        "SELECT DAYOFWEEK(booking_date) as dow, HOUR(booking_time) as hr, COUNT(*) as cnt
         FROM bookings
         WHERE booking_date >= DATE_SUB(?, INTERVAL 90 DAY)
           AND status != 'Cancelled'
         GROUP BY DAYOFWEEK(booking_date), HOUR(booking_time)"
    );
    $demandStmt->execute([$date]);
    $demandMap = [];
    $maxDemand = 1;
    foreach ($demandStmt->fetchAll() as $d) {
        $key = $d['dow'] . '-' . $d['hr'];
        $demandMap[$key] = (int) $d['cnt'];
        if ($d['cnt'] > $maxDemand) $maxDemand = (int) $d['cnt'];
    }

    // Demand surge: this week's total vs 4-week average
    $surgeMultiplier = 1.0;
    $thisWeekStmt = $pdo->prepare(
        "SELECT COUNT(*) as cnt FROM bookings
         WHERE booking_date BETWEEN DATE_SUB(?, INTERVAL 7 DAY) AND ?
           AND status != 'Cancelled'"
    );
    $thisWeekStmt->execute([$date, $date]);
    $thisWeekCount = (int) $thisWeekStmt->fetchColumn();

    $avgStmt = $pdo->prepare(
        "SELECT COUNT(*) / 4 as avg_cnt FROM bookings
         WHERE booking_date BETWEEN DATE_SUB(?, INTERVAL 28 DAY) AND DATE_SUB(?, INTERVAL 7 DAY)
           AND status != 'Cancelled'"
    );
    $avgStmt->execute([$date, $date]);
    $fourWeekAvg = (float) $avgStmt->fetchColumn();

    if ($fourWeekAvg > 0 && $thisWeekCount > ($fourWeekAvg * 1.20)) {
        $surgeMultiplier = 1.2;
    }

    // ── Gap-fill data: stylist bookings this day ──
    // Group day bookings by staff_id with their time ranges
    $stylistDaySchedule = []; // [staff_id => [ [start_ts, end_ts], ... ]]
    foreach ($dayBookings as $db) {
        $sid = $db['staff_id'] ?: 0;
        $bStart = strtotime($db['booking_time']);
        $bEnd = $bStart + ((int) $db['duration_mins'] * 60);
        $stylistDaySchedule[$sid][] = ['start' => $bStart, 'end' => $bEnd];
    }

    // ═══════════════════════════════════
    // Score each slot
    // ═══════════════════════════════════
    $scoredSlots = [];

    foreach ($rawSlots as $slot) {
        $slotTime = $slot['time'];
        $slotTs   = $slot['ts'];
        $slotBucket = getTimeBucket($slotTime);
        $slotHour = (int) date('G', $slotTs);

        $historyScore  = 0;
        $affinityScore = 0;
        $gapScore      = 0;
        $demandRaw     = 0;

        if ($isNewCustomer) {
            // ── NEW CUSTOMER: gap-fill 50% + demand 50% ──
            $gapScore = calcGapScore($slotTs, $durationMins, $stylistDaySchedule, $start, $end);
            $demandKey = $dayOfWeek . '-' . $slotHour;
            $demandRaw = isset($demandMap[$demandKey])
                ? min(100, ($demandMap[$demandKey] / $maxDemand) * 100 * $surgeMultiplier)
                : 10;

            $finalScore = ($gapScore * 0.50) + ($demandRaw * 0.50);
            $finalScore = max(0, min(100, round($finalScore, 2)));

            $scoredSlots[] = buildSlotResult(
                $slot, $finalScore, 0, 0, $gapScore, $demandRaw, 0, false, null
            );
        } else {
            // ── RETURNING CUSTOMER: full scoring ──

            // History Preference (0–100): day match 35%, time bucket 35%, stylist 30%
            $dayMatch = isset($dowCounts[$dayOfWeek])
                ? ($dowCounts[$dayOfWeek] / $maxDowCount) * 100
                : 0;
            $bucketMatch = isset($bucketCounts[$slotBucket])
                ? ($bucketCounts[$slotBucket] / $maxBucketCount) * 100
                : 0;

            // Stylist component of history: does preferred stylist have this slot free?
            $stylistHistComponent = 0;
            if ($preferredStylistId) {
                $prefBusy = isset($stylistBookedSlots[$preferredStylistId][$slotTime]);
                $stylistHistComponent = $prefBusy ? 0 : 100;
            }

            $historyScore = ($dayMatch * 0.35) + ($bucketMatch * 0.35) + ($stylistHistComponent * 0.30);

            // Interaction adjustments on history
            if (isset($interactionData['skipped'][$slotTime]) && $interactionData['skipped'][$slotTime] >= 3) {
                $historyScore = max(0, $historyScore - 20);
            }
            if (isset($interactionData['selected'][$slotTime])) {
                $historyScore = min(100, $historyScore + 10);
            }

            // Stylist Affinity (0–100)
            if ($preferredStylistId) {
                $prefBusy = isset($stylistBookedSlots[$preferredStylistId][$slotTime]);
                if ($prefBusy) {
                    $affinityScore = 0;
                } else {
                    $affinityScore = 100; // preferred stylist is free
                }
                // Scale others proportionally if we have multiple stylists
                // (For simplicity, if preferred is free = 100, else 0)
            }

            // Gap-fill score (0–100)
            $gapScore = calcGapScore($slotTs, $durationMins, $stylistDaySchedule, $start, $end);

            // Demand score (0–100)
            $demandKey = $dayOfWeek . '-' . $slotHour;
            $demandRaw = isset($demandMap[$demandKey])
                ? min(100, ($demandMap[$demandKey] / $maxDemand) * 100 * $surgeMultiplier)
                : 10;

            // Final weighted score
            $finalScore = ($historyScore * 0.25)
                        + ($affinityScore * 0.15)
                        + ($gapScore * 0.25)
                        + ($demandRaw * 0.20)
                        - ($noShowPenalty * 0.15);

            $finalScore = max(0, min(100, round($finalScore, 2)));

            $scoredSlots[] = buildSlotResult(
                $slot, $finalScore, $historyScore, $affinityScore,
                $gapScore, $demandRaw, $noShowPenalty, $highRisk, $preferredStylistId
            );
        }
    }

    // ═══════════════════════════════════
    // Sort by final_score descending
    // ═══════════════════════════════════
    usort($scoredSlots, function ($a, $b) {
        if ($b['final_score'] === $a['final_score']) {
            // Tie-break: prefer time-of-day order, then gap-fill
            return strcmp($a['time'], $b['time']);
        }
        return $b['final_score'] <=> $a['final_score'];
    });

    // ═══════════════════════════════════
    // Pick top 3 recommended + diversity rule
    // ═══════════════════════════════════
    $recommended = array_slice($scoredSlots, 0, min(3, count($scoredSlots)));

    // Diversity: if top 3 are all same preferred_stylist, replace 3rd
    if (count($recommended) === 3) {
        $stylistIds = array_map(fn($s) => $s['preferred_stylist_id'], $recommended);
        $unique = array_unique(array_filter($stylistIds));
        if (count($unique) === 1 && $unique[array_key_first($unique)] !== null) {
            $sameStylist = $unique[array_key_first($unique)];
            // Find highest-scoring slot from a different stylist
            foreach ($scoredSlots as $candidate) {
                if ($candidate['preferred_stylist_id'] !== $sameStylist
                    && !in_array($candidate['time'], [$recommended[0]['time'], $recommended[1]['time']])) {
                    $recommended[2] = $candidate;
                    break;
                }
            }
        }
    }

    // Mark recommended slots
    $recTimes = array_column($recommended, 'time');
    foreach ($scoredSlots as &$s) {
        $s['is_recommended'] = in_array($s['time'], $recTimes);
    }
    unset($s);

    foreach ($recommended as &$r) {
        $r['is_recommended'] = true;
    }
    unset($r);

    return [
        'slots'       => $scoredSlots,
        'recommended' => $recommended,
        'fully_booked' => false,
    ];
}


// ─── Gap-fill scoring helper ───
function calcGapScore(int $slotTs, int $durationMins, array $stylistDaySchedule, int $dayStart, int $dayEnd): float {
    $slotEnd = $slotTs + ($durationMins * 60);

    // If no bookings at all that day → base score 20
    if (empty($stylistDaySchedule)) {
        return 20.0;
    }

    $bestGapScore = 0;

    // Check across all stylists' schedules
    foreach ($stylistDaySchedule as $staffId => $bookings) {
        // Sort bookings by start time
        usort($bookings, fn($a, $b) => $a['start'] <=> $b['start']);

        // Build list of gaps for this stylist
        $gaps = [];

        // Gap before first booking
        if ($bookings[0]['start'] > $dayStart) {
            $gaps[] = ['start' => $dayStart, 'end' => $bookings[0]['start']];
        }

        // Gaps between bookings
        for ($i = 0; $i < count($bookings) - 1; $i++) {
            $gapStart = $bookings[$i]['end'];
            $gapEnd   = $bookings[$i + 1]['start'];
            if ($gapEnd > $gapStart) {
                $gaps[] = ['start' => $gapStart, 'end' => $gapEnd];
            }
        }

        // Gap after last booking
        $lastEnd = end($bookings)['end'];
        if ($lastEnd < $dayEnd) {
            $gaps[] = ['start' => $lastEnd, 'end' => $dayEnd];
        }

        // Score this slot against each gap
        foreach ($gaps as $gap) {
            // Does the slot fit in this gap?
            if ($slotTs >= $gap['start'] && $slotEnd <= $gap['end']) {
                $gapDuration = ($gap['end'] - $gap['start']) / 60; // in minutes
                $serviceDuration = $durationMins;
                $remainingGap = $gapDuration - $serviceDuration;

                // Perfect adjacent fit (fills gap exactly or nearly)
                if ($remainingGap <= 5) {
                    $score = 100;
                } elseif ($remainingGap < 60) {
                    // Small remaining gap → great score
                    $score = 90 + (10 * (1 - $remainingGap / 60));
                } elseif ($remainingGap < 120) {
                    // Medium gap → decent score
                    $score = 50 + (20 * (1 - ($remainingGap - 60) / 60));
                } else {
                    // Large gap → lower score
                    $score = 10 + (20 * max(0, 1 - ($remainingGap - 120) / 180));
                }

                // Bonus: is slot adjacent to an existing booking? (within 5 min)
                foreach ($bookings as $bk) {
                    if (abs($slotTs - $bk['end']) <= 300 || abs($slotEnd - $bk['start']) <= 300) {
                        $score = min(100, $score + 10);
                        break;
                    }
                }

                $bestGapScore = max($bestGapScore, $score);
            }
        }
    }

    // If slot didn't fall into any tracked gap (e.g. unassigned bookings),
    // give a baseline based on adjacency to any booking
    if ($bestGapScore === 0) {
        $minDistance = PHP_INT_MAX;
        foreach ($stylistDaySchedule as $bookings) {
            foreach ($bookings as $bk) {
                $dist = min(abs($slotTs - $bk['end']), abs($slotEnd - $bk['start']));
                $minDistance = min($minDistance, $dist);
            }
        }
        if ($minDistance <= 1800) { // within 30 min
            $bestGapScore = 60;
        } elseif ($minDistance <= 3600) { // within 1 hour
            $bestGapScore = 40;
        } else {
            $bestGapScore = 20;
        }
    }

    return round($bestGapScore, 2);
}


// ─── Build slot result array ───
function buildSlotResult(
    array $slot,
    float $finalScore,
    float $historyScore,
    float $affinityScore,
    float $gapScore,
    float $demandScore,
    float $noShowPenalty,
    bool  $highRisk,
    ?int  $preferredStylistId
): array {
    return [
        'time'                 => $slot['time'],
        'display'              => $slot['display'],
        'available'            => true,
        'final_score'          => $finalScore,
        'history_score'        => round($historyScore, 2),
        'affinity_score'       => round($affinityScore, 2),
        'gap_fill_score'       => round($gapScore, 2),
        'demand_score'         => round($demandScore, 2),
        'no_show_penalty'      => round($noShowPenalty, 2),
        'high_risk'            => $highRisk,
        'is_recommended'       => false,
        'preferred_stylist_id' => $preferredStylistId,
    ];
}
