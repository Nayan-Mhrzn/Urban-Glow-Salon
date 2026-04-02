<?php
require_once dirname(__DIR__) . '/includes/config.php';

echo "Starting dummy data seeding...\n";

// Password for all dummy users
$passwordHash = password_hash('password123', PASSWORD_DEFAULT);

$customersData = [
    ['Aarav Sharma', 'aarav', 'aarav@example.com', '9841029384'],
    ['Anjali Chaudhary', 'anjali', 'anjali@example.com', '9867654321'],
    ['Shristi Thapa', 'shristi', 'shristi@example.com', '9840001122'],
    ['Puja Bista', 'puja', 'puja@example.com', '9851012345'],
    ['Bikash Gurung', 'bikash', 'bikash@example.com', '9811122233'],
    ['Nitesh Shrestha', 'nitesh', 'nitesh@example.com', '9849876543'],
    ['Kavita Joshi', 'kavita', 'kavita@example.com', '9851122334'],
    ['Sabina Maharjan', 'sabina', 'sabina@example.com', '9845566778'],
    ['Asmita K.C.', 'asmita', 'asmita@example.com', '9865556677'],
    ['Sagar Rai', 'sagar', 'sagar@example.com', '9843334455'],
    ['Anil Tamang', 'anil', 'anil@example.com', '9801122334'],
    ['Rina Poudel', 'rina', 'rina@example.com', '9812345678'],
    ['Nima Sherpa', 'nima', 'nima@example.com', '9867788990'],
    ['Manish KC', 'manish', 'manish@example.com', '9841234567'],
    ['Sunita Koirala', 'sunita', 'sunita@example.com', '9867654322'],
    ['Santosh Magar', 'santosh', 'santosh@example.com', '9846677889'],
    ['Alisha Basnet', 'alisha', 'alisha@example.com', '9848899001'],
    ['Pradeep Karki', 'pradeep', 'pradeep@example.com', '9851133557'],
    ['Dipesh Lama', 'dipesh', 'dipesh@example.com', '9803344556'],
    ['Roshni Shakya', 'roshni', 'roshni@example.com', '9841122334']
];

$userIds = [];
$stmtUser = $pdo->prepare("INSERT IGNORE INTO users (full_name, username, email, phone, password, role) VALUES (?, ?, ?, ?, ?, 'CUSTOMER')");
foreach ($customersData as $c) {
    if ($stmtUser->execute([$c[0], $c[1], $c[2], $c[3], $passwordHash])) {
        if ($pdo->lastInsertId()) {
            $userIds[$c[1]] = $pdo->lastInsertId();
        } else {
            // Fetch if ignored
            $u = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $u->execute([$c[1]]);
            $userIds[$c[1]] = $u->fetchColumn();
        }
    }
}
echo "Inserted " . count($userIds) . " customers.\n";

$servicesData = [
    ['Classic Haircut', 400, 30, 'Hair', 'Male', 'A standard, clean haircut tailored to your preferences.'],
    ['Premium Fade & Style', 600, 45, 'Hair', 'Male', 'Precision zero/skin fade with detailed shear work.'],
    ['Beard Trim & Line Up', 300, 20, 'Hair', 'Male', 'Careful shaping of the beard and sharp razor line-up.'],
    ['Hot Towel Shave', 500, 30, 'Skin', 'Male', 'Classic straight razor shave utilizing hot towels.'],
    ['Hair Color & Highlights', 1500, 90, 'Hair', 'Unisex', 'Premium hair coloring or custom highlights.'],
    ['Keratin Treatment', 3500, 120, 'Hair', 'Unisex', 'Smoothing treatment that eliminates frizz.'],
    ['Deep Cleansing Facial', 1200, 60, 'Skin', 'Unisex', 'Facial including steam, extraction, exfoliation.'],
    ['Anti-Aging Gold Facial', 2000, 75, 'Skin', 'Unisex', 'Premium treatment utilizing gold-infused serums.'],
    ['Bridal Hair & Makeup', 5000, 180, 'Makeup', 'Female', 'Complete bridal transformation package.'],
    ['Party Makeup', 2500, 90, 'Makeup', 'Female', 'Elegant makeup perfect for receptions events.'],
    ['Classic Manicure', 800, 45, 'Nails', 'Unisex', 'Hand soak, nail shaping, cuticle care.'],
    ['Classic Pedicure', 1000, 45, 'Nails', 'Unisex', 'Relaxing foot soak, scrub, massage.'],
    ['Full Body Massage', 2500, 60, 'Body', 'Unisex', 'Deep tissue or Swedish massage techniques.'],
    ['Head & Shoulder Massage', 800, 30, 'Body', 'Unisex', 'Focused relief for tension headaches.']
];

$serviceIds = [];
$stmtSvc = $pdo->prepare("INSERT INTO services (name, price, duration_mins, category, gender, description) VALUES (?, ?, ?, ?, ?, ?)");
foreach ($servicesData as $s) {
    // Check if exists
    $c = $pdo->prepare("SELECT id FROM services WHERE name = ?");
    $c->execute([$s[0]]);
    $id = $c->fetchColumn();
    if (!$id) {
        $stmtSvc->execute([$s[0], $s[1], $s[2], $s[3], $s[4], $s[5]]);
        $id = $pdo->lastInsertId();
    }
    $serviceIds[$s[0]] = $id;
}
echo "Inserted/Found " . count($serviceIds) . " services.\n";

