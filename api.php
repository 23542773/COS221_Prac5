<?php
header("Content-Type: application/json");
require_once 'config.php'; // Database configuration and common functions

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

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Verify this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'timestamp' => round(microtime(true) * 1000),
        'data' => 'Only POST requests are allowed'
    ]);
    exit;
}

// Load config
require_once 'config.php';

class API {
    private static $instance = null;
    private $db;
    
    private function __construct() {
        $config = DBConfig::getInstance();
        $this->db = $config->getConnection();
        
        if (!$this->db) {
            throw new Exception("Database connection failed");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new API();
        }
        return self::$instance;
    } 
    
    public function handleRequest() {
        try {
            $json = file_get_contents('php://input');
            
            if (empty($json)) {
                throw new Exception("No input data received", 400);
            }
            
            $data = json_decode($json, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Invalid JSON input: " . json_last_error_msg(), 400);
            }
            
            if (!isset($data['api'])) {
                throw new Exception("API parameter is required", 400);
            }
            
            switch ($data['api']) {
                case 'login':
                    $this->handleLogin($data);
                    break;
                case 'register':
                    $this->handleRegistration($data);
                    break;
                case 'GetAllProducts':
                    $this->handleGetAllProducts($data);
                    break;
                case 'GetAllRetailers':
                    $this->handleGetAllRetailers($data);
                    break;
                case 'GetDistinct':
                    $this->handleGetDistinct($data);
                    break;
                case 'rating':
                    $this->handleRating($data);
                    break;
                default:
                    throw new Exception("Unknown API endpoint", 400);
            }
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    private function handleLogin($data) {
        if (!isset($data['Email']) || !isset($data['Password'])) {
            throw new Exception("Email and Password are required", 400);
        }
        
        // TODO: Implement authentication logic
        
        $this->sendSuccess([
            'message' => 'Login successful',
            'apikey' => $this->generateApiKey(),
            'isAdmin' => false
        ]);
    }

    private function handleRegistration($data) {
        $required = ['Name', 'Surname', 'Email', 'Password','phoneNumber'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("$field is required", 400);
            }
        }
        
        if (!filter_var($data['Email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format", 400);
        }
        
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\d\s:])([^\s]){8,}$/', $data['Password'])) {
            throw new Exception("Password must be 8+ chars with uppercase, lowercase, number, and special character", 400);
        }
        
        $stmt = $this->db->prepare("SELECT id FROM users WHERE Email = ?");
        if (!$stmt) {
            throw new Exception("Database error", 500);
        }
        
        $stmt->execute([$data['Email']]);
        if ($stmt->fetch()) {
            throw new Exception("Email already registered", 409);
        }

        $stmt = $this->db->prepare("SELECT id FROM users WHERE Phone_Number = ?");
        if (!$stmt) {
        throw new Exception("Database error", 500);
        }
        $stmt->execute([$data['phoneNumber']]);
        if ($stmt->fetch()) {
        throw new Exception("Phone number already registered", 409);
        }
        
        $salt = bin2hex(random_bytes(16));
        $hashedPassword = hash('sha256', $data['Password'] . $salt);
        $apiKey = $this->generateApiKey();
        $fullName = $data['Name'] . ' ' . $data['Surname'];
        $phoneNumber = !empty($data['Phone_Number']) ? $data['Phone_Number'] : null;
        
        $stmt = $this->db->prepare("INSERT INTO users (API_Key, Name,Surname, Password, Salt, Email, Phone_Number) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Database preparation failed", 500);
        }
        
        $success = $stmt->execute([
            $apiKey,
            $data['Name'],
            $data['Surname'],
            $hashedPassword,
            $salt,
            $data['Email'],
            $phoneNumber
        ]);
        
        if (!$success) {
            throw new Exception("Failed to register user", 500);
        }
        
