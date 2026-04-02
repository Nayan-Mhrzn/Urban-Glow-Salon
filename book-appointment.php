<?php
/**
 * Book Appointment Page - Urban Glow Salon
 */
$pageTitle = 'Book Appointment';
require_once 'includes/config.php';

// Get pre-selected service
$selectedServiceId = (int)($_GET['service'] ?? 0);

// Fetch all active services
$stmt = $pdo->query("SELECT * FROM services WHERE is_active = 1 ORDER BY category, name");
$services = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<!-- Booking Hero -->
<section class="hero-gradient py-10 text-center">
    <div class="max-w-4xl mx-auto px-6">
        <h1 class="text-3xl md:text-4xl font-bold mb-2">Book an <span class="text-primary">Appointment</span></h1>
        <p class="text-gray-600">Choose your service, pick a date & time</p>
    </div>
</section>

<!-- Booking Form -->
<div class="max-w-3xl mx-auto px-6 py-10">
    <?php if (!isLoggedIn()): ?>
    <div class="bg-yellow-50 border-2 border-yellow-200 rounded-2xl p-8 text-center">
        <i class="fas fa-lock text-4xl text-yellow-500 mb-4"></i>
        <h3 class="text-xl font-bold text-gray-900 mb-2">Login Required</h3>
        <p class="text-gray-600 mb-5">Please login to book an appointment.</p>
        <a href="login.php" class="bg-primary text-white px-8 py-3 rounded-full text-sm font-semibold hover:bg-primary-dark transition-all">Login Now</a>
    </div>
    <?php else: ?>

    <!-- Step Indicators -->
    <div class="flex justify-center gap-6 md:gap-10 mb-10">
        <div class="flex items-center gap-2 text-primary font-semibold text-sm" id="step1Indicator">
            <span class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center text-sm font-bold">1</span>
            Select Service
        </div>
        <div class="flex items-center gap-2 text-gray-400 text-sm" id="step2Indicator">
            <span class="w-8 h-8 rounded-full bg-gray-200 text-gray-600 flex items-center justify-center text-sm font-bold">2</span>
            Date & Time
        </div>
        <div class="flex items-center gap-2 text-gray-400 text-sm" id="step3Indicator">
            <span class="w-8 h-8 rounded-full bg-gray-200 text-gray-600 flex items-center justify-center text-sm font-bold">3</span>
            Confirm
        </div>
    </div>

    <form action="<?= SITE_URL ?>/api/book-service.php" method="POST" id="bookingForm">
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

        <!-- Step 1: Select Service -->
        <div id="step1">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Choose a Service</h2>
            <div class="mb-4">
                <select name="service_id" id="serviceSelect" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-sm focus:border-primary outline-none transition-all bg-white">
                    <option value="">Select a service...</option>
                    <?php 
                    $currentCat = '';
                    foreach ($services as $service): 
                        if ($service['category'] !== $currentCat) {
                            if ($currentCat !== '') echo '</optgroup>';
                            echo '<optgroup label="' . $service['category'] . '">';
                            $currentCat = $service['category'];
                        }
                    ?>
                        <option value="<?= $service['id'] ?>" data-price="<?= $service['price'] ?>" data-duration="<?= $service['duration_mins'] ?>" data-name="<?= sanitize($service['name']) ?>" <?= $selectedServiceId === $service['id'] ? 'selected' : '' ?>>
                            <?= sanitize($service['name']) ?> — <?= formatPrice($service['price']) ?> (<?= $service['duration_mins'] ?> mins)
                        </option>
                    <?php endforeach; ?>
                    </optgroup>
                </select>
            </div>
            <button type="button" onclick="goToStep(2)" class="bg-primary text-white px-8 py-3 rounded-full text-sm font-semibold hover:bg-primary-dark transition-all">
                Next: Pick Date & Time <i class="fas fa-arrow-right ml-1"></i>
            </button>
        </div>

        <!-- Step 2: Date & Time -->
        <div id="step2" class="hidden">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Pick Date & Time</h2>

            <!-- Date Picker -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Select Date</label>
                <input type="date" name="booking_date" id="bookingDate" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>" class="w-full max-w-xs px-4 py-3 border-2 border-gray-200 rounded-xl text-sm focus:border-primary outline-none transition-all">
            </div>

            <!-- Hidden input for selected time -->
            <input type="hidden" name="booking_time" id="bookingTime" value="">

            <!-- Slot Cards Container -->
            <div id="slotContainer" class="mb-6">
                <!-- Initial state -->
                <div id="slotPlaceholder" class="text-center py-10 text-gray-400">
                    <i class="far fa-calendar-alt text-3xl mb-2"></i>
                    <p class="text-sm font-medium">Select a date to see available time slots</p>
                </div>

                <!-- Skeleton Loader (hidden by default) -->
                <div id="slotSkeleton" class="hidden">
                    <div class="grid grid-cols-1 gap-3">
                        <div class="slot-skeleton h-[72px] rounded-xl"></div>
                        <div class="slot-skeleton h-[72px] rounded-xl"></div>
                        <div class="slot-skeleton h-[72px] rounded-xl"></div>
                    </div>
                    <p id="skeletonText" class="text-center text-xs text-gray-400 mt-3 hidden">Finding best slots…</p>
                </div>

                <!-- Recommended Slots -->
                <div id="recommendedSection" class="hidden mb-5">
                    <h3 class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-1.5">
                        <span class="text-base">⭐</span> Recommended For You
                    </h3>
                    <div id="recommendedGrid" class="grid grid-cols-1 gap-3"></div>
                </div>

                <!-- Other Times -->
                <div id="otherTimesSection" class="hidden">
                    <h3 class="text-sm font-bold text-gray-700 mb-3">Other Available Times</h3>
                    <div id="otherTimesGrid" class="grid grid-cols-1 md:grid-cols-3 gap-3"></div>
                </div>

                <!-- Fully Booked -->
                <div id="fullyBookedMsg" class="hidden text-center py-10">
                    <i class="fas fa-calendar-times text-3xl text-red-300 mb-2"></i>
                    <p class="text-sm font-semibold text-gray-600">No slots available on this date</p>
                    <p class="text-xs text-gray-400 mt-1">Try selecting a different date</p>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Special Notes (optional)</label>
                <textarea name="notes" rows="3" placeholder="Any special requests..." class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-sm focus:border-primary outline-none transition-all resize-none"></textarea>
            </div>

            <div class="flex gap-3">
                <button type="button" onclick="goToStep(1)" class="px-6 py-3 border-2 border-gray-200 rounded-full text-sm font-semibold text-gray-700 hover:bg-gray-100 transition-all">
                    <i class="fas fa-arrow-left mr-1"></i> Back
                </button>
                <button type="button" onclick="goToStep(3)" class="bg-primary text-white px-8 py-3 rounded-full text-sm font-semibold hover:bg-primary-dark transition-all">
                    Next: Confirm <i class="fas fa-arrow-right ml-1"></i>
                </button>
            </div>
        </div>

        <!-- Step 3: Confirm -->
        <div id="step3" class="hidden">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Confirm Your Booking</h2>
            
            <div class="bg-gradient-to-br from-white to-gray-50 border-2 border-gray-200 rounded-2xl p-6 mb-6">
                <h3 class="text-lg font-bold mb-4">Booking Summary</h3>
                <div class="space-y-3">
                    <div class="flex justify-between text-sm border-b border-gray-100 pb-2">
                        <span class="text-gray-600">Service</span>
                        <span class="font-medium" id="summaryService">—</span>
                    </div>
                    <div class="flex justify-between text-sm border-b border-gray-100 pb-2">
                        <span class="text-gray-600">Date</span>
                        <span class="font-medium" id="summaryDate">—</span>
                    </div>
                    <div class="flex justify-between text-sm border-b border-gray-100 pb-2">
                        <span class="text-gray-600">Time</span>
                        <span class="font-medium" id="summaryTime">—</span>
                    </div>
                    <div class="flex justify-between text-sm border-b border-gray-100 pb-2">
                        <span class="text-gray-600">Duration</span>
                        <span class="font-medium" id="summaryDuration">—</span>
                    </div>
                    <div class="flex justify-between text-base pt-2 font-bold text-primary">
                        <span>Total</span>
                        <span id="summaryPrice">—</span>
                    </div>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="button" onclick="goToStep(2)" class="px-6 py-3 border-2 border-gray-200 rounded-full text-sm font-semibold text-gray-700 hover:bg-gray-100 transition-all">
                    <i class="fas fa-arrow-left mr-1"></i> Back
                </button>
                <button type="submit" class="bg-primary text-white px-8 py-3 rounded-full text-sm font-semibold hover:bg-primary-dark transition-all animate-pulse-glow">
                    <i class="fas fa-calendar-check mr-1"></i> Confirm Booking
                </button>
            </div>
        </div>
    </form>
    <?php endif; ?>