$productsData = [
    ['Urban Glow Matte Clay Pomade', 'Hair Care', 850, 24, 'High hold, no-shine clay pomade for natural textured styles.'],
    ['Charcoal Detox Face Wash', 'Skin Care', 600, 45, 'Deeply cleanses pores and removes excess oil without drying the skin.'],
    ['Argan Oil Hair Serum', 'Hair Care', 1200, 18, 'Lightweight serum that tames frizz and adds a healthy shine.'],
    ['Gentleman\'s Beard Grooming Kit', 'Beard Care', 1800, 12, 'Complete kit including beard oil, styling balm, and a wooden comb.'],
    ['Hydrating Aloe Vera Gel', 'Skin Care', 450, 60, 'Pure, soothing aloe gel perfect for post-shave or sunburns.'],
    ['Sea Salt Texturizing Spray', 'Hair Care', 750, 30, 'Adds incredible volume and a messy, beachy texture to flat hair.'],
    ['Tea Tree Anti-Dandruff Shampoo', 'Hair Care', 800, 40, 'Clarifying shampoo that cools the scalp and fights flakiness.'],
    ['Rosemary Mint Conditioner', 'Hair Care', 800, 35, 'Tingling, refreshing conditioner that promotes hair strength.'],
    ['Premium Boar Bristle Brush', 'Hair Care', 950, 15, 'Distributes natural oils through hair and beard for a natural sheen.'],
    ['Sandalwood Beard Oil', 'Beard Care', 650, 50, 'Softens coarse beard hairs and eliminates beard itch with a woody scent.'],
    ['SPF 50 Daily Sunscreen', 'Skin Care', 900, 25, 'Matte-finish sun protection that leaves zero white cast.'],
    ['Vitamin C Glow Serum', 'Skin Care', 1500, 20, 'Brightens complexion, fades dark spots, and evens out skin tone.'],
    ['Keratin Repair Hair Mask', 'Hair Care', 1300, 22, 'Intensive weekly treatment for heat-damaged or color-treated hair.'],
    ['Exfoliating Walnut Scrub', 'Skin Care', 550, 38, 'Gently buffs away dead skin cells to reveal a smoother complexion.'],
    ['Cooling Aftershave Lotion', 'Beard Care', 700, 42, 'Instantly calms razor burn and reduces redness post-shave.']
];

$stmtProd = $pdo->prepare("INSERT INTO products (name, category, price, stock_quantity, description) VALUES (?, ?, ?, ?, ?)");
foreach ($productsData as $p) {
    $c = $pdo->prepare("SELECT id FROM products WHERE name = ?");
    $c->execute([$p[0]]);
    if (!$c->fetchColumn()) {
        $stmtProd->execute([$p[0], $p[1], $p[2], $p[3], $p[4]]);
    }
}
echo "Inserted products.\n";

$bookingsData = [
    // customer_username, service_name, date, time, status
    ['manish', 'Classic Haircut', '2026-03-27', '10:00:00', 'Confirmed'],
    ['sunita', 'Deep Cleansing Facial', '2026-03-27', '11:30:00', 'Confirmed'],
    ['anil', 'Hot Towel Shave', '2026-03-27', '13:00:00', 'Pending'],
    ['rina', 'Classic Manicure', '2026-03-27', '14:00:00', 'Confirmed'],
    ['nitesh', 'Premium Fade & Style', '2026-03-27', '16:30:00', 'Cancelled'],
    ['puja', 'Party Makeup', '2026-03-28', '09:00:00', 'Confirmed'],
    ['sagar', 'Beard Trim & Line Up', '2026-03-28', '10:30:00', 'Confirmed'],
    ['asmita', 'Full Body Massage', '2026-03-28', '12:00:00', 'Pending'],
    ['bikash', 'Classic Haircut', '2026-03-28', '15:00:00', 'Confirmed'],
    ['shristi', 'Bridal Hair & Makeup', '2026-03-29', '09:00:00', 'Confirmed'],
    ['aarav', 'Premium Fade & Style', '2026-03-30', '11:00:00', 'Pending'],
    ['sabina', 'Keratin Treatment', '2026-03-30', '13:00:00', 'Confirmed'],
    ['dipesh', 'Beard Trim & Line Up', '2026-03-30', '17:00:00', 'Confirmed'],
    ['anjali', 'Anti-Aging Gold Facial', '2026-03-31', '10:00:00', 'Pending'],
    ['santosh', 'Hot Towel Shave', '2026-03-31', '11:30:00', 'Confirmed'],
    ['nima', 'Hair Color & Highlights', '2026-03-31', '13:00:00', 'Confirmed'],
    ['pradeep', 'Classic Haircut', '2026-04-01', '16:00:00', 'Confirmed'],
    ['alisha', 'Classic Manicure', '2026-04-01', '17:30:00', 'Pending']
];

