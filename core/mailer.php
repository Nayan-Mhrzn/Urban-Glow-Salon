<?php
/**
 * Mailer Helper - Urban Glow Salon
 * Centralized email sending using PHPMailer with SMTP.
 */

require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Send an email using the configured SMTP server.
 *
 * @param string $toEmail    Recipient email address
 * @param string $toName     Recipient name
 * @param string $subject    Email subject
 * @param string $htmlBody   HTML email body
 * @return bool              True on success, false on failure
 */
function sendMail($toEmail, $toName, $subject, $htmlBody) {
    $mail = new PHPMailer(true);

    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = SMTP_PORT;

        // Sender
        $mail->setFrom(SMTP_USER, SITE_NAME);
        $mail->addReplyTo(SMTP_USER, SITE_NAME);

        // Recipient
        $mail->addAddress($toEmail, $toName);

        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body    = wrapEmailTemplate($subject, $htmlBody);

        // Plain-text fallback
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log the error for debugging (visible in PHP error log)
        error_log("Urban Glow Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Wrap email content in a beautiful, branded HTML template.
 */
function wrapEmailTemplate($title, $bodyContent) {
    $siteUrl = SITE_URL;
    $siteName = SITE_NAME;

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
</head>
<body style="margin:0;padding:0;background-color:#f0f4f8;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f0f4f8;padding:40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">
                    <!-- Header -->
                    <tr>
                        <td style="background:linear-gradient(135deg,#20456c,#1a375e);padding:32px 40px;text-align:center;">
                            <h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:800;letter-spacing:0.5px;">{$siteName}</h1>
                            <p style="margin:6px 0 0;color:rgba(255,255,255,0.7);font-size:12px;letter-spacing:2px;text-transform:uppercase;font-weight:600;">Premium Grooming For All</p>
                        </td>
                    </tr>
                    <!-- Body -->
                    <tr>
                        <td style="padding:36px 40px;color:#333333;font-size:15px;line-height:1.7;">
                            {$bodyContent}
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#f8fafc;padding:24px 40px;text-align:center;border-top:1px solid #e5e7eb;">
                            <p style="margin:0 0 6px;color:#6b7280;font-size:13px;">Thank you for choosing {$siteName}!</p>
                            <p style="margin:0;color:#9ca3af;font-size:11px;">This is an automated email. Please do not reply directly.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
}

// =============================================
// PRE-BUILT EMAIL TEMPLATES
// =============================================

/**
 * Send a welcome email after registration.
 */
function sendWelcomeEmail($toEmail, $fullName) {
    $siteUrl = SITE_URL;
    $body = <<<HTML
        <h2 style="margin:0 0 16px;color:#1a375e;font-size:20px;">Welcome, {$fullName}! 🎉</h2>
        <p>Your account at <strong>Urban Glow Salon</strong> has been created successfully.</p>
        <p>You can now:</p>
        <ul style="padding-left:20px;color:#4b5563;">
            <li>Browse and book our premium salon services</li>
            <li>Shop our curated collection of grooming products</li>
            <li>Track your appointments and orders</li>
            <li>Leave reviews and earn recommendations</li>
        </ul>
        <div style="text-align:center;margin:28px 0;">
            <a href="{$siteUrl}/index.php" style="display:inline-block;background:#20456c;color:#ffffff;text-decoration:none;padding:14px 36px;border-radius:12px;font-weight:700;font-size:15px;">
                Explore Now
            </a>
        </div>
        <p style="color:#9ca3af;font-size:13px;">If you did not create this account, please ignore this email.</p>
HTML;
    return sendMail($toEmail, $fullName, 'Welcome to Urban Glow Salon!', $body);
}

/**
 * Send a password reset email.
 */
function sendPasswordResetEmail($toEmail, $fullName, $resetLink) {
    $body = <<<HTML
        <h2 style="margin:0 0 16px;color:#1a375e;font-size:20px;">Password Reset Request</h2>
        <p>Hi <strong>{$fullName}</strong>,</p>
        <p>We received a request to reset your password. Click the button below to create a new password:</p>
        <div style="text-align:center;margin:28px 0;">
            <a href="{$resetLink}" style="display:inline-block;background:#4339F2;color:#ffffff;text-decoration:none;padding:14px 36px;border-radius:12px;font-weight:700;font-size:15px;">
                Reset My Password
            </a>
        </div>
        <p style="color:#6b7280;font-size:13px;">This link will expire in <strong>1 hour</strong>.</p>
        <p style="color:#9ca3af;font-size:13px;">If you didn't request this, you can safely ignore this email. Your password will remain unchanged.</p>
HTML;
    return sendMail($toEmail, $fullName, 'Reset Your Password - Urban Glow Salon', $body);
}

/**
 * Send a simple 6-digit password reset code email.
 */
function sendPasswordResetCodeEmail($toEmail, $fullName, $code) {
    $body = <<<HTML
        <h2 style="margin:0 0 16px;color:#1a375e;font-size:20px;">Your Verification Code</h2>
        <p>Hi <strong>{$fullName}</strong>,</p>
        <p>We received a request to reset your password. Please enter the verification code below to proceed:</p>
        <div style="text-align:center;margin:28px 0;">
            <div style="display:inline-block;background:#f3f4f6;color:#1a375e;padding:16px 36px;border-radius:12px;font-weight:800;font-size:32px;letter-spacing:8px;border:2px dashed #9ca3af;">
                {$code}
            </div>
        </div>
        <p style="color:#6b7280;font-size:13px;">This code will expire in <strong>15 minutes</strong>.</p>
        <p style="color:#9ca3af;font-size:13px;">If you didn't request this, you can safely ignore this email. Your password will remain unchanged.</p>
HTML;
    return sendMail($toEmail, $fullName, 'Your Verification Code - Urban Glow Salon', $body);
}

/**
 * Send an order confirmation email.
 */
function sendOrderConfirmationEmail($toEmail, $fullName, $orderId, $orderItems, $totalAmount, $paymentMethod, $shippingAddress) {
    $siteUrl = SITE_URL;
    $currencySymbol = CURRENCY_SYMBOL;

    // Build order items table rows
    $itemsHtml = '';
    foreach ($orderItems as $item) {
        $itemTotal = number_format($item['price_at_purchase'] * $item['quantity'], 0);
        $itemsHtml .= <<<HTML
        <tr>
            <td style="padding:10px 12px;border-bottom:1px solid #f3f4f6;font-size:14px;color:#374151;">{$item['name']}</td>
            <td style="padding:10px 12px;border-bottom:1px solid #f3f4f6;font-size:14px;color:#6b7280;text-align:center;">{$item['quantity']}</td>
            <td style="padding:10px 12px;border-bottom:1px solid #f3f4f6;font-size:14px;color:#374151;text-align:right;font-weight:600;">{$currencySymbol} {$itemTotal}</td>
        </tr>
HTML;
    }

    $formattedTotal = number_format($totalAmount, 0);
    $body = <<<HTML
        <h2 style="margin:0 0 16px;color:#1a375e;font-size:20px;">Order Confirmed! 🛍️</h2>
        <p>Hi <strong>{$fullName}</strong>,</p>
        <p>Thank you for your order! Here's a summary:</p>
        
        <div style="background:#f8fafc;border-radius:12px;padding:20px;margin:20px 0;">
            <table style="width:100%;font-size:13px;color:#6b7280;margin-bottom:8px;">
                <tr>
                    <td><strong>Order #</strong></td>
                    <td style="text-align:right;font-weight:700;color:#1a375e;">{$orderId}</td>
                </tr>
                <tr>
                    <td><strong>Payment</strong></td>
                    <td style="text-align:right;">{$paymentMethod}</td>
                </tr>
            </table>
        </div>

        <!-- Items Table -->
        <table width="100%" cellpadding="0" cellspacing="0" style="margin:16px 0;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
            <thead>
                <tr style="background:#f9fafb;">
                    <th style="padding:10px 12px;text-align:left;font-size:12px;color:#6b7280;text-transform:uppercase;font-weight:700;">Product</th>
                    <th style="padding:10px 12px;text-align:center;font-size:12px;color:#6b7280;text-transform:uppercase;font-weight:700;">Qty</th>
                    <th style="padding:10px 12px;text-align:right;font-size:12px;color:#6b7280;text-transform:uppercase;font-weight:700;">Price</th>
                </tr>
            </thead>
            <tbody>
                {$itemsHtml}
            </tbody>
            <tfoot>
                <tr style="background:#f0f4f8;">
                    <td colspan="2" style="padding:12px;font-size:15px;font-weight:800;color:#1a375e;">Total</td>
                    <td style="padding:12px;text-align:right;font-size:15px;font-weight:800;color:#4339F2;">{$currencySymbol} {$formattedTotal}</td>
                </tr>
            </tfoot>
        </table>

        <div style="background:#f8fafc;border-radius:12px;padding:16px;margin:16px 0;">
            <p style="margin:0;font-size:13px;color:#6b7280;"><strong>Shipping Address:</strong></p>
            <p style="margin:4px 0 0;font-size:14px;color:#374151;">{$shippingAddress}</p>
        </div>

        <div style="text-align:center;margin:28px 0;">
            <a href="{$siteUrl}/customer/order-details.php?id={$orderId}" style="display:inline-block;background:#20456c;color:#ffffff;text-decoration:none;padding:14px 36px;border-radius:12px;font-weight:700;font-size:15px;">
                View Order Details
            </a>
        </div>
HTML;
    return sendMail($toEmail, $fullName, "Order #{$orderId} Confirmed - Urban Glow Salon", $body);
}

/**
 * Send a booking (appointment) confirmation email.
 */
function sendBookingConfirmationEmail($toEmail, $fullName, $bookingId, $serviceName, $bookingDate, $bookingTime, $staffName, $servicePrice) {
    $siteUrl = SITE_URL;
    $currencySymbol = CURRENCY_SYMBOL;

    $formattedDate = date('l, F j, Y', strtotime($bookingDate));
    $formattedTime = date('h:i A', strtotime($bookingTime));
    $formattedPrice = number_format($servicePrice, 0);

    $body = <<<HTML
        <h2 style="margin:0 0 16px;color:#1a375e;font-size:20px;">Booking Confirmed! ✨</h2>
        <p>Hi <strong>{$fullName}</strong>,</p>
        <p>Your appointment has been confirmed. Here are the details:</p>
        
        <div style="background:#f8fafc;border-radius:12px;padding:24px;margin:20px 0;border-left:4px solid #4339F2;">
            <table style="width:100%;font-size:14px;color:#374151;" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="padding:8px 0;color:#6b7280;font-weight:600;width:130px;">Booking ID</td>
                    <td style="padding:8px 0;font-weight:700;color:#1a375e;">#{$bookingId}</td>
                </tr>
                <tr>
                    <td style="padding:8px 0;color:#6b7280;font-weight:600;">Service</td>
                    <td style="padding:8px 0;font-weight:600;">{$serviceName}</td>
                </tr>
                <tr>
                    <td style="padding:8px 0;color:#6b7280;font-weight:600;">Date</td>
                    <td style="padding:8px 0;">{$formattedDate}</td>
                </tr>
                <tr>
                    <td style="padding:8px 0;color:#6b7280;font-weight:600;">Time</td>
                    <td style="padding:8px 0;font-weight:600;color:#4339F2;">{$formattedTime}</td>
                </tr>
                <tr>
                    <td style="padding:8px 0;color:#6b7280;font-weight:600;">Specialist</td>
                    <td style="padding:8px 0;">{$staffName}</td>
                </tr>
                <tr>
                    <td style="padding:8px 0;color:#6b7280;font-weight:600;">Price</td>
                    <td style="padding:8px 0;font-weight:700;color:#1a375e;">{$currencySymbol} {$formattedPrice}</td>
                </tr>
            </table>
        </div>

        <div style="text-align:center;margin:28px 0;">
            <a href="{$siteUrl}/customer/my-bookings.php" style="display:inline-block;background:#20456c;color:#ffffff;text-decoration:none;padding:14px 36px;border-radius:12px;font-weight:700;font-size:15px;">
                View My Bookings
            </a>
        </div>

        <p style="color:#6b7280;font-size:13px;">Please arrive 5-10 minutes before your scheduled time. If you need to cancel or reschedule, visit the My Bookings section in your dashboard.</p>
HTML;
    return sendMail($toEmail, $fullName, "Appointment Confirmed - #{$bookingId} - Urban Glow Salon", $body);
}
