<?php
/**
 * Email Notification System for Studio Bagosi
 * Handles sending emails to users when their booking status changes
 */

require_once 'database.php';

/**
 * Send email notification when booking status changes
 * @param int $booking_id - The booking ID
 * @param string $new_status - The new status
 * @param string $old_status - The previous status
 * @return bool - Success status
 */
function sendBookingStatusNotification($booking_id, $new_status, $old_status = null) {
    global $pdo;
    
    try {
        // Get booking details with user and package information
        $stmt = $pdo->prepare("
            SELECT b.*, 
                   u.full_name as user_name, 
                   u.email as user_email,
                   p.name as package_name,
                   p.price as package_price
            FROM bookings b 
            LEFT JOIN users u ON b.user_id = u.id 
            JOIN packages p ON b.package_id = p.id 
            WHERE b.id = ?
        ");
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$booking) {
            error_log("Booking not found: " . $booking_id);
            return false;
        }
        
        // Extract email for guest bookings
        $email = $booking['user_email'];
        $name = $booking['user_name'];
        
        // For guest bookings, extract email from notes
        if (!$email && $booking['notes']) {
            // Extract email from notes like "Guest booking via contact: email@example.com"
            if (preg_match('/contact:\s*([^\s]+@[^\s]+)/i', $booking['notes'], $matches)) {
                $contact_info = trim($matches[1]);
                if (filter_var($contact_info, FILTER_VALIDATE_EMAIL)) {
                    $email = $contact_info;
                    $name = 'Guest User';
                }
            }
        }
        
        if (!$email) {
            error_log("No email found for booking: " . $booking_id);
            return false;
        }
        
        // Only send notifications for status changes to confirmed, cancelled, or completed
        if (!in_array($new_status, ['confirmed', 'cancelled', 'completed'])) {
            return false;
        }
        
        // Skip if status hasn't actually changed
        if ($new_status === $old_status) {
            return false;
        }
        
        // Prepare email content based on status
        $subject = getEmailSubject($new_status);
        $body = getEmailBody($booking, $new_status, $name);
        
        // Send email
        return sendEmail($email, $subject, $body, $name);
        
    } catch (Exception $e) {
        error_log("Error sending booking notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Get email subject based on booking status
 */
function getEmailSubject($status) {
    switch ($status) {
        case 'confirmed':
            return 'Rezervimi juaj është konfirmuar - Studio Bagosi';
        case 'cancelled':
            return 'Rezervimi juaj është anulluar - Studio Bagosi';
        case 'completed':
            return 'Shërbimi juaj është përfunduar - Studio Bagosi';
        default:
            return 'Përditësim rezervimi - Studio Bagosi';
    }
}

/**
 * Get email body based on booking status
 */
function getEmailBody($booking, $status, $name) {
    $event_date = date('d/m/Y', strtotime($booking['event_date']));
    $event_time = date('H:i', strtotime($booking['event_date']));
    
    switch ($status) {
        case 'confirmed':
            return "
Përshëndetje $name,

Kemi kënaqësinë t'ju njoftojmë se rezervimi juaj është konfirmuar!

Detajet e rezervimit:
• Paketa: {$booking['package_name']}
• Data e eventit: $event_date
• Ora e eventit: $event_time
• Çmimi: €" . number_format($booking['package_price'], 2) . "

Do t'ju kontaktojmë së shpejti për të diskutuar detajet e mëtejshme dhe për t'ju përgatitur për ditën e veçantë.

Faleminderit që na zgjodhët!

Me respekt,
Ekipi i Studio Bagosi

---
Studio Bagosi - Filmime/Fotografi Martesore
Facebook: https://www.facebook.com/share/1AWXLV8FWh/?mibextid=wwXIfr
Instagram: https://www.instagram.com/studiobagosi
YouTube: https://youtube.com/@studiobagosi
            ";
            
        case 'cancelled':
            return "
Përshëndetje $name,

Na vjen keq t'ju njoftojmë se rezervimi juaj është anulluar.

Detajet e rezervimit të anulluar:
• Paketa: {$booking['package_name']}
• Data e eventit: $event_date
• Ora e eventit: $event_time

Nëse keni pyetje ose dëshironi të rezervoni përsëri, ju lutemi na kontaktoni.

Me respekt,
Ekipi i Studio Bagosi

---
Studio Bagosi - Filmime/Fotografi Martesore
Facebook: https://www.facebook.com/share/1AWXLV8FWh/?mibextid=wwXIfr
Instagram: https://www.instagram.com/studiobagosi
YouTube: https://youtube.com/@studiobagosi
            ";
            
        case 'completed':
            return "
Përshëndetje $name,

Faleminderit që na zgjodhët për eventin tuaj të veçantë!

Shërbimi për rezervimin tuaj është përfunduar me sukses:
• Paketa: {$booking['package_name']}
• Data e eventit: $event_date

Shpresojmë se jeni të kënaqur me shërbimet tona. Nëse keni ndonjë pyetje ose koment, ju lutemi na kontaktoni.

Do t'ju dërgojmë fotot/videot e përpunuara në kohën e caktuar.

Me respekt,
Ekipi i Studio Bagosi

---
Studio Bagosi - Filmime/Fotografi Martesore
Facebook: https://www.facebook.com/share/1AWXLV8FWh/?mibextid=wwXIfr
Instagram: https://www.instagram.com/studiobagosi
YouTube: https://youtube.com/@studiobagosi
            ";
            
        default:
            return "
Përshëndetje $name,

Rezervimi juaj ka një përditësim të ri.

Detajet e rezervimit:
• Paketa: {$booking['package_name']}
• Data e eventit: $event_date
• Ora e eventit: $event_time
• Statusi: " . ucfirst($status) . "

Nëse keni pyetje, ju lutemi na kontaktoni.

Me respekt,
Ekipi i Studio Bagosi
            ";
    }
}

/**
 * Send email using PHP's mail function
 */
function sendEmail($to, $subject, $body, $name = '') {
    // Email headers
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/plain;charset=UTF-8" . "\r\n";
    $headers .= "From: Studio Bagosi <noreply@studiobagosi.com>" . "\r\n";
    $headers .= "Reply-To: info@studiobagosi.com" . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    // Send email
    $result = mail($to, $subject, $body, $headers);
    
    if ($result) {
        error_log("Email sent successfully to: " . $to);
    } else {
        error_log("Failed to send email to: " . $to);
    }
    
    return $result;
}

/**
 * Get current booking status
 */
function getCurrentBookingStatus($booking_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT status FROM bookings WHERE id = ?");
        $stmt->execute([$booking_id]);
        return $stmt->fetchColumn();
    } catch (Exception $e) {
        error_log("Error getting booking status: " . $e->getMessage());
        return null;
    }
}
?> 