$stmtBook = $pdo->prepare("INSERT INTO bookings (user_id, service_id, booking_date, booking_time, status, day_of_week) VALUES (?, ?, ?, ?, ?, ?)");
foreach ($bookingsData as $b) {
    if (!isset($userIds[$b[0]]) || !isset($serviceIds[$b[1]])) continue;
    
    // Check if duplicate
    $dup = $pdo->prepare("SELECT id FROM bookings WHERE user_id=? AND service_id=? AND booking_date=? AND booking_time=?");
    $dup->execute([$userIds[$b[0]], $serviceIds[$b[1]], $b[2], $b[3]]);
    if (!$dup->fetchColumn()) {
        $dayOfWeek = (int) date('N', strtotime($b[2]));
        $stmtBook->execute([$userIds[$b[0]], $serviceIds[$b[1]], $b[2], $b[3], $b[4], $dayOfWeek]);
    }
}
echo "Inserted bookings.\n";

$reviewsData = [
    // customer_user, service_name, rating, comment
    ['aarav', 'Premium Fade & Style', 5, 'Got a fade and beard trim. Roshan dai did an excellent job. The salon is very clean and professional.'],
    ['anjali', 'Deep Cleansing Facial', 4, 'The facial was exceptionally relaxing, but I had to wait 15 minutes past my appointment time. Still, great service overall.'],
    ['shristi', 'Bridal Hair & Makeup', 5, 'Did my bridal makeup here! The team was so supportive, and the makeup lasted the entire event without caking. Highly recommended!'],
    ['puja', 'Classic Manicure', 2, 'Average manicure. The polish started chipping after just two days. Expected a bit better quality for the price I paid.'],
    ['bikash', 'Head & Shoulder Massage', 5, 'Best head massage I\'ve had in Kathmandu. Really relieved my tension after a long week at work.'],
    ['nitesh', 'Classic Haircut', 3, 'Got a standard haircut. It wasn\'t exactly the style I showed in the picture, but it still looks decent.'],
    ['kavita', 'Keratin Treatment', 5, 'Absolutely love my new keratin treatment! My hair has never felt so smooth and frizz-free. Thank you!'],
    ['sabina', 'Classic Pedicure', 4, 'The seating is very comfortable and the service is great, but the background music was a bit too loud for a relaxing spa day.'],
    ['asmita', 'Hair Color & Highlights', 1, 'Terrible experience. They completely messed up my highlight shades, it came out much brassier than the reference picture.'],
    ['sagar', 'Hot Towel Shave', 5, 'Quick, extremely smooth, and efficient hot towel shave. Will definitely be making this my regular spot.'],
    ['anil', 'Beard Trim & Line Up', 5, 'Always consistent and the staff is super friendly. They know exactly how I like my beard lined up.'],
    ['rina', 'Full Body Massage', 5, 'Booked a full body massage for my birthday treat to myself. Truly a rejuvenating and peaceful experience.'],
    ['nima', 'Anti-Aging Gold Facial', 5, 'The esthetician was very knowledgeable. She explained every product she was using, and my skin feels glowing.'],
    ['manish', 'Classic Haircut', 3, 'Fair pricing and decent haircut, but the AC wasn\'t working properly that day, so it was uncomfortably hot inside.'],
    ['sunita', 'Party Makeup', 5, 'Got ready for a wedding reception here. The makeup artist understood my skin tone perfectly. Looked very subtle and natural!'],
    ['santosh', 'Premium Fade & Style', 4, 'Good haircut. Bought their hair pomade on the way out, it smells amazing and has great hold.'],
    ['alisha', 'Classic Pedicure', 5, 'Very thorough and relaxing pedicure. They really took their time with the scrub and massage.'],
    ['pradeep', 'Classic Haircut', 3, 'Was a bit rushed today. Usually they take their time, but I felt like they were hurrying me out of the chair this time.'],
    ['dipesh', 'Premium Fade & Style', 5, 'The VIP treatment! Excellent skin fade. Highly recommend asking for Bikash when you book.'],
    ['roshni', 'Deep Cleansing Facial', 5, 'My skin feels tighter and so incredibly clean. Worth every penny, looking forward to my next session.']
];

$stmtRev = $pdo->prepare("INSERT INTO reviews (user_id, review_type, reference_id, rating, comment) VALUES (?, 'Service', ?, ?, ?)");
foreach ($reviewsData as $r) {
    if (!isset($userIds[$r[0]]) || !isset($serviceIds[$r[1]])) continue;
    
    // Check duplicate
    $dup = $pdo->prepare("SELECT id FROM reviews WHERE user_id=? AND reference_id=? AND review_type='Service'");
    $dup->execute([$userIds[$r[0]], $serviceIds[$r[1]]]);
    if (!$dup->fetchColumn()) {
        $stmtRev->execute([$userIds[$r[0]], $serviceIds[$r[1]], $r[2], $r[3]]);
    }
}
echo "Inserted reviews.\n";

echo "Seeding completed successfully!\n";