</div>

<script>
const siteUrl = '<?= SITE_URL ?>';
let currentRecommended = []; // track recommended slots for skip logging
let selectedSlotDisplay = ''; // for summary

function goToStep(step) {
    document.getElementById('step1').classList.add('hidden');
    document.getElementById('step2').classList.add('hidden');
    document.getElementById('step3').classList.add('hidden');
    document.getElementById('step' + step).classList.remove('hidden');

    for (let i = 1; i <= 3; i++) {
        const ind = document.getElementById('step' + i + 'Indicator');
        const circle = ind.querySelector('span');
        if (i <= step) {
            ind.classList.remove('text-gray-400');
            ind.classList.add(i < step ? 'text-green-600' : 'text-primary');
            circle.classList.remove('bg-gray-200', 'text-gray-600');
            circle.classList.add(i < step ? 'bg-green-500' : 'bg-primary', 'text-white');
        } else {
            ind.classList.remove('text-primary', 'text-green-600');
            ind.classList.add('text-gray-400');
            circle.classList.remove('bg-primary', 'bg-green-500', 'text-white');
            circle.classList.add('bg-gray-200', 'text-gray-600');
        }
    }

    if (step === 2) {
        const serviceSelect = document.getElementById('serviceSelect');
        if (!serviceSelect.value) {
            alert('Please select a service first.');
            goToStep(1);
            return;
        }
    }
    if (step === 3) {
        const date = document.getElementById('bookingDate').value;
        const time = document.getElementById('bookingTime').value;
        if (!date || !time) {
            alert('Please select a date and time slot.');
            goToStep(2);
            return;
        }
        updateSummary();
    }
}

