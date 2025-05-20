<?php
header("Content-Type: application/json");
require_once 'config.php'; // Database configuration and common function

// Initialize response array
$response = [
    'status' => 'error',
    'message' => 'Invalid request',
    'timestamp' => time()
];

// Get the request data
$requestData = json_decode(file_get_contents('php://input'), true);

// Validate JSON input
if (json_last_error() !== JSON_ERROR_NONE) {
    $response['message'] = 'Invalid JSON input';
    echo json_encode($response);
    exit;
}

// Check if API parameter is present
if (!isset($requestData['api'])) {
    $response['message'] = 'API parameter missing';
    echo json_encode($response);
    exit;
}

// Route the API request
switch ($requestData['api']) {
    case 'login':
        handleLogin($requestData);
        break;
        
    case 'register':
        handleRegister($requestData);
        break;
        
    case 'GetAllProducts':
        handleGetAllProducts($requestData);
        break;
        
    case 'GetAllRetailers':
        handleGetAllRetailers($requestData);
        break;
        
    case 'GetDistinct':
        handleGetDistinct($requestData);
        break;
        
    case 'rating':
        handleRating($requestData);
        break;
        
    // Add other API cases here
        
    default:
        $response['message'] = 'Unknown API endpoint';
        echo json_encode($response);
        break;
}

// API Handler Functions

function handleLogin($data) {
    global $response;
    
    // Validate required parameters
    if (!isset($data['Email']) || !isset($data['Password'])) {
        $response['message'] = 'Email and Password are required';
        echo json_encode($response);
        return;
    }
    
    // TODO: Implement authentication logic
    // Verify email and password against database
    
    // On successful authentication
    $response['status'] = 'success';
    $response['message'] = 'Login successful';
    $response['apikey'] = generateApiKey(); // Implement this function
    $response['isAdmin'] = false; // Set based on user role
    
    echo json_encode($response);
}

function handleRegister($data) {
    global $response;
    
    // Validate required parameters
    $requiredFields = ['Name', 'Surname', 'Email', 'password', 'User_type'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field])) {
            $response['message'] = "Missing required field: $field";
            echo json_encode($response);
            return;
        }
    }
    
    // Validate email format
    if (!filter_var($data['Email'], FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Invalid email format';
        echo json_encode($response);
        return;
    }
    
    // Validate password complexity
    if (!validatePassword($data['password'])) {
        $response['message'] = 'Password must be at least 8 characters with upper/lower case and special character';
        echo json_encode($response);
        return;
    }
    
    // Validate user type
    $validUserTypes = ['Admin', 'Customer'];
    if (!in_array($data['User_type'], $validUserTypes)) {
        $response['message'] = 'Invalid User_type';
        echo json_encode($response);
        return;
    }
    
    // TODO: Check if email already exists
    
    // TODO: Create user in database
    
    $response['status'] = 'success';
    $response['message'] = 'Registration successful';
    $response['data'] = [
        'apikey' => generateApiKey() // Implement this function
    ];
    
    echo json_encode($response);
}

function handleGetAllProducts($data) {
    global $response;
    
    // Validate API key
    if (!validateApiKey($data['api'])) {
        $response['message'] = 'Invalid API key';
        echo json_encode($response);
        return;
    }
    
    // Build query based on parameters
    $query = "SELECT * FROM products WHERE 1=1";
    
    // Handle search parameters
    if (isset($data['search']) && is_array($data['search'])) {
        // TODO: Add search conditions to query
    }
    
    // Handle limit
    $limit = isset($data['limit']) ? min(max(1, (int)$data['limit']), 800) : 50;
    $query .= " LIMIT $limit";
    
    // Handle sort and order
    if (isset($data['sort'])) {
        $validSortFields = ['category', 'price', 'brand', 'country_of_origin'];
        if (in_array($data['sort'], $validSortFields)) {
            $order = isset($data['order']) && strtoupper($data['order']) === 'DESC' ? 'DESC' : 'ASC';
            $query .= " ORDER BY {$data['sort']} $order";
        }
    }
    
    // TODO: Execute query and fetch results
    
    $response['status'] = 'success';
    $response['data'] = []; // Populate with actual data
    
    echo json_encode($response);
}

function handleGetAllRetailers($data) {
    global $response;
    
    // Validate API key
    if (!validateApiKey($data['api'])) {
        $response['message'] = 'Invalid API key';
        echo json_encode($response);
        return;
    }
    
    if (!isset($data['type']) || $data['type'] !== 'GetAllRetailers') {
        $response['message'] = 'Invalid type parameter';
        echo json_encode($response);
        return;
    }
    
    // Handle limit
    $limit = isset($data['limit']) ? min(max(1, (int)$data['limit']), 20) : 10;
    
    // TODO: Fetch retailers from database
    
    $response['status'] = 'success';
    $response['data'] = []; // Populate with retailer data
    
    echo json_encode($response);
}

function handleGetDistinct($data) {
    global $response;
    
    // Validate API key
    if (!validateApiKey($data['api'])) {
        $response['message'] = 'Invalid API key';
        echo json_encode($response);
        return;
    }
    
    if (!isset($data['type']) || $data['type'] !== 'GetDistinct') {
        $response['message'] = 'Invalid type parameter';
        echo json_encode($response);
        return;
    }
    
    if (!isset($data['field'])) {
        $response['message'] = 'Field parameter is required';
        echo json_encode($response);
        return;
    }
    
    $validFields = ['brand', 'categories', 'manufacturer', 'department', 'country_of_origin'];
    if (!in_array($data['field'], $validFields)) {
        $response['message'] = 'Invalid field parameter';
        echo json_encode($response);
        return;
    }
    
    // Handle limit
    $limit = isset($data['limit']) ? min(max(1, (int)$data['limit']), 20) : 10;
    
    // TODO: Fetch distinct values for the specified field
    
    $response['status'] = 'success';
    $response['data'] = []; // Populate with distinct values
    
    echo json_encode($response);
}

function handleRating($data) {
    global $response;
    
    // Validate API key
    if (!validateApiKey($data['api'])) {
        $response['message'] = 'Invalid API key';
        echo json_encode($response);
        return;
    }
    
    if (!isset($data['operation'])) {
        $response['message'] = 'Operation parameter is required';
        echo json_encode($response);
        return;
    }
    
    if ($data['operation'] === 'set') {
        if (!isset($data['review'])) {
            $response['message'] = 'Review parameter is required for set operation';
            echo json_encode($response);
            return;
        }
        
        $review = (int)$data['review'];
        if ($review < 1 || $review > 5) {
            $response['message'] = 'Review must be between 1 and 5';
            echo json_encode($response);
            return;
        }
        
        // TODO: Save review to database
    } elseif ($data['operation'] === 'get') {
        // TODO: Retrieve reviews from database
    } else {
        $response['message'] = 'Invalid operation';
        echo json_encode($response);
        return;
    }
    
    $response['status'] = 'success';
    $response['message'] = 'Operation completed';
    
    echo json_encode($response);
}

// Helper functions

function generateApiKey() {
    return bin2hex(random_bytes(16));
}

function validatePassword($password) {
    // At least 8 characters, upper and lower case, and a special character
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\da-zA-Z]).{8,}$/', $password);
}

function validateApiKey($apiKey) {
    // TODO: Implement API key validation against database
    return true; // Placeholder
}
?>