<?php
/**
 * Slot Score Debug Page — Admin Only
 * Urban Glow Salon
 *
 * Usage: slot-debug.php?customer_id=X&service_id=Y&date=YYYY-MM-DD
 */
$pageTitle = 'Slot Score Debug';
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/core/slot_scorer.php';

// Admin gate is enforced by header.php (calls requireAdmin())

$customerId = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : null;
$serviceId  = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 0;
$date       = $_GET['date'] ?? '';

// Fetch customers and services for the form
$customers = $pdo->query("SELECT id, full_name, username FROM users WHERE role = 'CUSTOMER' ORDER BY full_name")->fetchAll();
$services  = $pdo->query("SELECT id, name, duration_mins FROM services WHERE is_active = 1 ORDER BY name")->fetchAll();

// Run scoring if all params provided
$result = null;
$customerName = '';
$serviceName  = '';
$serviceDuration = 0;

if ($customerId && $serviceId && $date) {
    $result = scoreSlots($pdo, $customerId, $serviceId, $date);

    // Get names for display
    foreach ($customers as $c) {
        if ($c['id'] === $customerId) { $customerName = $c['full_name']; break; }
    }
    foreach ($services as $s) {
        if ($s['id'] === $serviceId) { $serviceName = $s['name']; $serviceDuration = $s['duration_mins']; break; }
    }
}

require_once 'header.php';
?>