// ── Slot card creation ──
function createSlotCard(slot, isRecommended) {
    const card = document.createElement('div');
    card.className = isRecommended ? 'slot-card slot-card-recommended' : 'slot-card';
    card.dataset.time = slot.time;
    card.dataset.display = slot.display;
    card.dataset.recommended = isRecommended ? '1' : '0';

    let inner = '';
    if (isRecommended) {
        inner += '<span class="slot-badge">✦ Recommended</span>';
    }
    inner += '<div class="flex items-center justify-between w-full">';
    inner += '  <div class="flex items-center gap-3">';
    inner += '    <i class="far fa-clock text-base ' + (isRecommended ? 'text-primary' : 'text-gray-400') + '"></i>';
    inner += '    <div>';
    inner += '      <p class="text-sm font-bold text-gray-900">' + slot.display + '</p>';
    inner += '      <p class="text-[11px] text-gray-400 font-medium">Score: ' + Math.round(slot.final_score) + '/100</p>';
    inner += '    </div>';
    inner += '  </div>';
    inner += '  <div class="slot-check hidden"><i class="fas fa-check-circle text-primary text-lg"></i></div>';
    inner += '</div>';

    card.innerHTML = inner;

    card.addEventListener('click', function () {
        selectSlot(this);
    });

    return card;
}

// ── Select a slot ──
function selectSlot(card) {
    const time = card.dataset.time;
    const display = card.dataset.display;
    const isRec = card.dataset.recommended === '1';
    const date = document.getElementById('bookingDate').value;
    const serviceId = document.getElementById('serviceSelect').value;

    // Remove previous selection
    document.querySelectorAll('.slot-card-selected').forEach(function (el) {
        el.classList.remove('slot-card-selected');
        el.querySelector('.slot-check').classList.add('hidden');
    });

    // Apply selection
    card.classList.add('slot-card-selected');
    card.querySelector('.slot-check').classList.remove('hidden');

    // Store value
    document.getElementById('bookingTime').value = time;
    selectedSlotDisplay = display;

    // ── Interaction tracking ──
    // Log "selected" for chosen slot
    logInteraction(time, date, 'selected', serviceId);

    // If customer picked a non-recommended slot, log "skipped" for each recommended
    if (!isRec) {
        currentRecommended.forEach(function (recSlot) {
            logInteraction(recSlot.time, date, 'skipped', serviceId);
        });
    }
}

