<?php
header('Content-Type: application/json');

// CSRF protection token (basic)
session_start();

// Initialize response
$response = array(
    'success' => false,
    'message' => ''
);

try {
    // Check if request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate and sanitize input data
    $firstName = sanitizeInput($_POST['firstName'] ?? '');
    $lastName = sanitizeInput($_POST['lastName'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $city = sanitizeInput($_POST['city'] ?? '');
    $state = sanitizeInput($_POST['state'] ?? '');
    $country = sanitizeInput($_POST['country'] ?? '');
    $gender = sanitizeInput($_POST['gender'] ?? '');
    $dob = sanitizeInput($_POST['dob'] ?? '');
    $message = sanitizeInput($_POST['message'] ?? '');
    $terms = isset($_POST['terms']) ? true : false;

    // Validate required fields
    if (empty($firstName) || empty($lastName) || empty($email) || empty($phone) || 
        empty($address) || empty($city) || empty($state) || 
        empty($country) || empty($gender) || empty($dob) || !$terms) {
        throw new Exception('All required fields must be filled');
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Validate phone number (basic validation)
    if (!preg_match('/^[\d\s()+-]{10,}$/', str_replace([' ', '-', '(', ')'], '', $phone))) {
        throw new Exception('Invalid phone number format');
    }

    // Validate date of birth
    $dob_timestamp = strtotime($dob);
    if ($dob_timestamp === false) {
        throw new Exception('Invalid date of birth');
    }

    // Check age (must be 18 or older)
    $age = calculateAge($dob_timestamp);
    if ($age < 18) {
        throw new Exception('You must be at least 18 years old');
    }

    // Validate name fields (only letters, spaces, hyphens, apostrophes)
    if (!preg_match("/^[a-zA-Z\s'-]{2,}$/", $firstName)) {
        throw new Exception('Invalid first name format');
    }
    if (!preg_match("/^[a-zA-Z\s'-]{2,}$/", $lastName)) {
        throw new Exception('Invalid last name format');
    }

    // Validate city
    if (!preg_match("/^[a-zA-Z\s'-]{2,}$/", $city)) {
        throw new Exception('Invalid city format');
    }

    // If using database, you could save the data here
    // Example: $pdo->prepare("INSERT INTO registrations ...")->execute([...]);

    // Log the registration (optional)
    logRegistration([
        'firstName' => $firstName,
        'lastName' => $lastName,
        'email' => $email,
        'phone' => $phone,
        'city' => $city,
        'country' => $country,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

    // Send confirmation email (optional)
    // sendConfirmationEmail($email, $firstName . ' ' . $lastName);

    // Return success response
    $response['success'] = true;
    $response['message'] = 'Registration submitted successfully!';

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = 'Error: ' . $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);
exit();

/**
 * Sanitize and validate input
 */
function sanitizeInput($input) {
    // Remove leading and trailing whitespace
    $input = trim($input);
    
    // Remove any potentially harmful characters
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    
    // Remove null bytes
    $input = str_replace(chr(0), '', $input);
    
    return $input;
}

/**
 * Calculate age from timestamp
 */
function calculateAge($timestamp) {
    $birthDate = new DateTime('@' . $timestamp);
    $today = new DateTime('now');
    $age = $today->diff($birthDate);
    return $age->y;
}

/**
 * Log registration attempts (optional)
 */
function logRegistration($data) {
    $logFile = dirname(__FILE__) . '/registrations_log.txt';
    $logEntry = json_encode($data) . PHP_EOL;
    
    // Append to log file (limit file size to prevent it from growing too large)
    if (file_exists($logFile) && filesize($logFile) > 1000000) { // 1MB limit
        unlink($logFile);
    }
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * Send confirmation email (optional)
 */
function sendConfirmationEmail($email, $name) {
    $subject = "Registration Confirmation";
    $message = "Hello " . $name . ",\n\n";
    $message .= "Thank you for registering with us!\n\n";
    $message .= "We have received your registration and will process it shortly.\n\n";
    $message .= "Best regards,\n";
    $message .= "Registration Team";
    
    $headers = "From: noreply@registration.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    // Uncomment to enable email sending
    // mail($email, $subject, $message, $headers);
}
?>
