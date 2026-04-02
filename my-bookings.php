<?php
/**
 * My Bookings History - Urban Glow Salon
 */
$pageTitle = 'Appointment History';
require_once 'includes/config.php';
requireLogin();

$userId = $_SESSION['user_id'];

// Fetch all user bookings
$stmtBookings = $pdo->prepare("
    SELECT b.id, b.booking_date, b.booking_time, b.status, s.name as service_name, s.price, s.duration_mins, s.image as service_image
    FROM bookings b 
    JOIN services s ON b.service_id = s.id 
    WHERE b.user_id = ? 
    ORDER BY b.booking_date DESC, b.booking_time DESC
");
$stmtBookings->execute([$userId]);
$bookings = $stmtBookings->fetchAll();

require_once 'includes/header.php';
?>

<!-- Main Layout Wrapper -->
<div class="bg-[#f0f4f8] min-h-[calc(100vh-80px)]" style="background-image: url('data:image/svg+xml,%3Csvg width=\'20\' height=\'20\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cpath d=\'M1 1h18v18H1V1zm1 1v16h16V2H2z\' fill=\'%23dbeafe\' fill-opacity=\'0.3\' fill-rule=\'evenodd\'/%3E%3C/svg%3E');">
    <div class="max-w-[1200px] mx-auto px-4 sm:px-6 py-12">
        
        <!-- White Card Container -->
        <div class="bg-white rounded-[32px] shadow-[0_8px_40px_rgb(0,0,0,0.04)] p-8 md:p-12 mb-12 relative overflow-hidden">
            <!-- Header Area -->
            <div class="mb-10">
                <h1 class="text-[36px] font-extrabold text-[#111827] mb-1">Appointment <span class="text-[#4f46e5]">History</span></h1>
                <p class="text-[17px] text-gray-500 font-medium">Manage and track your salon appointments</p>
            </div>

            <?php if (empty($bookings)): ?>
                <div class="text-center py-20 border-2 border-dashed border-gray-200 rounded-3xl">
                    <i class="fas fa-calendar-times text-6xl text-gray-300 mb-4"></i>
                    <h2 class="text-2xl font-bold text-gray-700 mb-2">No Appointments</h2>
                    <p class="text-gray-500 mb-6">You haven't booked any services yet.</p>
                    <a href="services.php" class="inline-block bg-[#4f46e5] hover:bg-[#4338ca] text-white font-bold px-8 py-3 rounded-full transition-colors">Book a Service</a>
                </div>
            <?php else: ?>
                <!-- Bookings List -->
                <div class="space-y-4">
                    <?php foreach($bookings as $booking): ?>
                        <!-- Single Booking Row -->
                        <div class="bg-white border-2 border-[#f1f5f9] hover:border-[#e2e8f0] rounded-2xl p-4 flex flex-col md:flex-row items-center gap-6 transition-colors shadow-sm">
                            
                            <!-- Left: Image Block -->
                            <div class="w-full md:w-[180px] h-[140px] bg-[#f8fafc] rounded-xl flex items-center justify-center flex-shrink-0 relative overflow-hidden">
                                <?php if ($booking['service_image']): ?>
                                    <img src="<?= SITE_URL ?>/images/<?= sanitize($booking['service_image']) ?>" alt="Service" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <i class="fas fa-cut text-4xl text-gray-300"></i>
                                <?php endif; ?>
                            </div>

                            <!-- Middle Column: Details -->
                            <div class="flex-1 w-full pl-2">
                                <div class="flex justify-between items-start w-full mb-3">
                                    <h3 class="text-[19px] font-extrabold text-[#111827]"><?= sanitize($booking['service_name']) ?></h3>
                                    
                                    <!-- Status Badge (Top Right) -->
                                    <?php 
                                        $statusBadgeConfig = [
                                            'Pending' => 'bg-[#eff6ff] text-[#3b82f6]',
                                            'Confirmed' => 'bg-[#eff6ff] text-[#3b82f6]',
                                            'Completed' => 'bg-[#ecfdf5] text-[#10b981]',
                                            'Cancelled' => 'bg-red-50 text-red-600'
                                        ];
                                        
                                        $displayStatus = strtoupper($booking['status']);
                                        if($booking['status'] === 'Confirmed' || $booking['status'] === 'Pending') $displayStatus = 'BOOKED';
                                        
                                        $badgeClass = $statusBadgeConfig[$booking['status']] ?? 'bg-gray-100 text-gray-600';
                                    ?>
                                    <span class="hidden md:inline-block px-4 py-1.5 <?= $badgeClass ?> rounded-full text-[11px] font-extrabold tracking-widest uppercase">
                                        <?= $displayStatus ?>
                                    </span>
                                </div>
                                
                                <div class="space-y-2.5 mb-5 w-full">
                                    <p class="text-[14px] text-gray-500 font-medium flex items-center gap-2">
                                        <i class="far fa-calendar-alt w-4 text-gray-400"></i> 
                                        <?= date('D, M j, Y', strtotime($booking['booking_date'])) ?>
                                    </p>
                                    <div class="flex items-center gap-3">
                                        <p class="text-[14px] text-[#4f46e5] font-semibold flex items-center gap-2">
                                            <i class="far fa-clock w-4 text-[#4f46e5]"></i> 
                                            <?php 
                                                $startTime = strtotime($booking['booking_date'] . ' ' . $booking['booking_time']);
                                                $endTime = $startTime + ($booking['duration_mins'] * 60);
                                                echo date('H:i:s', $startTime) . ' - ' . date('H:i:s', $endTime);
                                            ?>
                                        </p>
                                        <span class="text-[#f59e0b] text-[10px]">●</span>
                                        <span class="text-[11px] font-bold text-gray-400 tracking-wider">COMPLETE</span>
                                    </div>
                                </div>
                                
                                <div class="flex flex-col md:flex-row md:items-center justify-between w-full mt-6 gap-4">
                                    <p class="text-[20px] font-extrabold text-[#4f46e5]"><?= formatPrice($booking['price']) ?></p>
                                    
                                    <div class="flex items-center gap-3">
                                        <a href="booking-details.php?id=<?= $booking['id'] ?>" class="bg-[#111827] hover:bg-black text-white text-[13px] font-bold px-6 py-2.5 rounded-full transition-colors flex items-center gap-2 whitespace-nowrap">
                                            <i class="fas fa-eye text-[#9ca3af]"></i> View Details
                                        </a>
                                        <!-- Mobile Status Badge -->
                                        <span class="md:hidden px-4 py-1.5 <?= $badgeClass ?> rounded-full text-[11px] font-extrabold tracking-widest uppercase ml-auto">
                                            <?= $displayStatus ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