// ── Log interaction ──
function logInteraction(slotTime, date, action, serviceId) {
    fetch(siteUrl + '/api/log-slot-interaction.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ slot_time: slotTime, date: date, action: action, service_id: parseInt(serviceId) })
    }).catch(function () { /* silent fail */ });
}

// ── Fetch scored slots on date change ──
document.getElementById('bookingDate')?.addEventListener('change', function () {
    const date = this.value;
    const serviceId = document.getElementById('serviceSelect').value;
    if (!date || !serviceId) return;

    // Reset state
    document.getElementById('bookingTime').value = '';
    selectedSlotDisplay = '';
    currentRecommended = [];

    // Show skeleton
    document.getElementById('slotPlaceholder').classList.add('hidden');
    document.getElementById('recommendedSection').classList.add('hidden');
    document.getElementById('otherTimesSection').classList.add('hidden');
    document.getElementById('fullyBookedMsg').classList.add('hidden');
    document.getElementById('slotSkeleton').classList.remove('hidden');
    document.getElementById('skeletonText').classList.add('hidden');

    // Show "Finding best slots…" after 800ms
    var skeletonTimer = setTimeout(function () {
        document.getElementById('skeletonText').classList.remove('hidden');
    }, 800);

    fetch(siteUrl + '/api/get-scored-slots.php?date=' + encodeURIComponent(date) + '&service_id=' + encodeURIComponent(serviceId))
        .then(function (r) { return r.json(); })
        .then(function (data) {
            clearTimeout(skeletonTimer);
            document.getElementById('slotSkeleton').classList.add('hidden');

            if (data.fully_booked || (!data.recommended.length && !data.slots.length)) {
                document.getElementById('fullyBookedMsg').classList.remove('hidden');
                return;
            }

            // Store recommended for tracking
            currentRecommended = data.recommended || [];

            // Render recommended
            var recGrid = document.getElementById('recommendedGrid');
            recGrid.innerHTML = '';
            if (data.recommended && data.recommended.length > 0) {
                data.recommended.forEach(function (slot) {
                    recGrid.appendChild(createSlotCard(slot, true));
                });
                document.getElementById('recommendedSection').classList.remove('hidden');
            }

            // Render other times (exclude recommended)
            var recTimes = (data.recommended || []).map(function (s) { return s.time; });
            var otherSlots = data.slots.filter(function (s) { return recTimes.indexOf(s.time) === -1; });
            var otherGrid = document.getElementById('otherTimesGrid');
            otherGrid.innerHTML = '';
            if (otherSlots.length > 0) {
                otherSlots.forEach(function (slot) {
                    otherGrid.appendChild(createSlotCard(slot, false));
                });
                document.getElementById('otherTimesSection').classList.remove('hidden');
            }
        })
        .catch(function () {
            clearTimeout(skeletonTimer);
            document.getElementById('slotSkeleton').classList.add('hidden');
            document.getElementById('fullyBookedMsg').classList.remove('hidden');
        });
});

// ── Summary updater ──
function updateSummary() {
    const service = document.getElementById('serviceSelect');
    const selected = service.options[service.selectedIndex];
    document.getElementById('summaryService').textContent = selected.dataset.name || '\u2014';
    document.getElementById('summaryPrice').textContent = 'Rs. ' + Number(selected.dataset.price || 0).toLocaleString();
    document.getElementById('summaryDuration').textContent = (selected.dataset.duration || '\u2014') + ' mins';
    document.getElementById('summaryDate').textContent = document.getElementById('bookingDate').value || '\u2014';
    document.getElementById('summaryTime').textContent = selectedSlotDisplay || '\u2014';
}
</script>

<?php require_once 'includes/footer.php'; ?>
