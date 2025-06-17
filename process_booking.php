<?php
header('Content-Type: application/json');
require_once 'config/database.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $date = $_POST['date'] ?? '';
    $service = $_POST['service'] ?? '';
    $message = $_POST['message'] ?? '';
    
    // Validate inputs
    if (empty($name) || empty($email) || empty($phone) || empty($date) || empty($service)) {
        $response['message'] = 'Please fill in all required fields.';
        echo json_encode($response);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Please enter a valid email address.';
        echo json_encode($response);
        exit;
    }
    
    try {
        $stmt = $conn->prepare("INSERT INTO bookings (name, email, phone, event_date, service_type, message, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$name, $email, $phone, $date, $service, $message]);
        
        // Send email notification to admin
        $to = 'admin@studiobagosi.com'; // Replace with your email
        $subject = 'New Booking Request';
        $email_message = "New booking request received:\n\n";
        $email_message .= "Name: $name\n";
        $email_message .= "Email: $email\n";
        $email_message .= "Phone: $phone\n";
        $email_message .= "Event Date: $date\n";
        $email_message .= "Service: $service\n";
        $email_message .= "Message: $message\n";
        
        $headers = "From: $email\r\n";
        $headers .= "Reply-To: $email\r\n";
        
        mail($to, $subject, $email_message, $headers);
        
        $response = [
            'success' => true,
            'message' => 'Booking request submitted successfully!'
        ];
    } catch(PDOException $e) {
        $response['message'] = 'An error occurred while processing your request.';
        error_log($e->getMessage());
    }
}

echo json_encode($response);
?> 