        $this->sendSuccess(['apikey' => $apiKey]);
    }

    private function handleGetAllProducts($data) {
        if (!$this->validateApiKey($data['api'])) {
            throw new Exception("Invalid API key", 401);
        }
        
        $query = "SELECT * FROM products WHERE 1=1";
        
        if (isset($data['search']) && is_array($data['search'])) {
            // TODO: Add search conditions
        }
        
        $limit = isset($data['limit']) ? min(max(1, (int)$data['limit']), 800) : 50;
        $query .= " LIMIT $limit";
        
        if (isset($data['sort'])) {
            $validSortFields = ['category', 'price', 'brand', 'country_of_origin'];
            if (in_array($data['sort'], $validSortFields)) {
                $order = isset($data['order']) && strtoupper($data['order']) === 'DESC' ? 'DESC' : 'ASC';
                $query .= " ORDER BY {$data['sort']} $order";
            }
        }
        
        // TODO: Execute query
        
        $this->sendSuccess([]);
    }

    private function handleGetAllRetailers($data) {
    if (!$this->validateApiKey($data['api'])) {
        throw new Exception("Invalid API key", 401);
    }
    
    if (!isset($data['type']) || $data['type'] !== 'GetAllRetailers') {
        throw new Exception("Invalid type parameter", 400);
    }
    
    // Set default limit to 10, with bounds between 1 and 20
    $limit = isset($data['limit']) ? min(max(1, (int)$data['limit']), 20) : 10;
    
    try {
        // Prepare and execute the query
        $stmt = $this->db->prepare("SELECT RetailerID, Name, URL FROM retailers LIMIT :limit");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        // Fetch all results
        $retailers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format URLs to include https:// if not present
        foreach ($retailers as &$retailer) {
            if (!empty($retailer['URL']) && !preg_match('/^https?:\/\//i', $retailer['URL'])) {
                $retailer['URL'] = 'https://' . $retailer['URL'];
            }
        }
        
        $this->sendSuccess($retailers);
    } catch (PDOException $e) {
        throw new Exception("Database error: " . $e->getMessage(), 500);
    }
}

    private function handleGetDistinct($data) {
        if (!$this->validateApiKey($data['api'])) {
            throw new Exception("Invalid API key", 401);
        }
        
        if (!isset($data['type']) || $data['type'] !== 'GetDistinct') {
            throw new Exception("Invalid type parameter", 400);
        }
        
        if (!isset($data['field'])) {
            throw new Exception("Field parameter is required", 400);
        }
        
        $validFields = ['brand', 'categories', 'manufacturer', 'department', 'country_of_origin'];
        if (!in_array($data['field'], $validFields)) {
            throw new Exception("Invalid field parameter", 400);
        }
        
        $limit = isset($data['limit']) ? min(max(1, (int)$data['limit']), 20) : 10;
        
        // TODO: Fetch distinct values
        
        $this->sendSuccess([]);
    }

    private function handleRating($data) {
        if (!$this->validateApiKey($data['api'])) {
            throw new Exception("Invalid API key", 401);
        }
        
        if (!isset($data['operation'])) {
            throw new Exception("Operation parameter is required", 400);
        }
        
        if ($data['operation'] === 'set') {
            if (!isset($data['review'])) {
                throw new Exception("Review parameter is required for set operation", 400);
            }
            
            $review = (int)$data['review'];
            if ($review < 1 || $review > 5) {
                throw new Exception("Review must be between 1 and 5", 400);
            }
            
            // TODO: Save review
        } elseif ($data['operation'] === 'get') {
            // TODO: Retrieve reviews
        } else {
            throw new Exception("Invalid operation", 400);
        }
        
        $this->sendSuccess(['message' => 'Operation completed']);
    }

    private function generateApiKey() {
        return bin2hex(random_bytes(16));
    }

    private function validateApiKey($apiKey) {
        // TODO: Implement API key validation
        return true;
    }

    private function sendSuccess($data) {
        echo json_encode([
            'status' => 'success',
            'timestamp' => round(microtime(true) * 1000),
            'data' => $data
        ]);
        exit;
    }
    
    private function sendError($message, $code = 400) {
        http_response_code($code);
        echo json_encode([
            'status' => 'error',
            'timestamp' => round(microtime(true) * 1000),
            'data' => $message
        ]);
        exit;
    }
}

// Handle the request
$api = API::getInstance();
$api->handleRequest();
