<?php
/**
 * My Orders History - Urban Glow Salon
 */
$pageTitle = 'Order History';
require_once '../config/config.php';
requireLogin();

$userId = $_SESSION['user_id'];

// Fetch all user orders
$stmtOrders = $pdo->prepare("
    SELECT o.*, 
           (SELECT p.image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = o.id LIMIT 1) as first_product_image,
           (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as item_count
    FROM orders o 
    WHERE o.user_id = ? 
    ORDER BY o.created_at DESC
");
$stmtOrders->execute([$userId]);
$orders = $stmtOrders->fetchAll();

require_once '../partials/header.php';
?>

<!-- Main Layout Wrapper -->
<div class="bg-[#f0f4f8] min-h-[calc(100vh-80px)]" style="background-image: url('data:image/svg+xml,%3Csvg width=\'20\' height=\'20\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cpath d=\'M1 1h18v18H1V1zm1 1v16h16V2H2z\' fill=\'%23dbeafe\' fill-opacity=\'0.3\' fill-rule=\'evenodd\'/%3E%3C/svg%3E');">
    <div class="max-w-[1200px] mx-auto px-4 sm:px-6 py-12">
        
        <!-- White Card Container -->
        <div class="bg-white rounded-[32px] shadow-[0_8px_40px_rgb(0,0,0,0.04)] p-8 md:p-12 mb-12 relative overflow-hidden">
            <!-- Header Area -->
            <div class="mb-10">
                <h1 class="text-[36px] font-extrabold text-[#111827] mb-1">Order <span class="text-[#3b82f6]">History</span></h1>
                <p class="text-[17px] text-gray-500 font-medium">Manage and track your recent purchases</p>
            </div>

            <?php if (empty($orders)): ?>
                <div class="text-center py-20 border-2 border-dashed border-gray-200 rounded-3xl">
                    <i class="fas fa-box-open text-6xl text-gray-300 mb-4"></i>
                    <h2 class="text-2xl font-bold text-gray-700 mb-2">No Orders Yet</h2>
                    <p class="text-gray-500 mb-6">You haven't placed any orders. Start exploring our premium products!</p>
                    <a href="../shop/products.php" class="inline-block bg-[#3b82f6] hover:bg-[#2563eb] text-white font-bold px-8 py-3 rounded-full transition-colors">Go to Shop</a>
                </div>
            <?php else: ?>
                <!-- Orders List -->
                <div class="space-y-5">
                    <?php foreach($orders as $order): ?>
                        <!-- Single Order Row -->
                        <div class="bg-white border-2 border-[#f1f5f9] hover:border-[#e2e8f0] rounded-2xl p-5 flex flex-col md:flex-row items-center justify-between gap-6 transition-colors shadow-sm">
                            
                            <!-- Left: Icon & Order ID & Date -->
                            <div class="flex items-center gap-5 w-full md:w-auto">
                                <div class="w-14 h-14 bg-[#eff6ff] rounded-2xl flex items-center justify-center text-[#3b82f6] flex-shrink-0">
                                    <i class="fas fa-shopping-bag text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">ORDER ID: #<?= $order['id'] ?></p>
                                    <p class="text-[16px] font-extrabold text-[#111827]"><?= date('M j, Y', strtotime($order['created_at'])) ?></p>
                                </div>
                            </div>

                            <!-- Middle: Status Badge -->
                            <div class="w-full md:w-[150px] flex justify-start md:justify-center">
                                <?php 
                                    $statusBadgeConfig = [
                                        'Pending' => 'bg-[#eff6ff] text-[#3b82f6]',
                                        'Confirmed' => 'bg-[#eff6ff] text-[#3b82f6]',
                                        'Processing' => 'bg-[#eff6ff] text-[#3b82f6]',
                                        'Shipped' => 'bg-purple-50 text-purple-600',
                                        'Delivered' => 'bg-[#ecfdf5] text-[#10b981]',
                                        'Cancelled' => 'bg-red-50 text-red-600'
                                    ];
                                    
                                    // Treat pending/processing as Confirmed to match UI
                                    $displayStatus = $order['status'];
                                    if(in_array($displayStatus, ['Pending', 'Processing'])) {
                                        $displayStatus = 'Confirmed';
                                    }
                                    
                                    $badgeClass = $statusBadgeConfig[$displayStatus] ?? 'bg-gray-100 text-gray-600';
                                ?>
                                <span class="px-4 py-1.5 <?= $badgeClass ?> rounded-full text-[11px] font-extrabold tracking-widest uppercase">
                                    <?= $displayStatus ?>
                                </span>
                            </div>

                            <!-- Right: Amount & Actions -->
                            <div class="flex flex-col md:flex-row md:items-center gap-5 w-full md:w-auto md:ml-auto">
                                <div class="text-left md:text-right mr-4">
                                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">TOTAL AMOUNT</p>
                                    <p class="text-[20px] font-extrabold text-[#3b82f6]"><?= formatPrice($order['total_amount']) ?></p>
                                </div>
                                <div class="flex gap-3 mt-4 md:mt-0">
                                    <a href="order-details.php?id=<?= $order['id'] ?>" class="bg-[#111827] hover:bg-black text-white text-[13px] font-bold px-6 py-2.5 rounded-full transition-colors whitespace-nowrap">
                                        View Details
                                    </a>
                                    
                                    <?php if(in_array($order['status'], ['Pending', 'Processing'])): ?>
                                        <button onclick="cancelOrder(<?= $order['id'] ?>)" class="bg-[#fef2f2] hover:bg-[#fee2e2] text-[#ef4444] text-[13px] font-bold px-6 py-2.5 rounded-full transition-colors flex items-center whitespace-nowrap">
                                            Cancel
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
        </div>
    </div>
</div>

<script>
async function cancelOrder(id) {
    if(confirm('Are you sure you want to cancel Order #' + id + '? This action cannot be undone.')) {
        try {
            const response = await fetch('<?= SITE_URL ?>/api/cancel-order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_id: id })
            });
            const data = await response.json();
            
            if(data.success) {
                alert('Order cancelled successfully.');
                location.reload(); 
            } else {
                alert('Failed to cancel order: ' + (data.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error cancelling order:', error);
            alert('An error occurred while cancelling the order.');
        }
    }
}
</script>

<?php require_once '../partials/footer.php'; ?>
