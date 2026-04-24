<?php
/**
 * Service Details Page - Urban Glow Salon
 */
require_once '../app/Config/config.php';

$service_id = (int)($_GET['id'] ?? 0);
if (!$service_id) {
    setFlash('error', 'Service not found.');
    redirect(SITE_URL . '/booking/services.php');
}

// Fetch service
$stmt = $pdo->prepare("SELECT * FROM services WHERE id = ? AND is_active = 1");
$stmt->execute([$service_id]);
$service = $stmt->fetch();

if (!$service) {
    setFlash('error', 'Service not found.');
    redirect(SITE_URL . '/booking/services.php');
}

$pageTitle = $service['name'];

// Fetch related services
$stmt = $pdo->prepare("SELECT * FROM services WHERE category = ? AND id != ? AND is_active = 1 LIMIT 4");
$stmt->execute([$service['category'], $service_id]);
$relatedServices = $stmt->fetchAll();

// Fetch specialists for this service
$stmt = $pdo->prepare("
    SELECT u.id, u.full_name, u.profile_image, u.role,
           (SELECT COUNT(*) FROM bookings WHERE staff_id = u.id) as booking_count
    FROM users u 
    JOIN staff_services ss ON u.id = ss.staff_id 
    WHERE ss.service_id = ? AND u.role = 'STAFF'
    ORDER BY booking_count DESC
");
$stmt->execute([$service_id]);
$serviceSpecialists = $stmt->fetchAll();

require_once '../Includes/Partials/header.php';
?>

<div class="bg-[#f0f4f8] min-h-screen py-10 font-sans tracking-tight">
    <div class="max-w-[1280px] mx-auto px-4 sm:px-6 mb-16">
        
        <!-- Breadcrumbs -->
        <nav class="text-[14px] font-bold text-slate-500 mb-6 flex flex-wrap items-center gap-3">
            <a href="<?= SITE_URL ?>/index.php" class="hover:text-primary transition-colors flex items-center gap-1.5"><i class="fas fa-home"></i> Home</a>
            <i class="fas fa-chevron-right text-[10px] text-slate-300"></i>
            <a href="<?= SITE_URL ?>/booking/services.php" class="hover:text-primary transition-colors">Services</a>
            <i class="fas fa-chevron-right text-[10px] text-slate-300"></i>
            <span class="text-slate-900"><?= sanitize($service['name']) ?></span>
        </nav>

        <!-- Flash Messages -->
        <?php $flash = getFlash(); if ($flash): ?>
            <div class="mb-6 p-4 rounded-xl text-sm font-bold shadow-sm <?= $flash['type'] === 'error' ? 'bg-red-50 text-red-600 border border-red-100' : 'bg-green-50 text-green-600 border border-green-100' ?>">
                <?= $flash['message'] ?>
            </div>
        <?php endif; ?>

        <!-- Main Unified Card -->
        <div class="bg-white rounded-[24px] shadow-sm flex flex-col md:flex-row overflow-hidden border border-gray-100 relative min-h-[600px]">
            
            <!-- Left Side: Service Details -->
            <div class="md:w-[400px] flex-shrink-0 bg-[#fafbfc] border-r border-gray-100 p-10 flex flex-col items-center relative z-10">
                <!-- Circular Image Container -->
                <div class="mb-6 mt-4">
                    <div class="w-56 h-56 rounded-full overflow-hidden border-[8px] border-white shadow-[0_10px_30px_-5px_rgba(0,0,0,0.15)] relative">
                        <img src="<?= SITE_URL ?>/assets/img/<?= $service['image'] ?>" class="w-full h-full object-cover">
                    </div>
                </div>

                <div class="mb-3">
                    <span class="bg-blue-100/50 text-blue-600 text-[10px] font-black uppercase tracking-[0.2em] px-4 py-1.5 rounded-full">
                        <?= sanitize($service['category']) ?>
                    </span>
                </div>

                <h1 class="text-[26px] font-extrabold text-gray-900 text-center mb-3 leading-[1.1] tracking-tight">
                    <?= sanitize($service['name']) ?>
                </h1>

                <div class="flex items-center justify-center gap-4 text-center mb-6">
                    <span class="font-extrabold text-[24px] text-blue-600"><?= formatPrice($service['price']) ?></span>
                    <span class="w-[1px] h-6 bg-gray-200"></span>
                    <span class="text-[14px] font-semibold text-gray-500 flex items-center gap-1.5">
                        <i class="far fa-clock text-gray-400"></i> <?= sanitize($service['duration_mins']) ?> m
                    </span>
                </div>

                <p class="text-[14px] text-gray-500 text-center leading-relaxed font-medium mb-8">
                    <?= sanitize($service['description']) ?>
                </p>
                
                <div class="mt-auto w-full">
                    <!-- Service Cost Breakdown Box -->
                    <div class="bg-white border border-gray-200 rounded-[16px] p-5 shadow-sm">
                        <div class="flex justify-between items-center text-[13px] font-bold">
                            <span class="text-gray-500">Service Cost</span>
                            <span class="text-gray-900"><?= formatPrice($service['price']) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Booking Form -->
            <div class="flex-1 p-8 md:p-12 bg-white flex flex-col relative overflow-y-auto">
                
                <?php if (!isLoggedIn()): ?>
                    <!-- Overlay if not logged in -->
                    <div class="absolute inset-0 bg-white/50 backdrop-blur-[4px] z-20 flex flex-col items-center justify-center p-8 text-center pt-24">
                        <div class="bg-white p-8 rounded-3xl shadow-xl border border-gray-100 flex flex-col items-center max-w-sm w-full">
                            <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center text-2xl mb-4 shadow-inner">
                                <i class="fas fa-lock"></i>
                            </div>
                            <h3 class="text-xl font-extrabold text-gray-900 mb-2 mt-2">Login Required</h3>
                            <p class="text-gray-500 mb-8 text-[14px] font-medium leading-relaxed">Sign in with your account to securely schedule this appointment.</p>
                            <a href="<?= SITE_URL ?>/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white px-8 py-3.5 rounded-full text-[14px] font-extrabold tracking-wide shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all w-full">
                                Login / Sign Up
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <h2 class="text-[20px] font-extrabold text-gray-900 mb-8 flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-md bg-blue-50 flex items-center justify-center text-blue-600">
                        <i class="far fa-calendar-alt text-sm"></i>
                    </div>
                    Configure Appointment
                </h2>

                <form action="<?= SITE_URL ?>/api/book-service.php" method="POST" id="bookingForm" class="flex-1 flex flex-col relative z-10">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="service_id" id="serviceIdInput" value="<?= $service['id'] ?>">
                    <input type="hidden" name="booking_time" id="bookingTime" value="">

                    <div class="flex flex-col md:flex-row gap-8 mb-8">
                        <!-- Left: Date -->
                        <div class="md:w-[320px] flex-shrink-0 flex flex-col">
                            <label class="block text-[11px] font-black text-gray-400 uppercase tracking-widest mb-3">1. Select Date</label>
                            <input type="date" name="booking_date" id="bookingDate" required min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" class="w-full px-5 py-4 border-2 border-gray-50 rounded-xl text-[15px] font-bold text-gray-900 focus:border-blue-600 focus:ring-0 outline-none transition-all cursor-pointer bg-gray-50 hover:bg-white shadow-inner mb-3">
                            <button type="button" id="checkAvailabilityBtn" class="w-full bg-[#1e293b] hover:bg-[#0f172a] text-white py-3.5 rounded-xl font-bold text-[13px] tracking-wide shadow-sm flex items-center justify-center gap-2 transition-colors">
                                <i class="fas fa-search text-xs text-blue-300"></i> CHECK AVAILABILITY
                            </button>
                        </div>

                        <!-- Right: Specialist -->
                        <div class="flex-1 flex flex-col">
                            <label class="block text-[11px] font-black text-gray-400 uppercase tracking-widest mb-3">2. Choose Specialist</label>
                            <input type="hidden" name="staff_id" id="staffIdInput" value="">
                        
                        <div class="grid grid-cols-2 lg:grid-cols-3 gap-3">
                            <!-- Any Specialist -->
                            <div class="specialist-card cursor-pointer p-3 rounded-2xl border border-blue-600 bg-blue-50 transition-all flex flex-col items-center justify-center text-center relative overflow-hidden ring-2 ring-blue-600" data-id="">
                                <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-lg mb-2 shadow-sm">
                                    <i class="fas fa-users"></i>
                                </div>
                                <p class="text-[12px] font-extrabold text-gray-900 leading-tight">Any Specialist</p>
                                <p class="text-[9px] font-bold text-blue-500 mt-0.5 uppercase tracking-wider">Recommended</p>
                                <div class="absolute top-2 right-2 text-blue-600 opacity-100 check-icon">
                                    <i class="fas fa-check-circle text-sm"></i>
                                </div>
                            </div>
                            
                            <!-- Specific Specialists -->
                            <?php foreach($serviceSpecialists as $staff): ?>
                            <div class="specialist-card cursor-pointer p-3 rounded-2xl border border-gray-100 bg-white hover:border-blue-300 hover:bg-blue-50/30 transition-all flex flex-col items-center justify-center text-center relative overflow-hidden group" data-id="<?= $staff['id'] ?>">
                                <div class="w-10 h-10 rounded-full bg-gray-100 mb-2 overflow-hidden shadow-sm border-2 border-white group-hover:border-blue-100 transition-colors">
                                    <?php if($staff['profile_image']): ?>
                                        <img src="<?= SITE_URL ?>/assets/img/<?= $staff['profile_image'] ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center text-gray-400">
                                            <i class="fas fa-user text-sm"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <p class="text-[12px] font-bold text-gray-900 leading-tight"><?= sanitize($staff['full_name']) ?></p>
                                <p class="text-[9px] font-bold text-gray-400 mt-0.5 uppercase tracking-wider">BARBER</p>
                                <div class="absolute top-2 right-2 text-blue-600 opacity-0 check-icon transition-opacity">
                                    <i class="fas fa-check-circle text-sm"></i>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        </div>
                    </div>

                    <!-- Step 3: Special Requests -->
                    <div class="mb-10 pb-8 border-b border-gray-100">
                        <label class="block text-[11px] font-black text-gray-400 uppercase tracking-widest mb-3">3. Special Requests</label>
                        <textarea name="notes" rows="1" placeholder="Any specific requirements..." class="w-full px-5 py-4 border border-gray-100 rounded-xl text-[14px] font-medium text-gray-900 focus:border-blue-600 outline-none transition-all resize-none shadow-sm h-16"></textarea>
                    </div>

                    <!-- Time Slots Container -->
                    <div class="mb-8">
                        <div id="slotContainer">
                            <!-- Initial Placeholder -->
                            <div id="slotPlaceholder" class="p-8 text-center border-2 border-dashed border-gray-200 rounded-2xl text-gray-400 bg-gray-50/50">
                                <i class="far fa-clock text-2xl mb-2 text-gray-300"></i>
                                <p class="text-sm font-bold">Pick a date first</p>
                            </div>

                            <!-- Skeleton -->
                            <div id="slotSkeleton" class="hidden">
                                <div class="grid grid-cols-2 gap-3 mb-2">
                                    <div class="h-[60px] bg-slate-100 rounded-xl animate-pulse"></div>
                                    <div class="h-[60px] bg-slate-100 rounded-xl animate-pulse"></div>
                                    <div class="h-[60px] bg-slate-100 rounded-xl animate-pulse"></div>
                                    <div class="h-[60px] bg-slate-100 rounded-xl animate-pulse"></div>
                                </div>
                                <p class="text-xs text-center text-gray-400 font-medium">Analyzing schedules...</p>
                            </div>

                            <!-- Display Containers -->
                            <div id="recommendedSection" class="hidden mb-6">
                                <h3 class="text-[12px] font-extrabold text-gray-500 mb-4 flex items-center gap-1.5 uppercase tracking-widest">
                                    RECOMMENDED FOR YOU <span class="text-amber-400 ml-1">✨</span>
                                </h3>
                                <div id="recommendedGrid" class="grid grid-cols-1 sm:grid-cols-2 gap-4"></div>
                            </div>

                            <div id="otherTimesSection" class="hidden mb-6">
                                <h3 class="text-[12px] font-extrabold text-gray-500 mb-4 mt-8 uppercase tracking-widest">AVAILABLE TIME SLOTS</h3>
                                <div class="bg-gray-50 border border-gray-100 rounded-3xl p-6">
                                    <div id="otherTimesGrid" class="flex flex-wrap gap-2.5"></div>
                                </div>
                            </div>
                            
                            <!-- Fully Booked -->
                            <div id="fullyBookedMsg" class="hidden text-center p-8 border-2 border-dashed border-red-100 bg-red-50 text-red-500 rounded-2xl">
                                <i class="fas fa-calendar-times text-2xl mb-2 text-red-300"></i>
                                <p class="text-sm font-bold">Fully booked on this date</p>
                                <p class="text-xs text-red-400 mt-1">Please try another day.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="mt-auto pt-6">
                        <button type="submit" id="submitBtn" class="w-full py-4 rounded-full bg-blue-600 hover:bg-blue-700 text-white font-black tracking-wide text-[14px] shadow-[0_10px_20px_rgba(37,99,235,0.2)] hover:shadow-[0_15px_30px_rgba(37,99,235,0.3)] transition-all flex items-center justify-center gap-2 group">
                            COMPLETE BOOKING <i class="fas fa-arrow-right text-sm transform group-hover:translate-x-1 transition-transform"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const siteUrl = '<?= SITE_URL ?>';
let currentRecommended = []; 

function fetchSlots() {
    const date = document.getElementById('bookingDate').value;
    const serviceId = document.getElementById('serviceIdInput').value;
    const specialistId = document.getElementById('staffIdInput').value;
    
    if (!date) return;

    // Reset time hidden input
    document.getElementById('bookingTime').value = '';
    currentRecommended = [];

    // Loading states
    document.getElementById('slotPlaceholder')?.classList.add('hidden');
    document.getElementById('recommendedSection').classList.add('hidden');
    document.getElementById('otherTimesSection').classList.add('hidden');
    document.getElementById('fullyBookedMsg').classList.add('hidden');
    document.getElementById('slotSkeleton').classList.remove('hidden');

    const url = siteUrl + '/api/get-scored-slots.php?date=' + encodeURIComponent(date) + 
                '&service_id=' + encodeURIComponent(serviceId) + 
                (specialistId ? '&specialist_id=' + encodeURIComponent(specialistId) : '');

    fetch(url)
        .then(function (r) { return r.json(); })
        .then(function (data) {
            document.getElementById('slotSkeleton').classList.add('hidden');

            if (data.fully_booked || (!data.recommended.length && !data.slots.length)) {
                document.getElementById('fullyBookedMsg').classList.remove('hidden');
                return;
            }

            currentRecommended = data.recommended || [];
            
            // Render Recommended
            const recGrid = document.getElementById('recommendedGrid');
            recGrid.innerHTML = '';
            if (data.recommended && data.recommended.length > 0) {
                data.recommended.forEach(function (slot) {
                    recGrid.appendChild(createRecommendedCard(slot));
                });
                document.getElementById('recommendedSection').classList.remove('hidden');
            }

            // Render Others
            const recTimes = (data.recommended || []).map(function (s) { return s.time; });
            const otherSlots = data.slots.filter(function (s) { return recTimes.indexOf(s.time) === -1; });
            const otherGrid = document.getElementById('otherTimesGrid');
            otherGrid.innerHTML = '';
            if (otherSlots.length > 0) {
                otherSlots.forEach(function (slot) {
                    otherGrid.appendChild(createTimePill(slot));
                });
                document.getElementById('otherTimesSection').classList.remove('hidden');
            }
        })
        .catch(function () {
            document.getElementById('slotSkeleton').classList.add('hidden');
            document.getElementById('fullyBookedMsg').classList.remove('hidden');
        });
}

// Handle check availability button and date change
document.getElementById('checkAvailabilityBtn')?.addEventListener('click', fetchSlots);
document.getElementById('bookingDate')?.addEventListener('change', fetchSlots);

// Handle specialist selection
document.querySelectorAll('.specialist-card').forEach(card => {
    card.addEventListener('click', function() {
        // Reset all
        document.querySelectorAll('.specialist-card').forEach(c => {
            c.classList.remove('ring-2', 'ring-blue-600', 'bg-blue-50');
            c.classList.add('border-gray-100', 'bg-white');
            c.querySelector('.check-icon').classList.remove('opacity-100');
            c.querySelector('.check-icon').classList.add('opacity-0');
        });
        
        // Activate clicked
        this.classList.add('ring-2', 'ring-blue-600', 'bg-blue-50');
        this.classList.remove('border-gray-100', 'bg-white');
        this.querySelector('.check-icon').classList.remove('opacity-0');
        this.querySelector('.check-icon').classList.add('opacity-100');
        
        // Set value and fetch
        document.getElementById('staffIdInput').value = this.dataset.id;
        
        if (document.getElementById('bookingDate').value) {
            fetchSlots();
        }
    });
});

// UI: Recommended Card
function createRecommendedCard(slot) {
    const el = document.createElement('div');
    el.className = 'cursor-pointer p-4 rounded-3xl border border-gray-100 bg-white hover:border-blue-300 hover:shadow-md transition-all flex items-center justify-between slot-card relative overflow-hidden group';
    el.dataset.time = slot.time;
    el.dataset.display = slot.display;
    el.dataset.recommended = '1';
    
    // Check if slot has specialist attached
    const sp = slot.specialist;
    const spId = sp ? sp.id : '';
    el.dataset.specialist = spId;

    let spImageHtml = '<div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-400"><i class="fas fa-user text-sm"></i></div>';
    let spName = 'Any Specialist';
    
    if (sp) {
        if (sp.profile_image) {
            spImageHtml = '<img src="' + siteUrl + '/assets/img/' + sp.profile_image + '" class="w-10 h-10 rounded-full object-cover shadow-sm">';
        }
        spName = sp.full_name;
    }

    let statusText = slot.final_score > 75 ? 'Great' : (slot.final_score > 60 ? 'Good' : 'Available');

    let inner = `
        <div class="flex items-center gap-4 w-full">
            <div class="w-12 h-12 rounded-[14px] bg-blue-50 flex flex-col items-center justify-center text-blue-600 shadow-sm border border-blue-100/50 group-[.ring-2]:text-white group-[.ring-2]:bg-blue-600 transition-colors">
                <i class="far fa-clock text-xs mb-0.5"></i>
            </div>
            <div>
                <p class="text-[15px] font-black text-gray-900 leading-none mb-1">${slot.display}</p>
                <p class="text-[11px] font-semibold text-gray-400 capitalize">${statusText}</p>
            </div>
            
            <div class="ml-auto flex items-center gap-3">
                <div class="flex items-center gap-2 text-right">
                    <div class="hidden sm:block">
                        <p class="text-[12px] font-bold text-gray-900 leading-none mb-1">${spName}</p>
                        <p class="text-[10px] font-bold text-gray-400">Specialist</p>
                    </div>
                    ${spImageHtml}
                </div>
                <div class="w-12 text-right">
                    <span class="text-[13px] font-extrabold text-blue-600">${Math.round(slot.final_score)}%</span>
                </div>
            </div>
        </div>
    `;

    el.innerHTML = inner;
    el.addEventListener('click', function() { selectSlot(this); });
    return el;
}

// UI: Available Time Pill
function createTimePill(slot) {
    const el = document.createElement('div');
    el.className = 'cursor-pointer px-4 py-2.5 rounded-xl border border-gray-100 bg-white hover:border-gray-300 hover:bg-gray-50 transition-all slot-card w-[calc(20%-8px)] min-w-[70px] text-center ext-pill group';
    el.dataset.time = slot.time;
    el.dataset.display = slot.display;
    el.dataset.recommended = '0';
    el.dataset.specialist = '';

    el.innerHTML = `<span class="text-[13px] font-bold text-gray-600 group-hover:text-gray-900 group-[.ring-2]:text-blue-700 transition-colors">${slot.display}</span>`;

    el.addEventListener('click', function() { selectSlot(this); });
    return el;
}

function selectSlot(card) {
    const time = card.dataset.time;
    const isRec = card.dataset.recommended === '1';
    const date = document.getElementById('bookingDate').value;
    const serviceId = document.getElementById('serviceIdInput').value;
    const specialistId = card.dataset.specialist;

    // Reset previous visual state for all slot cards
    document.querySelectorAll('.slot-card').forEach(function (el) {
        // Remove card formatting
        el.classList.remove('ring-2', 'ring-blue-600', 'border-transparent');
        // If it's a pill, remove its specific active state
        if (el.classList.contains('ext-pill')) {
            el.classList.remove('bg-blue-50', 'border-blue-600');
            el.classList.add('bg-white', 'border-gray-100');
        }
    });

    // Apply active state
    if (card.classList.contains('ext-pill')) {
        card.classList.add('ring-2', 'ring-blue-600', 'bg-blue-50', 'border-transparent');
        card.classList.remove('bg-white', 'border-gray-100');
    } else {
        card.classList.add('ring-2', 'ring-blue-600', 'border-transparent');
    }

    // Store value
    document.getElementById('bookingTime').value = time;
    
    // If you explicitly tapped a recommended card that belongs to a specific specialist,
    // and you hadn't explicitly locked them in step 2, update the hidden input.
    if (specialistId && !document.getElementById('staffIdInput').value) {
        document.getElementById('staffIdInput').value = specialistId;
    }

    // Log interaction
    logInteraction(time, date, 'selected', serviceId);
    if (!isRec) {
        currentRecommended.forEach(function (recSlot) {
            logInteraction(recSlot.time, date, 'skipped', serviceId);
        });
    }
}

function logInteraction(slotTime, date, action, serviceId) {
    fetch(siteUrl + '/api/log-slot-interaction.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ slot_time: slotTime, date: date, action: action, service_id: parseInt(serviceId) })
    }).catch(function () {});
}

document.getElementById('bookingForm').addEventListener('submit', function(e) {
    if (!document.getElementById('bookingTime').value) {
        e.preventDefault();
        alert('Please select a time slot to complete your booking.');
    }
});

// Auto fetch slots on load since today's date is pre-filled
document.addEventListener('DOMContentLoaded', fetchSlots);
</script>

<?php require_once '../Includes/Partials/footer.php'; ?>
