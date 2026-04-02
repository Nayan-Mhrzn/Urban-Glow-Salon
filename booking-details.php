<?php
/**
 * Customer Booking Details Page - Urban Glow Salon
 */
require_once 'includes/config.php';
requireLogin();

$bookingId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$userId = $_SESSION['user_id'];

// Get booking details and service info
$stmt = $pdo->prepare("
    SELECT b.*, s.name as service_name, s.image as service_image, s.price, s.duration_mins 
    FROM bookings b
    JOIN services s ON b.service_id = s.id
    WHERE b.id = ? AND b.user_id = ?
");
$stmt->execute([$bookingId, $userId]);
$booking = $stmt->fetch();

if (!$booking) {
    setFlash('error', 'Booking not found or you do not have permission to view this booking.');
    redirect(SITE_URL . '/dashboard.php');
}

$pageTitle = 'Booking #' . $booking['id'];
require_once 'includes/header.php';
?>

<div class="bg-gray-50 min-h-[calc(100vh-80px)] py-12">
    <div class="max-w-4xl mx-auto px-6">
        
        <!-- Back Button -->
        <a href="<?= SITE_URL ?>/dashboard.php" class="inline-flex items-center text-sm font-semibold text-gray-500 hover:text-primary mb-6 transition-colors group">
            <i class="fas fa-arrow-left mr-2 group-hover:-translate-x-1 transition-transform"></i> Back to Dashboard
        </a>

        <!-- Booking Header Card -->
        <div class="bg-white rounded-2xl shadow-[0_2px_16px_rgba(67,57,242,0.03)] border border-gray-100 p-8 mb-6 relative overflow-hidden">
            <!-- Decorative accent -->
            <div class="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-bl-full -z-0"></div>
            
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
                <div>
                    <h1 class="text-3xl font-extrabold text-gray-900 mb-2 tracking-tight">Booking #<?= sanitize($booking['id']) ?></h1>
                    <p class="text-gray-500 text-sm font-medium">Created on <?= date('F j, Y \a\t g:i A', strtotime($booking['created_at'])) ?></p>
                </div>
                
                <div class="text-left md:text-right">
                    <?php 
                        $status = strtolower($booking['status'] ?? 'pending');
                        $statusClass = match($status) {
                            'pending' => 'bg-amber-100 text-amber-800 ring-1 ring-amber-200',
                            'confirmed' => 'bg-blue-100 text-blue-800 ring-1 ring-blue-200',
                            'completed' => 'bg-green-100 text-green-800 ring-1 ring-green-200',
                            'cancelled' => 'bg-red-100 text-red-800 ring-1 ring-red-200',
                            default => 'bg-gray-100 text-gray-800 ring-1 ring-gray-200'
                        };
                    ?>
                    <div class="inline-block px-4 py-1.5 rounded-full text-[13px] font-extrabold tracking-widest uppercase <?= $statusClass ?> mb-2">
                        <?= sanitize($booking['status'] ?? 'Pending') ?>
                    </div>
                </div>
            </div>

            <hr class="my-6 border-gray-100">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Appointment Date & Time -->
                <div>
                    <h3 class="text-xs font-extrabold text-gray-400 uppercase tracking-widest mb-3 flex items-center gap-2">
                        <i class="far fa-calendar-alt"></i> Appointment Schedule
                    </h3>
                    <p class="text-[16px] text-gray-900 font-bold leading-relaxed">
                        <?= date('l, F j, Y', strtotime($booking['booking_date'])) ?><br>
                        <span class="text-primary mt-1 inline-flex items-center gap-2">
                            <i class="far fa-clock"></i> <?= date('h:i A', strtotime($booking['booking_time'])) ?>
                        </span>
                    </p>
                </div>
                
                <!-- Additional Notes -->
                <?php if (!empty($booking['notes'])): ?>
                <div>
                    <h3 class="text-xs font-extrabold text-gray-400 uppercase tracking-widest mb-3 flex items-center gap-2">
                        <i class="fas fa-sticky-note"></i> Special Requests / Notes
                    </h3>
                    <p class="text-[15px] text-gray-800 font-medium leading-relaxed italic bg-gray-50 p-4 rounded-xl border border-gray-100 shadow-inner">
                        "<?= sanitize($booking['notes']) ?>"
                    </p>
                </div>
                <?php else: ?>
                <div>
                    <h3 class="text-xs font-extrabold text-gray-400 uppercase tracking-widest mb-3 flex items-center gap-2">
                        <i class="fas fa-sticky-note"></i> Special Requests / Notes
                    </h3>
                    <p class="text-[14px] text-gray-400 font-medium leading-relaxed italic">
                        No additional requests or notes provided for this appointment.
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Service Item Card -->
        <div class="bg-white rounded-2xl shadow-[0_2px_16px_rgba(67,57,242,0.03)] border border-gray-100 overflow-hidden">
            <div class="px-8 py-5 border-b border-gray-100 bg-gray-50/50">
                <h2 class="text-lg font-extrabold text-gray-900 tracking-tight">Service Details</h2>
            </div>
            
            <div class="p-6 sm:px-8 flex flex-col md:flex-row items-start md:items-center gap-6 hover:bg-[#EEF0FF] transition-colors group">
                <div class="w-24 h-24 bg-white rounded-xl border border-gray-200 flex items-center justify-center p-2 flex-shrink-0 shadow-sm group-hover:border-primary/30 transition-colors overflow-hidden">
                    <img src="<?= SITE_URL ?>/images/<?= sanitize($booking['service_image'] ?? 'placeholder-service.png') ?>" alt="<?= sanitize($booking['service_name']) ?>" class="w-full h-full object-cover rounded-lg" onerror="this.src='https://via.placeholder.com/100?text=Service'">
                </div>
                
                <div class="flex-1">
                    <h4 class="font-extrabold text-gray-900 text-xl mb-2 leading-snug"><?= sanitize($booking['service_name']) ?></h4>
                    <div class="flex items-center gap-4 text-[14px] text-gray-500 font-semibold">
                        <span class="bg-gray-100 px-3 py-1 rounded-md flex items-center gap-1.5 border border-gray-200"><i class="fas fa-hourglass-half text-gray-400"></i> <?= $booking['duration_mins'] ?> mins</span>
                    </div>
                </div>

                <div class="text-left md:text-right mt-4 md:mt-0 w-full md:w-auto">
                    <div class="text-[13px] text-gray-400 font-extrabold uppercase tracking-widest mb-1">Estimated Price</div>
                    <span class="font-extrabold text-gray-900 text-2xl"><?= formatPrice($booking['price']) ?></span>
                </div>
            </div>
            
        </div>

    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
