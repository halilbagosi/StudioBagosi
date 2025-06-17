<?php
require_once 'auth_check.php';
require_once '../config/database.php';
require_once '../config/email_notifications.php';

// Handle booking actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $booking_id = $_POST['booking_id'] ?? 0;
        $status = $_POST['status'] ?? '';
        $notes = $_POST['notes'] ?? '';
        
        if (!empty($booking_id) && !empty($status)) {
            // Get current status before updating
            $old_status = getCurrentBookingStatus($booking_id);
            
            // Update booking status and notes
            $stmt = $pdo->prepare("UPDATE bookings SET status = ?, notes = ? WHERE id = ?");
            $stmt->execute([$status, $notes, $booking_id]);
            
            // Send email notification if status changed
            if ($old_status && $old_status !== $status) {
                $email_sent = sendBookingStatusNotification($booking_id, $status, $old_status);
                if ($email_sent) {
                    header('Location: bookings.php?success=status_updated_email_sent');
                } else {
                    header('Location: bookings.php?success=status_updated_email_failed');
                }
            } else {
                header('Location: bookings.php?success=status_updated');
            }
            exit;
        }
    }
    
    if ($_POST['action'] === 'delete_booking') {
        $booking_id = $_POST['booking_id'] ?? 0;
        
        if (!empty($booking_id)) {
            $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
            $stmt->execute([$booking_id]);
            header('Location: bookings.php?success=booking_deleted');
            exit;
        }
    }
}

// Get all bookings with user and package details
$stmt = $pdo->query("
    SELECT b.*, 
           COALESCE(u.full_name, 'Guest User') as full_name,
           COALESCE(u.email, SUBSTRING_INDEX(b.notes, 'contact: ', -1)) as contact,
           p.name as package_name 
    FROM bookings b 
    LEFT JOIN users u ON b.user_id = u.id 
    JOIN packages p ON b.package_id = p.id 
    ORDER BY b.created_at DESC
");
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookings Management - Studio Bagosi</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container py-5">
        <h1 class="mb-4">Bookings Management</h1>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php
                switch ($_GET['success']) {
                    case 'status_updated':
                        echo "Booking status updated successfully!";
                        break;
                    case 'status_updated_email_sent':
                        echo "Booking status updated successfully and notification email sent to client!";
                        break;
                    case 'booking_deleted':
                        echo "Booking deleted successfully!";
                        break;
                }
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['success']) && $_GET['success'] === 'status_updated_email_failed'): ?>
            <div class="alert alert-warning">
                Booking status updated successfully, but failed to send notification email to client. Please contact them manually.
            </div>
        <?php endif; ?>

        <!-- Bookings Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Contact</th>
                                <th>Package</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($booking['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($booking['contact']); ?></td>
                                <td><?php echo htmlspecialchars($booking['package_name']); ?></td>
                                <td><?php echo date('d M Y', strtotime($booking['booking_date'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $booking['status'] === 'confirmed' ? 'success' : 
                                            ($booking['status'] === 'cancelled' ? 'danger' : 
                                             ($booking['status'] === 'completed' ? 'info' : 'warning')); 
                                    ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#viewBookingModal" 
                                                data-booking='<?php echo htmlspecialchars(json_encode($booking)); ?>'>
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editBookingModal" 
                                                data-booking='<?php echo htmlspecialchars(json_encode($booking)); ?>'>
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <form method="POST" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this booking?');">
                                            <input type="hidden" name="action" value="delete_booking">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- View Booking Modal -->
    <div class="modal fade" id="viewBookingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Booking Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="bookingDetails"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Booking Modal -->
    <div class="modal fade" id="editBookingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Booking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="booking_id" id="edit_booking_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" id="edit_status">
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" id="edit_notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle view booking modal
        document.querySelectorAll('[data-bs-target="#viewBookingModal"]').forEach(button => {
            button.addEventListener('click', function() {
                const booking = JSON.parse(this.dataset.booking);
                const details = document.getElementById('bookingDetails');
                
                details.innerHTML = `
                    <dl class="row">
                        <dt class="col-sm-4">Client</dt>
                        <dd class="col-sm-8">${booking.full_name}</dd>
                        
                        <dt class="col-sm-4">Contact</dt>
                        <dd class="col-sm-8">${booking.contact || 'Not provided'}</dd>
                        
                        <dt class="col-sm-4">Package</dt>
                        <dd class="col-sm-8">${booking.package_name}</dd>
                        
                        <dt class="col-sm-4">Date</dt>
                        <dd class="col-sm-8">${new Date(booking.booking_date).toLocaleDateString()}</dd>
                        
                        <dt class="col-sm-4">Status</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-${booking.status === 'confirmed' ? 'success' : 
                                                  (booking.status === 'cancelled' ? 'danger' : 
                                                   (booking.status === 'completed' ? 'info' : 'warning'))}">
                                ${booking.status.charAt(0).toUpperCase() + booking.status.slice(1)}
                            </span>
                        </dd>
                        
                        <dt class="col-sm-4">Notes</dt>
                        <dd class="col-sm-8">${booking.notes || 'No notes'}</dd>
                    </dl>
                `;
            });
        });

        // Handle edit booking modal
        document.querySelectorAll('[data-bs-target="#editBookingModal"]').forEach(button => {
            button.addEventListener('click', function() {
                const booking = JSON.parse(this.dataset.booking);
                document.getElementById('edit_booking_id').value = booking.id;
                document.getElementById('edit_status').value = booking.status;
                document.getElementById('edit_notes').value = booking.notes || '';
            });
        });
    </script>
</body>
</html> 