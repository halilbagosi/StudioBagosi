<?php
require_once 'auth_check.php';
require_once '../config/database.php';
require_once '../config/email_notifications.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test_email = $_POST['test_email'] ?? '';
    $test_booking_id = $_POST['test_booking_id'] ?? '';
    
    if (!empty($test_email) && filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
        // Send a test email
        $subject = 'Test Email - Studio Bagosi Notification System';
        $body = "
Përshëndetje,

Ky është një email test nga sistemi i njoftimeve të Studio Bagosi.

Nëse po merrni këtë email, do të thotë se sistemi i njoftimeve funksionon si duhet!

Me respekt,
Ekipi i Studio Bagosi

---
Studio Bagosi - Filmime/Fotografi Martesore
Facebook: https://www.facebook.com/share/1AWXLV8FWh/?mibextid=wwXIfr
Instagram: https://www.instagram.com/studiobagosi
YouTube: https://youtube.com/@studiobagosi
        ";
        
        if (sendEmail($test_email, $subject, $body)) {
            $message = "Test email sent successfully to: " . htmlspecialchars($test_email);
            $message_type = 'success';
        } else {
            $message = "Failed to send test email to: " . htmlspecialchars($test_email);
            $message_type = 'danger';
        }
    }
    
    if (!empty($test_booking_id) && is_numeric($test_booking_id)) {
        // Test booking notification
        if (sendBookingStatusNotification($test_booking_id, 'confirmed')) {
            $message = "Test booking notification sent successfully for booking ID: " . htmlspecialchars($test_booking_id);
            $message_type = 'success';
        } else {
            $message = "Failed to send booking notification for booking ID: " . htmlspecialchars($test_booking_id);
            $message_type = 'danger';
        }
    }
}

// Get some recent bookings for testing
$stmt = $pdo->query("
    SELECT b.id, 
           COALESCE(u.full_name, 'Guest User') as full_name,
           COALESCE(u.email, SUBSTRING_INDEX(b.notes, 'contact: ', -1)) as contact,
           p.name as package_name,
           b.status
    FROM bookings b 
    LEFT JOIN users u ON b.user_id = u.id 
    JOIN packages p ON b.package_id = p.id 
    ORDER BY b.created_at DESC 
    LIMIT 10
");
$recent_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email System Test - Studio Bagosi</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container py-5">
        <h1 class="mb-4"><i class="fas fa-envelope-open-text"></i> Email System Test</h1>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title"><i class="fas fa-paper-plane"></i> Send Test Email</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="test_email" class="form-label">Test Email Address</label>
                                <input type="email" class="form-control" id="test_email" name="test_email" 
                                       placeholder="Enter email address to test" required>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Send Test Email
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title"><i class="fas fa-calendar-check"></i> Test Booking Notification</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="test_booking_id" class="form-label">Booking ID</label>
                                <select class="form-select" id="test_booking_id" name="test_booking_id" required>
                                    <option value="">Select a booking to test</option>
                                    <?php foreach ($recent_bookings as $booking): ?>
                                        <option value="<?php echo $booking['id']; ?>">
                                            ID: <?php echo $booking['id']; ?> - 
                                            <?php echo htmlspecialchars($booking['full_name']); ?> - 
                                            <?php echo htmlspecialchars($booking['package_name']); ?> 
                                            (<?php echo htmlspecialchars($booking['contact']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <p class="text-muted small">
                                <i class="fas fa-info-circle"></i> This will send a "confirmed" status notification to the booking's email.
                            </p>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check"></i> Send Test Booking Notification
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title"><i class="fas fa-info-circle"></i> Email System Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Email Configuration</h6>
                                <ul class="list-unstyled">
                                    <li><strong>From:</strong> Studio Bagosi &lt;noreply@studiobagosi.com&gt;</li>
                                    <li><strong>Reply-To:</strong> info@studiobagosi.com</li>
                                    <li><strong>Method:</strong> PHP mail() function</li>
                                    <li><strong>Encoding:</strong> UTF-8</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>Notification Triggers</h6>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check text-success"></i> Status changed to "confirmed"</li>
                                    <li><i class="fas fa-times text-danger"></i> Status changed to "cancelled"</li>
                                    <li><i class="fas fa-star text-info"></i> Status changed to "completed"</li>
                                    <li><i class="fas fa-clock text-warning"></i> No notification for "pending"</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-lightbulb"></i> 
                            <strong>Note:</strong> Make sure your server is configured to send emails. 
                            If emails are not being delivered, check your server's mail configuration and spam folders.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 