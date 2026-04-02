<?php
/**
 * User Dashboard - Urban Glow Salon
 */
$pageTitle = 'My Dashboard';
require_once '../config/config.php';
requireLogin();

// Fetch fresh user data including addresses
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    logoutUser();
    redirect('login.php');
}

// Fetch recent orders (limit 5 for dashboard)
$stmtOrders = $pdo->prepare("
    SELECT o.id, o.total_amount, o.status, o.created_at, 
           (SELECT p.image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = o.id LIMIT 1) as first_product_image
    FROM orders o 
    WHERE o.user_id = ? 
    ORDER BY o.created_at DESC 
    LIMIT 5
");
$stmtOrders->execute([$_SESSION['user_id']]);
$orders = $stmtOrders->fetchAll();

// Fetch recent appointments (limit 5)
$stmtAppointments = $pdo->prepare("
    SELECT b.id, b.booking_date, b.booking_time, b.status, s.name as service_name, s.price, s.duration_mins 
    FROM bookings b 
    JOIN services s ON b.service_id = s.id 
    WHERE b.user_id = ? 
    ORDER BY b.booking_date DESC, b.booking_time DESC 
    LIMIT 5
");
$stmtAppointments->execute([$_SESSION['user_id']]);
$appointments = $stmtAppointments->fetchAll();

// Fetch recent reviews (limit 5)
$stmtReviews = $pdo->prepare("
    SELECT r.*, 
        CASE WHEN r.review_type = 'Product' THEN p.name ELSE s.name END as target_name,
        CASE WHEN r.review_type = 'Product' THEN p.image ELSE s.image END as target_image
    FROM reviews r
    LEFT JOIN products p ON r.review_type = 'Product' AND r.reference_id = p.id
    LEFT JOIN services s ON r.review_type = 'Service' AND r.reference_id = s.id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
    LIMIT 5
");
$stmtReviews->execute([$_SESSION['user_id']]);
$reviews = $stmtReviews->fetchAll();

require_once '../partials/header.php';
?>

<!-- Dashboard Wrapper -->
<div class="bg-gray-50 min-h-[calc(100vh-80px)] py-12">
    <div class="max-w-7xl mx-auto px-6">
        


        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <!-- LEFT COLUMN: Profile & Settings -->
            <div class="lg:col-span-4 space-y-8 flex-shrink-0">
                
                <!-- Profile Card -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 text-center relative overflow-hidden">
                    <div class="w-full h-24 bg-primary/10 absolute top-0 left-0"></div>
                    
                    <!-- Profile Card Settings Dropdown -->
                    <div class="absolute top-6 left-6 z-20 text-left">
                        <button id="cardSettingsBtn" class="text-primary hover:bg-white bg-white/70 backdrop-blur-md w-10 h-10 rounded-full flex items-center justify-center transition-colors focus:outline-none shadow-[0_2px_10px_rgba(67,57,242,0.15)] ring-1 ring-primary/10">
                            <i class="fas fa-sliders-h text-lg"></i>
                        </button>
                        
                        <div id="cardSettingsDropdown" class="absolute left-0 top-12 w-[240px] bg-white rounded-2xl shadow-[0_12px_40px_rgba(0,0,0,0.12)] border border-gray-100 py-3 opacity-0 invisible -translate-y-2 transition-all duration-200 origin-top-left">
                            
                            <!-- Picture Field (Hidden Form) -->
                            <form id="profilePicForm" enctype="multipart/form-data" class="m-0">
                                <label class="cursor-pointer flex items-center gap-3 px-6 py-3.5 hover:bg-[#EEF0FF] transition-colors relative group">
                                    <input type="file" name="profile_image" id="profileImageInput" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" accept="image/jpeg, image/png, image/webp">
                                    <i class="fas fa-camera fa-fw text-primary text-[17px] group-hover:text-primary-dark transition-colors"></i>
                                    <span class="text-[15px] font-semibold text-gray-800 pointer-events-none" id="uploadBtnText">Change Picture</span>
                                </label>
                            </form>
                            
                            <!-- Password Change -->
                            <a href="../customer/profile.php" class="flex items-center gap-3 px-6 py-3.5 hover:bg-[#EEF0FF] transition-colors group">
                                <i class="fas fa-lock fa-fw text-primary text-[17px] group-hover:text-primary-dark transition-colors"></i>
                                <span class="text-[15px] font-semibold text-gray-800">Change Password</span>
                            </a>
                            
                            <hr class="border-gray-100 mx-6">
                            
                            <!-- Logout -->
                            <a href="<?= SITE_URL ?>/api/logout.php" class="flex items-center gap-3 px-6 py-3.5 hover:bg-red-50 transition-colors group text-red-600">
                                <i class="fas fa-sign-out-alt fa-fw text-[17px] group-hover:text-red-700 transition-colors"></i>
                                <span class="text-[15px] font-semibold text-gray-800">Logout</span>
                            </a>
                        </div>
                    </div>

                    <!-- Profile Image -->
                    <div class="relative w-32 h-32 mx-auto mt-6 mb-4">
                        <div class="w-full h-full rounded-full border-4 border-white overflow-hidden bg-gray-100 shadow-sm">
                            <?php if ($user['profile_image']): ?>
                                <img src="<?= SITE_URL ?>/assets/images/<?= sanitize($user['profile_image']) ?>" alt="Profile" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center bg-primary text-white text-4xl font-bold">
                                    <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- User Details -->
                    <h2 class="text-2xl font-extrabold text-gray-900 mb-1"><?= sanitize($user['full_name']) ?></h2>
                    <p class="text-[15px] font-semibold text-primary mb-2">@<?= sanitize($user['username']) ?></p>
                    <p class="text-[15px] text-gray-500 flex items-center justify-center gap-2 mb-2">
                        <i class="fas fa-envelope text-gray-400"></i> <?= sanitize($user['email']) ?>
                    </p>
                </div>

                <!-- Address Hub -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col relative h-[300px]">
                    <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="font-bold text-gray-900 flex items-center gap-2">
                            <i class="fas fa-map-marker-alt text-primary"></i> Address Hub
                        </h3>
                    </div>
                    
                    <div class="flex border-b border-gray-100">
                        <button class="flex-1 py-3 text-sm font-semibold text-primary border-b-2 border-primary bg-primary/5 transition-colors" id="tabHome">
                            <i class="fas fa-home opacity-70 mr-1"></i> Home
                        </button>
                        <button class="flex-1 py-3 text-sm font-semibold text-gray-500 border-b-2 border-transparent hover:text-gray-700 transition-colors" id="tabWork">
                            <i class="fas fa-building opacity-70 mr-1"></i> Work
                        </button>
                    </div>

                    <div class="p-6 flex-1 flex flex-col bg-gray-50/50" id="addressContainer">
                        <div class="flex-1">
                            <?php if(!empty($user['home_address'])): ?>
                                <p class="text-gray-600 text-sm leading-relaxed whitespace-pre-line"><?= sanitize($user['home_address']) ?></p>
                            <?php else: ?>
                                <p class="text-gray-400 text-sm italic">No home address set.</p>
                            <?php endif; ?>
                        </div>
                        <button onclick="editAddress('home')" class="w-full mt-4 py-2 bg-white border border-gray-200 hover:border-primary text-gray-700 hover:text-primary text-sm font-semibold rounded-xl transition-colors">
                            Update Home Address
                        </button>
                    </div>
                </div>

            </div>

            <!-- RIGHT COLUMN: Main Content -->
            <div class="lg:col-span-8 flex flex-col gap-8">
                
                <!-- Appointment History -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 md:p-8">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-900 leading-none">
                            <i class="far fa-calendar-check text-primary mr-2"></i>Appointments
                        </h2>
                        <a href="../customer/my-bookings.php" class="text-primary hover:text-primary-dark text-sm font-semibold flex items-center gap-1">
                            View All <i class="fas fa-chevron-right text-[10px] mt-0.5"></i>
                        </a>
                    </div>

                    <?php if(empty($appointments)): ?>
                        <div class="bg-gray-50 rounded-xl p-8 text-center border border-dashed border-gray-200">
                            <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-gray-400 mx-auto mb-3 shadow-sm">
                                <i class="fas fa-calendar-alt text-xl"></i>
                            </div>
                            <h3 class="text-gray-900 font-bold mb-1">No appointments yet</h3>
                            <p class="text-gray-500 text-sm mb-4">Book a service to see it here.</p>
                            <a href="../booking/services.php" class="inline-block bg-primary text-white px-5 py-2 rounded-lg text-sm font-semibold hover:bg-primary-dark transition-colors">Book Now</a>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach($appointments as $apt): ?>
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 rounded-xl border border-gray-100 hover:border-primary/30 hover:shadow-sm transition-all bg-white group gap-4">
                                    <div class="flex items-start sm:items-center gap-4">
                                        <div class="w-14 h-14 bg-primary/10 text-primary rounded-xl flex flex-col items-center justify-center flex-shrink-0">
                                            <span class="text-xs font-bold leading-none mb-1"><?= date('M', strtotime($apt['booking_date'])) ?></span>
                                            <span class="text-lg font-extrabold leading-none"><?= date('d', strtotime($apt['booking_date'])) ?></span>
                                        </div>
                                        <div>
                                            <h3 class="font-bold text-gray-900 mb-1 group-hover:text-primary transition-colors"><?= sanitize($apt['service_name']) ?></h3>
                                            <div class="flex flex-wrap items-center gap-2 sm:gap-4 text-xs text-gray-500 font-medium">
                                                <span class="flex items-center gap-1.5"><i class="far fa-clock"></i> <?= date('h:i A', strtotime($apt['booking_time'])) ?></span>
                                                <span class="hidden sm:inline text-gray-300">•</span>
                                                <span class="flex items-center gap-1.5 font-bold text-gray-900"><?= formatPrice($apt['price']) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center justify-between sm:justify-end gap-4 w-full sm:w-auto mt-2 sm:mt-0 pt-3 sm:pt-0 border-t border-gray-100 sm:border-0">
                                        <?php 
                                            // Make badges clean
                                            $badgeClass = 'bg-gray-100 text-gray-600';
                                            if ($apt['status'] === 'Pending') $badgeClass = 'bg-yellow-50 text-yellow-700 border border-yellow-200';
                                            if ($apt['status'] === 'Confirmed') $badgeClass = 'bg-blue-50 text-blue-700 border border-blue-200';
                                            if ($apt['status'] === 'Completed') $badgeClass = 'bg-green-50 text-green-700 border border-green-200';
                                            if ($apt['status'] === 'Cancelled' || $apt['status'] === 'No Show') $badgeClass = 'bg-red-50 text-red-700 border border-red-200';
                                        ?>
                                        <span class="px-3 py-1 text-[11px] font-bold uppercase tracking-wider rounded-md <?= $badgeClass ?>"><?= $apt['status'] ?></span>
                                        
                                        <a href="booking-details.php?id=<?= $apt['id'] ?>" class="text-gray-400 hover:text-primary transition-colors bg-gray-50 hover:bg-primary/10 w-8 h-8 rounded-full flex items-center justify-center">
                                            <i class="fas fa-arrow-right text-xs"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Order History -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 md:p-8">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-900 leading-none">
                            <i class="fas fa-shopping-bag text-primary mr-2"></i>Orders
                        </h2>
                        <a href="../customer/my-orders.php" class="text-primary hover:text-primary-dark text-sm font-semibold flex items-center gap-1">
                            View All <i class="fas fa-chevron-right text-[10px] mt-0.5"></i>
                        </a>
                    </div>

                    <?php if(empty($orders)): ?>
                        <div class="bg-gray-50 rounded-xl p-8 text-center border border-dashed border-gray-200">
                            <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-gray-400 mx-auto mb-3 shadow-sm">
                                <i class="fas fa-box-open text-xl"></i>
                            </div>
                            <h3 class="text-gray-900 font-bold mb-1">No orders yet</h3>
                            <p class="text-gray-500 text-sm mb-4">Start shopping for your favorite products.</p>
                            <a href="../shop/products.php" class="inline-block bg-primary text-white px-5 py-2 rounded-lg text-sm font-semibold hover:bg-primary-dark transition-colors">Shop Now</a>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php foreach($orders as $order): ?>
                                <div class="flex items-center gap-4 p-4 rounded-xl border border-gray-100 hover:border-primary/30 hover:shadow-sm transition-all bg-white group">
                                    <div class="w-16 h-16 bg-gray-50 rounded-lg flex items-center justify-center p-2 border border-gray-100 flex-shrink-0">
                                        <?php if ($order['first_product_image']): ?>
                                            <img src="<?= SITE_URL ?>/assets/images/<?= sanitize($order['first_product_image']) ?>" class="w-full h-full object-contain" alt="Product" onerror="this.src='https://via.placeholder.com/60?text=Order'">
                                        <?php else: ?>
                                            <i class="fas fa-box text-gray-300 text-2xl"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex justify-between items-start mb-1">
                                            <h3 class="font-bold text-gray-900 truncate">Order #<?= $order['id'] ?></h3>
                                            <p class="font-bold text-primary ml-2"><?= formatPrice($order['total_amount']) ?></p>
                                        </div>
                                        <div class="flex justify-between items-center text-xs">
                                            <span class="text-gray-500"><?= date('M j, Y', strtotime($order['created_at'])) ?></span>
                                            <?php 
                                                $badgeClass = 'text-gray-500';
                                                if ($order['status'] === 'Pending' || $order['status'] === 'Processing') $badgeClass = 'text-yellow-600 font-semibold';
                                                if ($order['status'] === 'Shipped') $badgeClass = 'text-blue-600 font-semibold';
                                                if ($order['status'] === 'Delivered') $badgeClass = 'text-green-600 font-semibold';
                                                if ($order['status'] === 'Cancelled') $badgeClass = 'text-red-500 font-semibold';
                                            ?>
                                            <span class="<?= $badgeClass ?> flex items-center gap-1">
                                                <?php if($order['status'] === 'Delivered'): ?><i class="fas fa-check-circle"></i><?php endif; ?>
                                                <?= $order['status'] ?>
                                            </span>
                                        </div>
                                    </div>
                                    <a href="order-details.php?id=<?= $order['id'] ?>" class="text-gray-300 hover:text-primary transition-colors pl-2">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- My Reviews -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 md:p-8">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-900 leading-none">
                            <i class="far fa-star text-primary mr-2"></i>My Reviews
                        </h2>
                        <a href="reviews.php?user=<?= $user['id'] ?>" class="text-primary hover:text-primary-dark text-sm font-semibold flex items-center gap-1">
                            View All <i class="fas fa-chevron-right text-[10px] mt-0.5"></i>
                        </a>
                    </div>

                    <?php if(empty($reviews)): ?>
                        <div class="bg-gray-50 rounded-xl p-8 text-center border border-dashed border-gray-200">
                            <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-gray-400 mx-auto mb-3 shadow-sm">
                                <i class="far fa-comment-alt text-xl"></i>
                            </div>
                            <h3 class="text-gray-900 font-bold mb-1">No reviews written</h3>
                            <p class="text-gray-500 text-sm mb-4">Share your experience with products or services.</p>
                            <a href="../shop/products.php" class="inline-block bg-primary text-white px-5 py-2 rounded-lg text-sm font-semibold hover:bg-primary-dark transition-colors">Start Reviewing</a>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach($reviews as $review): ?>
                                <div class="p-5 rounded-xl border border-gray-100 hover:border-primary/30 hover:shadow-sm transition-all bg-white group">
                                    <div class="flex gap-4">
                                        <div class="w-12 h-12 bg-gray-50 rounded-lg flex items-center justify-center flex-shrink-0 border border-gray-100">
                                            <?php if ($review['target_image']): ?>
                                                <img src="<?= SITE_URL ?>/assets/images/<?= sanitize($review['target_image']) ?>" class="w-10 h-10 object-contain rounded" alt="Item">
                                            <?php else: ?>
                                                <i class="fas <?= $review['review_type'] === 'Product' ? 'fa-box' : 'fa-spa' ?> text-gray-400"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start mb-1 gap-1">
                                                <h3 class="font-bold text-gray-900 truncate">
                                                    <?= sanitize($review['target_name'] ?? 'Unknown Item') ?>
                                                    <span class="ml-2 text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 bg-gray-100 text-gray-500 rounded align-middle"><?= $review['review_type'] ?></span>
                                                </h3>
                                                <div class="flex items-center text-yellow-400 text-xs">
                                                    <?php for($i=1; $i<=5; $i++): ?>
                                                        <i class="<?= $i <= $review['rating'] ? 'fas' : 'far' ?> fa-star"></i>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>
                                            <div class="text-[11px] text-gray-400 mb-2 font-medium">
                                                <?= date('F j, Y', strtotime($review['created_at'])) ?>
                                            </div>
                                            <p class="text-sm text-gray-600 line-clamp-2 leading-relaxed">
                                                "<?= sanitize($review['comment']) ?>"
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
// Mock JS data for tab switching
const addressData = {
    home: `<?= !empty($user['home_address']) ? sanitize(str_replace("\n", "\\n", $user['home_address'])) : '' ?>`,
    work: `<?= !empty($user['work_address']) ? sanitize(str_replace("\n", "\\n", $user['work_address'])) : '' ?>`
};

const tabHome = document.getElementById('tabHome');
const tabWork = document.getElementById('tabWork');
const addressContainer = document.getElementById('addressContainer');

function updateAddressView(type) {
    // Styling tabs
    if(type === 'home') {
        tabHome.className = "flex-1 py-3 text-sm font-semibold text-primary border-b-2 border-primary bg-primary/5 transition-colors";
        tabHome.innerHTML = '<i class="fas fa-home opacity-70 mr-1"></i> Home';
        tabWork.className = "flex-1 py-3 text-sm font-semibold text-gray-500 border-b-2 border-transparent hover:text-gray-700 transition-colors";
        tabWork.innerHTML = '<i class="fas fa-building opacity-70 mr-1"></i> Work';
    } else {
        tabWork.className = "flex-1 py-3 text-sm font-semibold text-primary border-b-2 border-primary bg-primary/5 transition-colors";
        tabWork.innerHTML = '<i class="fas fa-building opacity-70 mr-1"></i> Work';
        tabHome.className = "flex-1 py-3 text-sm font-semibold text-gray-500 border-b-2 border-transparent hover:text-gray-700 transition-colors";
        tabHome.innerHTML = '<i class="fas fa-home opacity-70 mr-1"></i> Home';
    }

    // Render content
    const title = type === 'home' ? 'Home' : 'Work';
    const addressText = addressData[type] 
        ? `<p class="text-gray-600 text-sm leading-relaxed whitespace-pre-line">${addressData[type].replace(/\n/g, '<br>')}</p>` 
        : `<p class="text-gray-400 text-sm italic">No ${type} address set.</p>`;

    addressContainer.innerHTML = `
        <div class="flex-1">
            ${addressText}
        </div>
        <button onclick="editAddress('${type}')" class="w-full mt-4 py-2 bg-white border border-gray-200 hover:border-primary text-gray-700 hover:text-primary text-sm font-semibold rounded-xl transition-colors">
            Update ${title} Address
        </button>
    `;
}

tabHome.addEventListener('click', () => updateAddressView('home'));
tabWork.addEventListener('click', () => updateAddressView('work'));

async function editAddress(type) {
    const title = type === 'home' ? 'Home' : 'Work';
    const currentAddress = addressData[type].replace(/<br>/g, '\n');
    const newAddress = prompt(`Enter new ${title} Address:`, currentAddress);
    
    if(newAddress !== null && newAddress.trim() !== currentAddress.trim()) {
        try {
            const response = await fetch('<?= SITE_URL ?>/api/update-address.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ type: type, address: newAddress })
            });
            const data = await response.json();
            
            if(data.success) {
                // Update local JS state and re-render
                addressData[type] = newAddress;
                updateAddressView(type);
                // Optional UI toast could go here
            } else {
                alert('Failed to update address: ' + (data.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error updating address:', error);
            alert('An error occurred while updating the address.');
        }
    }
}

const profileInput = document.getElementById('profileImageInput');
if (profileInput) {
    profileInput.addEventListener('change', async function() {
        if (!this.files.length) return;
        
        const btnText = document.getElementById('uploadBtnText');
        const originalText = btnText.innerHTML;
        btnText.innerHTML = 'Uploading...';
        
        const formData = new FormData(document.getElementById('profilePicForm'));
        try {
            const response = await fetch('<?= SITE_URL ?>/api/update-profile-pic.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                location.reload();
            } else {
                alert('Upload failed: ' + (data.message || 'Unknown error'));
                btnText.innerHTML = originalText;
            }
        } catch (error) {
            console.error('Upload Error:', error);
            alert('An error occurred while uploading. Please check connection and try again.');
            btnText.innerHTML = originalText;
        }
    });
}

// Settings Dropdown Logic
const settingsBtn = document.getElementById('cardSettingsBtn');
const settingsDrop = document.getElementById('cardSettingsDropdown');
if (settingsBtn && settingsDrop) {
    settingsBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        settingsDrop.classList.toggle('opacity-0');
        settingsDrop.classList.toggle('invisible');
        settingsDrop.classList.toggle('-translate-y-2');
    });
    
    document.addEventListener('click', (e) => {
        if (!settingsDrop.contains(e.target) && !settingsBtn.contains(e.target)) {
            settingsDrop.classList.add('opacity-0', 'invisible', '-translate-y-2');
        }
    });
}
</script>

<?php require_once '../partials/footer.php'; ?>
