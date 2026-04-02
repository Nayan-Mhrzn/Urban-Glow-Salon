# Urban Glow Salon - Project Documentation

## 1. Project Overview
**Urban Glow Salon** is a comprehensive, hybrid web application that seamlessly merges an intelligent service-booking engine with a modern e-commerce storefront. Designed for a premium grooming and salon business, the platform provides dedicated interfaces for Customers, Staff, and Administrators.

## 2. Technology Stack & Methodology
*   **Methodology:** Structured (Procedural) Programming
*   **Backend Language:** PHP (v8+)
*   **Database:** MySQL (via PDO for Object-Oriented secure querying)
*   **Frontend Styling:** HTML5, modern Vanilla CSS, and **Tailwind CSS** (for responsive, utility-first UI design)
*   **Frontend Interactivity:** Vanilla JavaScript
*   **Server Environment:** WAMP Server (Windows, Apache, MySQL, PHP)

## 3. Core Modules & Features

### A. Customer Portal
*   **Secure Authentication:** Registration and login system using Bcrypt cryptographic hashing for passwords.
*   **Customer Dashboard:** A centralized, aesthetic hub where users can track active orders, view upcoming appointments, and manage profile settings.
*   **Profile Management:** Floating settings UI allowing users to dynamically upload profile pictures, update contact information, and securely change passwords.

### B. E-Commerce Shop
*   **Dynamic Product Catalog:** View products by categories or brands, featuring detailed product pages with image sliders.
*   **Smart Checkout System:** Features responsive cart layouts, real-time total calculations, and "Auto-fill" functionality allowing users to instantly snap between saved Home or Work addresses.
*   **Order Tracking:** Detailed, itemized order history breakdowns mapping directly to database invoices.

### C. Intelligent Appointment Booking
*   **Service Exploration:** Browse salon services categorized by Hair, Skin, Body, Makeup, and Nails.
*   **Adaptive Scheduling:** Users can book specific dates and times, interacting directly with the custom scoring engine to find optimal slots.
*   **Booking Details UI:** Distinct tracking pages allowing customers to review their appointment times, selected staff, and special requests.

### D. Administrative Panel (`/admin/`)
*   **Analytics Dashboard:** Visual charts and metrics tracking revenue, total bookings, and top-selling products.
*   **Entity Management:** Complete CRUD (Create, Read, Update, Delete) control over Users, Products, Services, and Bookings.
*   **System Settings:** Dynamic configuration table allowing admins to modify store hours, contact info, and payment gateways without touching code.

### E. Staff Panel (`/staff/`)
*   **Role-Based Routing:** Staff members log in through the main portal but are automatically redirected via backend role detection to their private dashboards.
*   **Schedule Management:** Allows barbers/stylists to exclusively track and manage the appointments assigned to them.

## 4. Custom Algorithms & Intelligence

The project distinguishes itself from basic CRUD applications by implementing several backend algorithms:

1.  **Adaptive Slot Recommendation Algorithm:** 
    *(File: `includes/slot_scorer.php`)*
    Dynamically scores and recommends booking times for a user instead of mapping raw availability. It calculates scores based on:
    *   *Historical Affinity:* Identifies a customer's past booking habits (e.g., favoring mornings).
    *   *Schedule Gap-Fill:* Heavily rewards time slots that eliminate awkward idle gaps in the staff's schedule, maximizing operational efficiency.
    *   *No-Show Penalty:* Evaluates a user's cancellation history and suppresses prime-time slots for high-risk users.

2.  **Tag-Based Jaccard Similarity Recommender:** 
    *(File: `includes/recommender.php`)*
    Powers the "Recommended For You" section in the shop. It cross-references the unique tags of every product a customer has bought against the tags of unpurchased products (Intersection-Over-Union) to generate personalized shopping suggestions. Features a randomized fallback array for brand-new users (Cold-Start management).

3.  **Dynamic Fuzzy-Match Cross-Listing Engine:** 
    *(File: `products.php`)*
    Replaces rigid static categories in the sidebar. When a user clicks "Hair Care", the engine maps the request to a dynamic multi-tag `LIKE` query (`tags LIKE '%hair%'`) rather than a strict column match. This allows hybrid products (e.g., a Hair & Beard Serum) to exist natively across multiple UI sections simultaneously.

## 5. Security Implementations
*   **CSRF Protection:** Custom token generation and validation (`verifyCSRFToken()`) on all state-changing forms to prevent Cross-Site Request Forgery.
*   **SQL Injection Prevention:** Strict use of PDO Prepared Statements for all database queries ensuring variables are never interpolated directly into SQL sequences.
*   **XSS Protection:** Implementation of `sanitize()` wrapper functions to rigorously encode HTML entities before echoing user-submitted data (like reviews or profile names) to the screen.
