-- =============================================
-- Urban Glow Salon — Database Schema
-- =============================================

CREATE DATABASE IF NOT EXISTS urban_glow_salon;
USE urban_glow_salon;

-- =============================================
-- USERS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    role ENUM('CUSTOMER', 'ADMIN', 'STAFF') DEFAULT 'CUSTOMER',
    profile_image VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- SERVICES TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    duration_mins INT NOT NULL DEFAULT 30,
    category ENUM('Hair', 'Skin', 'Body', 'Makeup', 'Nails') NOT NULL,
    gender ENUM('Male', 'Female', 'Unisex') DEFAULT 'Unisex',
    image VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- BOOKINGS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    service_id INT NOT NULL,
    booking_date DATE NOT NULL,
    booking_time TIME NOT NULL,
    status ENUM('Pending', 'Confirmed', 'Completed', 'Cancelled') DEFAULT 'Pending',
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- PRODUCTS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    discount_price DECIMAL(10, 2) DEFAULT NULL,
    category ENUM('Hair Care', 'Beard Care', 'Skin Care', 'Color & Treatments') NOT NULL,
    brand VARCHAR(100),
    brand_logo VARCHAR(255),
    stock_quantity INT DEFAULT 0,
    image VARCHAR(255),
    tags VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- PRODUCT IMAGES TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- CART TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (user_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- ORDERS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled') DEFAULT 'Pending',
    payment_method VARCHAR(50) DEFAULT 'Cash on Delivery',
    shipping_address TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- ORDER ITEMS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price_at_purchase DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- REVIEWS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    review_type ENUM('Product', 'Service') NOT NULL,
    reference_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- DEFAULT ADMIN USER (password: admin123)
-- =============================================
INSERT INTO users (username, email, password, full_name, phone, role) VALUES
('admin', 'admin@urbanglowsalon.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', '9800000000', 'ADMIN');

-- =============================================
-- SAMPLE SERVICES
-- =============================================
INSERT INTO services (name, description, price, duration_mins, category, gender, image) VALUES
('Hair Cutting', 'Professional haircut with styling and finishing.', 350.00, 30, 'Hair', 'Unisex', 'services/hair-cutting.jpg'),
('Women''s Creative Haircut', 'Layer, step, or bob cut with blow-dry finish.', 1200.00, 60, 'Hair', 'Female', 'services/womens-haircut.jpg'),
('Global Hair Coloring', 'Full head hair coloring (Ammonia-free options available).', 4500.00, 120, 'Hair', 'Unisex', 'services/hair-coloring.jpg'),
('Beard Shaping & Trim', 'Razor line-up and beard trimming with hot towel.', 350.00, 30, 'Hair', 'Male', 'services/beard-trim.jpg'),
('Threading', 'Eyebrow and facial threading for clean shaping.', 150.00, 15, 'Skin', 'Unisex', 'services/threading.jpg'),
('Classic Facial', 'Deep cleansing facial with steam, scrub, and mask.', 800.00, 45, 'Skin', 'Unisex', 'services/facial.jpg'),
('Gold Facial', 'Premium gold facial for radiant, glowing skin.', 1500.00, 60, 'Skin', 'Unisex', 'services/gold-facial.jpg'),
('Bridal Makeup', 'Complete bridal makeup with hairstyling.', 15000.00, 180, 'Makeup', 'Female', 'services/bridal-makeup.jpg'),
('Classic Manicure', 'Nail shaping, cuticle care, hand massage, and polish.', 500.00, 30, 'Nails', 'Unisex', 'services/manicure.jpg'),
('Nail Extensions', 'Acrylic or gel nail extensions with design.', 2500.00, 90, 'Nails', 'Female', 'services/nail-extensions.jpg'),
('Head Massage', 'Relaxing head massage with essential oils.', 300.00, 20, 'Body', 'Unisex', 'services/head-massage.jpg'),
('Shoulder Massage', 'Targeted shoulder and neck massage.', 500.00, 30, 'Body', 'Unisex', 'services/shoulder-massage.jpg'),
('Full Body Massage', 'Complete relaxation full body massage.', 2000.00, 60, 'Body', 'Unisex', 'services/full-body-massage.jpg'),
('Party Makeup', 'Glamorous party makeup with hairstyling.', 5000.00, 90, 'Makeup', 'Female', 'services/party-makeup.jpg'),
('Pedicure', 'Complete foot care with scrub, massage, and polish.', 600.00, 40, 'Nails', 'Unisex', 'services/pedicure.jpg');

-- =============================================
-- SAMPLE PRODUCTS
-- =============================================
INSERT INTO products (name, description, price, discount_price, category, brand, stock_quantity, image, tags) VALUES
('Maleic Bond Repair Serum', 'A lightweight bond-repair serum designed to strengthen damaged hair.', 899.00, NULL, 'Hair Care', 'Olaplex', 25, 'products/bond-repair-serum.jpg', 'repair,serum,bond'),
('Anti Dandruff Shampoo', 'A gentle yet effective anti-dandruff shampoo that cleanses the scalp.', 1199.00, NULL, 'Hair Care', 'L''Oréal Professionnel', 30, 'products/anti-dandruff-shampoo.jpg', 'shampoo,dandruff,scalp'),
('Frizz Control Complex SP', 'A protective anti-frizz hair serum that smooths hair while shielding it.', 949.00, NULL, 'Hair Care', 'Kérastase', 20, 'products/frizz-control.jpg', 'frizz,serum,smooth'),
('Maleic Bond Repair Mask', 'An intensive bond-repair hair mask that deeply nourishes and strengthens.', 1599.00, NULL, 'Hair Care', 'Olaplex', 15, 'products/bond-repair-mask.jpg', 'mask,repair,bond'),
('Beard Growth Oil', 'Natural beard growth oil with essential oils for thicker, fuller beard.', 599.00, 499.00, 'Beard Care', 'Beardo', 40, 'products/beard-oil.jpg', 'beard,oil,growth'),
('Beard Wash', 'Gentle beard wash that cleanses without stripping natural oils.', 449.00, NULL, 'Beard Care', 'Ustraa', 35, 'products/beard-wash.jpg', 'beard,wash,clean'),
('Beard Styling Wax', 'Strong hold beard wax for perfect styling and shaping.', 399.00, NULL, 'Beard Care', 'Beardo', 28, 'products/beard-wax.jpg', 'beard,wax,styling'),
('Vitamin C Face Serum', 'Brightening vitamin C serum for glowing, even-toned skin.', 799.00, 699.00, 'Skin Care', 'L''Oréal Professionnel', 22, 'products/vitamin-c-serum.jpg', 'serum,vitamin-c,brightening'),
('Charcoal Face Wash', 'Deep cleansing charcoal face wash for oil control.', 349.00, NULL, 'Skin Care', 'Ustraa', 50, 'products/charcoal-face-wash.jpg', 'face-wash,charcoal,oil-control'),
('Sunscreen SPF 50', 'Lightweight sunscreen with SPF 50 for daily protection.', 549.00, NULL, 'Skin Care', 'Kérastase', 30, 'products/sunscreen.jpg', 'sunscreen,spf,protection'),
('Hair Color Cream', 'Ammonia-free permanent hair color with rich, vibrant shades.', 650.00, NULL, 'Color & Treatments', 'L''Oréal Professionnel', 18, 'products/hair-color.jpg', 'color,cream,ammonia-free'),
('Keratin Treatment Kit', 'Professional keratin smoothing treatment for silky straight hair.', 3500.00, 2999.00, 'Color & Treatments', 'Kérastase', 10, 'products/keratin-kit.jpg', 'keratin,treatment,smoothing');
