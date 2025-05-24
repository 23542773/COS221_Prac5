<?php
header("Content-Type: application/json");
require_once 'config_cos221.php'; // Database configuration and common functions

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
require_once 'config_cos221.php';

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
                case 'logout':
                    $this->handleLogout($data);
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

    $email = trim($data['Email']);
    $password = $data['Password'];

    try {
        // Get user credentials - now including Name and Surname
        $stmt = $this->db->prepare("SELECT API_Key, Password, Salt, Name, Surname FROM users WHERE Email = ?");
        if (!$stmt) {
            throw new Exception("Database preparation failed", 500);
        }
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            throw new Exception("Invalid email or password", 401);
        }

        // Verify password
        $hashedPassword = hash('sha256', $password . $user['Salt']);
        if ($hashedPassword !== $user['Password']) {
            throw new Exception("Invalid email or password", 401);
        }

        // Check admin privileges
        $adminStmt = $this->db->prepare("SELECT Privilege FROM admin WHERE `K` = ?");
        $adminStmt->execute([$user['API_Key']]);
        $adminResult = $adminStmt->fetch();

        $response = [
            'message' => 'Login successful',
            'apikey' => $user['API_Key'],
            'name' => $user['Name'],    // Added name from database
            'surname' => $user['Surname']  // Added surname from database
        ];

        // Add admin privilege if user is admin
        if ($adminResult) {
            $response['privilege'] = $adminResult['Privilege'];
        }

        $this->sendSuccess($response);
    } catch (PDOException $e) {
        throw new Exception("Database error: " . $e->getMessage(), 500);
    }
}

    private function handleLogout($data) {
    if (!isset($data['apikey'])) {
        throw new Exception("API key is required", 400);
    }

    $apiKey = $data['apikey'];

    try {
        // Verify API key exists
        $stmt = $this->db->prepare("SELECT 1 FROM users WHERE API_Key = ?");
        $stmt->execute([$apiKey]);
        
        if (!$stmt->fetch()) {
            throw new Exception("Invalid API key", 401);
        }

        $this->sendSuccess([
            'message' => 'Logout successful'
        ]);
    } catch (PDOException $e) {
        throw new Exception("Database error: " . $e->getMessage(), 500);
    }
}

    private function handleRegistration($data) {
    // Define required fields
    $required = ['Name', 'Surname', 'Email', 'Password', 'phoneNumber'];
    
    // Validate required fields
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("$field is required", 400);
        }
    }
    
    // Validate email format
    $email = trim($data['Email']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format", 400);
    }
    
    // Validate password complexity
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\d\s:])([^\s]){8,}$/', $data['Password'])) {
        throw new Exception("Password must be 8+ chars with uppercase, lowercase, number, and special character", 400);
    }
    
    try {
        // Check if email exists
        $stmt = $this->db->prepare("SELECT Email FROM users WHERE Email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new Exception("Email already registered", 409);
        }

        // Check if phone number exists
        $phoneNumber = $data['phoneNumber'];
        $stmt = $this->db->prepare("SELECT Phone_Number FROM users WHERE Phone_Number = ?");
        $stmt->execute([$phoneNumber]);
        if ($stmt->fetch()) {
            throw new Exception("Phone number already registered", 409);
        }
        
        // Generate secure credentials
        $salt = bin2hex(random_bytes(16));
        $hashedPassword = hash('sha256', $data['Password'] . $salt);
        $apiKey = $this->generateApiKey();
        
        // Insert new user
        $stmt = $this->db->prepare("INSERT INTO users 
            (API_Key, Name, Surname, Password, Salt, Email, Phone_Number) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
            
        $success = $stmt->execute([
            $apiKey,
            $data['Name'],
            $data['Surname'],
            $hashedPassword,
            $salt,
            $email,
            $phoneNumber
        ]);
        
        if (!$success) {
            throw new Exception("Failed to register user", 500);
        }
        
        // Return success with API key
        $this->sendSuccess([
            'message' => 'Registration successful',
            'apikey' => $apiKey
        ]);
        
    } catch (PDOException $e) {
        throw new Exception("Database error: " . $e->getMessage(), 500);
    }
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
    // Validate API key first
    if (!isset($data['apikey'])) {
        throw new Exception("API key is required", 400);
    }
    
    try {
        // Verify API key exists in database
        $stmt = $this->db->prepare("SELECT 1 FROM users WHERE API_Key = ?");
        $stmt->execute([$data['apikey']]);
        if (!$stmt->fetch()) {
            throw new Exception("Invalid API key", 401);
        }

        // Set default limit to 10, with bounds between 1 and 20
        $limit = isset($data['limit']) ? min(max(1, (int)$data['limit']), 20) : 10;
        
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
    // Validate API key
    if (!isset($data['apikey'])) {
        throw new Exception("API key is required", 400);
    }
    
    // Verify API key exists
    $stmt = $this->db->prepare("SELECT 1 FROM users WHERE API_Key = ?");
    $stmt->execute([$data['apikey']]);
    if (!$stmt->fetch()) {
        throw new Exception("Invalid API key", 401);
    }

    // Validate required parameters
    if (!isset($data['table']) || !isset($data['field'])) {
        throw new Exception("Table and field parameters are required", 400);
    }

    // Define valid tables and their allowed fields
    $validTables = [
        'products' => ['ProductID', 'Name', 'Brand', 'Category'],
        'retailers' => ['RetailerID', 'Name'],
        'productratings' => ['K', 'PID', 'Rating', 'Date'],
        'listings' => ['ProductID', 'RID', 'quantity', 'price', 'remaining'],
        'orders' => ['OrderID', 'K', 'OrderDate', 'Total']
    ];

    // Validate table and field
    if (!array_key_exists($data['table'], $validTables)) {
        throw new Exception("Invalid table specified", 400);
    }

    if (!in_array($data['field'], $validTables[$data['table']])) {
        throw new Exception("Invalid field for the specified table", 400);
    }

    // Set limit with bounds
    $limit = isset($data['limit']) ? min(max(1, (int)$data['limit']), 50) : 10;
    
    // Prepare base query
    $query = "SELECT DISTINCT {$data['field']} FROM {$data['table']}";
    $params = [];

    // Add search condition if provided
    if (!empty($data['search'])) {
        $searchTerm = "%{$data['search']}%";
        
        // Special cases for different tables
        if ($data['table'] === 'products' && $data['field'] === 'Name') {
            $query = "SELECT * FROM products WHERE Name LIKE :search LIMIT :limit";
            $params[':search'] = $searchTerm;
        } 
        elseif ($data['table'] === 'retailers' && $data['field'] === 'Name') {
            $query = "SELECT * FROM retailers WHERE Name LIKE :search LIMIT :limit";
            $params[':search'] = $searchTerm;
        }
        elseif ($data['table'] === 'listings') {
            // For listings, we'll return full listing details when searching by ProductID or RID
            if (in_array($data['field'], ['ProductID', 'RID'])) {
                $query = "SELECT l.*, p.Name as ProductName, r.Name as RetailerName 
                          FROM listings l
                          JOIN products p ON l.ProductID = p.ProductID
                          JOIN retailers r ON l.RID = r.RetailerID
                          WHERE l.{$data['field']} LIKE :search
                          LIMIT :limit";
                $params[':search'] = $searchTerm;
            } else {
                $query .= " WHERE {$data['field']} LIKE :search";
                $params[':search'] = $searchTerm;
            }
        }
        elseif ($data['table'] === 'orders') {
            // For orders, return full order details when searching by OrderID or K
            if (in_array($data['field'], ['OrderID', 'K'])) {
                $query = "SELECT o.*, u.Name as UserName, u.Surname as UserSurname 
                          FROM orders o
                          JOIN users u ON o.K = u.API_Key
                          WHERE o.{$data['field']} LIKE :search
                          LIMIT :limit";
                $params[':search'] = $searchTerm;
            } else {
                $query .= " WHERE {$data['field']} LIKE :search";
                $params[':search'] = $searchTerm;
            }
        }
        else {
            $query .= " WHERE {$data['field']} LIKE :search";
            $params[':search'] = $searchTerm;
        }
    }

    // Add limit (except when getting full details which already has limit)
    $fullDetailCases = [
        'products' => 'Name',
        'retailers' => 'Name',
        'listings' => ['ProductID', 'RID'],
        'orders' => ['OrderID', 'K']
    ];
    
    $isFullDetailCase = (
        ($data['table'] === 'products' && $data['field'] === 'Name') ||
        ($data['table'] === 'retailers' && $data['field'] === 'Name') ||
        ($data['table'] === 'listings' && in_array($data['field'], ['ProductID', 'RID'])) ||
        ($data['table'] === 'orders' && in_array($data['field'], ['OrderID', 'K']))
    );

    if (!$isFullDetailCase) {
        $query .= " LIMIT :limit";
    }

    // Prepare and execute query
    $stmt = $this->db->prepare($query);
    
    // Bind parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    // Bind limit if not already bound
    if (!array_key_exists(':limit', $params) && !$isFullDetailCase) {
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    }

    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format results - extract just the field values unless we got full details
    $shouldExtractField = !(
        ($data['table'] === 'products' && $data['field'] === 'Name') ||
        ($data['table'] === 'retailers' && $data['field'] === 'Name') ||
        ($data['table'] === 'listings' && in_array($data['field'], ['ProductID', 'RID'])) ||
        ($data['table'] === 'orders' && in_array($data['field'], ['OrderID', 'K']))
    );

    if ($shouldExtractField) {
        $results = array_column($results, $data['field']);
        // Remove empty values
        $results = array_filter($results, function($value) {
            return !empty($value);
        });
        // Re-index array
        $results = array_values($results);
    }

    $this->sendSuccess([
        'table' => $data['table'],
        'field' => $data['field'],
        'count' => count($results),
        'results' => $results
    ]);
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