<!-- Debug Form -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mb-6">
    <h2 class="text-lg font-bold text-gray-900 mb-1">Slot Score Debugger</h2>
    <p class="text-sm text-gray-500 mb-5">Inspect the full scoring breakdown for any customer + service + date combination.</p>

    <form method="GET" class="flex flex-wrap items-end gap-4">
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Customer</label>
            <select name="customer_id" class="text-sm border border-gray-200 rounded-xl px-4 py-2.5 bg-white focus:border-primary outline-none min-w-[200px]">
                <option value="">Select customer…</option>
                <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $customerId === $c['id'] ? 'selected' : '' ?>><?= sanitize($c['full_name']) ?> (@<?= sanitize($c['username']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Service</label>
            <select name="service_id" class="text-sm border border-gray-200 rounded-xl px-4 py-2.5 bg-white focus:border-primary outline-none min-w-[200px]">
                <option value="">Select service…</option>
                <?php foreach ($services as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $serviceId === $s['id'] ? 'selected' : '' ?>><?= sanitize($s['name']) ?> (<?= $s['duration_mins'] ?> mins)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Date</label>
            <input type="date" name="date" value="<?= sanitize($date) ?>" class="text-sm border border-gray-200 rounded-xl px-4 py-2.5 focus:border-primary outline-none">
        </div>
        <button type="submit" class="px-6 py-2.5 bg-primary text-white rounded-xl text-sm font-semibold hover:bg-primary-dark transition-all">
            <i class="fas fa-bug mr-1"></i> Run Scorer
        </button>
    </form>
</div>

<?php if ($result !== null): ?>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
        <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1">Customer</p>
        <p class="text-base font-bold text-gray-900"><?= sanitize($customerName) ?: 'ID #' . $customerId ?></p>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
        <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1">Service</p>
        <p class="text-base font-bold text-gray-900"><?= sanitize($serviceName) ?> <span class="text-sm text-gray-400 font-medium">(<?= $serviceDuration ?> mins)</span></p>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
        <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1">Date</p>
        <p class="text-base font-bold text-gray-900"><?= date('D, M j, Y', strtotime($date)) ?></p>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
        <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1">Status</p>
        <?php if ($result['fully_booked']): ?>
            <p class="text-base font-bold text-red-600"><i class="fas fa-times-circle mr-1"></i> Fully Booked</p>
        <?php else: ?>
            <p class="text-base font-bold text-green-600"><i class="fas fa-check-circle mr-1"></i> <?= count($result['slots']) ?> slots available</p>
        <?php endif; ?>
    </div>
</div>

<?php if (!$result['fully_booked'] && !empty($result['slots'])): ?>

<!-- Score Breakdown Table -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-600 uppercase text-[11px]">
                <tr>
                    <th class="px-4 py-3 font-semibold">Rank</th>
                    <th class="px-4 py-3 font-semibold">Slot Time</th>
                    <th class="px-4 py-3 font-semibold text-center">History<br><span class="text-gray-400 normal-case">(25%)</span></th>
                    <th class="px-4 py-3 font-semibold text-center">Affinity<br><span class="text-gray-400 normal-case">(15%)</span></th>
                    <th class="px-4 py-3 font-semibold text-center">Gap-Fill<br><span class="text-gray-400 normal-case">(25%)</span></th>
                    <th class="px-4 py-3 font-semibold text-center">Demand<br><span class="text-gray-400 normal-case">(20%)</span></th>
                    <th class="px-4 py-3 font-semibold text-center">No-Show<br><span class="text-gray-400 normal-case">(15%)</span></th>
                    <th class="px-4 py-3 font-semibold text-center">Final Score</th>
                    <th class="px-4 py-3 font-semibold text-center">Recommended?</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php
                $rank = 0;
                foreach ($result['slots'] as $slot):
                    $rank++;
                    $isRec = $slot['is_recommended'];
                ?>
                <tr class="<?= $isRec ? 'bg-primary-bg/40' : 'hover:bg-gray-50' ?> transition-colors">
                    <td class="px-4 py-3 font-bold text-gray-400">#<?= $rank ?></td>
                    <td class="px-4 py-3">
                        <span class="font-bold text-gray-900"><?= $slot['display'] ?></span>
                        <span class="text-[11px] text-gray-400 ml-1">(<?= $slot['time'] ?>)</span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-block px-2 py-0.5 rounded-md text-xs font-semibold <?= $slot['history_score'] > 60 ? 'bg-green-100 text-green-700' : ($slot['history_score'] > 30 ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-600') ?>">
                            <?= number_format($slot['history_score'], 1) ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-block px-2 py-0.5 rounded-md text-xs font-semibold <?= $slot['affinity_score'] > 60 ? 'bg-green-100 text-green-700' : ($slot['affinity_score'] > 30 ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-600') ?>">
                            <?= number_format($slot['affinity_score'], 1) ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-block px-2 py-0.5 rounded-md text-xs font-semibold <?= $slot['gap_fill_score'] > 60 ? 'bg-green-100 text-green-700' : ($slot['gap_fill_score'] > 30 ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-600') ?>">
                            <?= number_format($slot['gap_fill_score'], 1) ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-block px-2 py-0.5 rounded-md text-xs font-semibold <?= $slot['demand_score'] > 60 ? 'bg-green-100 text-green-700' : ($slot['demand_score'] > 30 ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-600') ?>">
                            <?= number_format($slot['demand_score'], 1) ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-block px-2 py-0.5 rounded-md text-xs font-semibold <?= $slot['no_show_penalty'] > 0 ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600' ?>">
                            -<?= number_format($slot['no_show_penalty'], 1) ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-block px-2.5 py-1 rounded-lg text-sm font-extrabold <?= $isRec ? 'bg-primary text-white' : 'bg-gray-100 text-gray-800' ?>">
                            <?= number_format($slot['final_score'], 1) ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <?php if ($isRec): ?>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-primary-bg text-primary">
                                <i class="fas fa-star text-[9px]"></i> Yes
                            </span>
                        <?php else: ?>
                            <span class="text-gray-400 text-xs">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php elseif ($result['fully_booked']): ?>
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-10 text-center">
    <i class="fas fa-calendar-times text-4xl text-red-300 mb-3"></i>
    <p class="text-lg font-bold text-gray-700">Fully Booked</p>
    <p class="text-sm text-gray-500 mt-1">No available slots for this date.</p>
</div>
<?php endif; ?>

<?php endif; ?>

<?php require_once 'footer.php'; ?>

