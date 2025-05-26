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
                case 'wishlist':
                    $this->handleWishlist($data);
                    break;
                default:
                    throw new Exception("Unknown API endpoint", 400);
            }
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), $e->getCode() ?: 500);
        }
    }


    private function handleWishlist($data) {
    // Validate required parameters
    if (!isset($data['apikey']) || !isset($data['operation'])) {
        throw new Exception("API key and operation parameters are required", 400);
    }

    $apiKey = $data['apikey'];
    $operation = strtolower($data['operation']);

    try {
        // Verify API key exists
        $stmt = $this->db->prepare("SELECT 1 FROM users WHERE API_Key = ?");
        $stmt->execute([$apiKey]);
        if (!$stmt->fetch()) {
            throw new Exception("Invalid API key", 401);
        }

        switch ($operation) {
            case 'get':
                $this->handleGetWishlist($apiKey);
                break;
            case 'set':
                if (!isset($data['ProductID'])) {
                    throw new Exception("ProductID is required for set operation", 400);
                }
                $this->handleSetWishlist($apiKey, (int)$data['ProductID']);
                break;
            case 'unset':
                if (!isset($data['ProductID'])) {
                    throw new Exception("ProductID is required for unset operation", 400);
                }
                $this->handleUnsetWishlist($apiKey, (int)$data['ProductID']);
                break;
            default:
                throw new Exception("Invalid operation. Must be 'get', 'set', or 'unset'", 400);
        }
    } catch (PDOException $e) {
        throw new Exception("Database error: " . $e->getMessage(), 500);
    }
}

private function handleGetWishlist($apiKey) {
    // Get all wishlist items with product details
    $stmt = $this->db->prepare("
        SELECT w.ProductID, p.Name, p.Price, p.Thumbnail
        FROM wishlist w
        JOIN products p ON w.ProductID = p.ProductID
        WHERE w.API_Key = ?
        ORDER BY w.AddedAt DESC
    ");
    $stmt->execute([$apiKey]);
    $wishlistItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $this->sendSuccess([
        'wishlist' => $wishlistItems,
        'count' => count($wishlistItems)
    ]);
}

private function handleSetWishlist($apiKey, $productId) {
    // Verify product exists
    $stmt = $this->db->prepare("SELECT 1 FROM products WHERE ProductID = ?");
    $stmt->execute([$productId]);
    if (!$stmt->fetch()) {
        throw new Exception("Product not found", 404);
    }

    // Check if already in wishlist
    $stmt = $this->db->prepare("SELECT 1 FROM wishlist WHERE API_Key = ? AND ProductID = ?");
    $stmt->execute([$apiKey, $productId]);
    if ($stmt->fetch()) {
        throw new Exception("Product already in wishlist", 409);
    }

    // Add to wishlist
    $stmt = $this->db->prepare("INSERT INTO wishlist (API_Key, ProductID, AddedAt) VALUES (?, ?, NOW())");
    $stmt->execute([$apiKey, $productId]);

    $this->sendSuccess([
        'message' => 'Product added to wishlist',
        'productId' => $productId
    ]);
}

private function handleUnsetWishlist($apiKey, $productId) {
    // Remove from wishlist
    $stmt = $this->db->prepare("DELETE FROM wishlist WHERE API_Key = ? AND ProductID = ?");
    $stmt->execute([$apiKey, $productId]);

    if ($stmt->rowCount() === 0) {
        throw new Exception("Product not found in wishlist", 404);
    }

    $this->sendSuccess([
        'message' => 'Product removed from wishlist',
        'productId' => $productId
    ]);
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

        // Check if user is an admin by existence in admin table
        $adminStmt = $this->db->prepare("SELECT 1 FROM admin WHERE `K` = ?");
        $adminStmt->execute([$user['API_Key']]);
        $isAdmin = $adminStmt->fetch() ? true : false;

        $response = [
            'message' => 'Login successful',
            'apikey' => $user['API_Key'],
            'name' => $user['Name'],
            'surname' => $user['Surname'],
            'isadmin' => $isAdmin
        ];

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
    if (!isset($data['table'])) {
        throw new Exception("Table parameter is required", 400);
    }

    // Define table structures with all columns to return
    $tableStructures = [
        'products' => [
            'fields' => ['products.ProductID', 'Name', 'Brand', 'Category'],
            'columns' => ['products.ProductID', 'Name', 'Description', 'Brand', 'Category', 'Thumbnail'],
            'filterable' => ['Brand', 'Category'] // Added filterable fields
        ],
        'retailers' => [
            'fields' => ['retailers.RetailerID', 'Name'],
            'columns' => ['retailers.RetailerID', 'Name', 'URL']
        ],
        'productratings' => [
            'fields' => ['productratings.K', 'productratings.PID', 'Rating', 'Date'],
            'columns' => ['productratings.K', 'productratings.PID', 'Rating', 'Comment', 'Date']
        ],
        'listings' => [
            'fields' => ['listings.ProductID', 'listings.RID', 'quantity', 'price', 'remaining'],
            'columns' => ['listings.ProductID', 'listings.RID', 'quantity', 'price', 'remaining'],
            'joins' => [
                'products' => ['listings.ProductID' => 'products.ProductID'],
                'retailers' => ['listings.RID' => 'retailers.RetailerID']
            ],
            'sortable' => ['price'] // Added sortable fields
        ],
        'orders' => [
            'fields' => ['orders.OrderID', 'orders.K', 'OrderDate', 'Total'],
            'columns' => ['orders.OrderID', 'orders.K', 'OrderDate', 'Total'],
            'joins' => [
                'users' => ['orders.K' => 'users.API_Key']
            ]
        ]
    ];

    // Validate table
    if (!array_key_exists($data['table'], $tableStructures)) {
        throw new Exception("Invalid table specified", 400);
    }

    $table = $data['table'];
    $tableConfig = $tableStructures[$table];
    $columns = implode(', ', $tableConfig['columns']);

    // Set limit
    $limit = isset($data['limit']) ? max(1, (int)$data['limit']) : 10;

    // Base FROM and JOINs
    $query = "FROM $table";
    if (isset($tableConfig['joins'])) {
        foreach ($tableConfig['joins'] as $joinTable => $joinConditions) {
            foreach ($joinConditions as $left => $right) {
                $query .= " JOIN $joinTable ON $left = $right";
            }
        }
    }

    $params = [];
    $whereConditions = [];

    // Add filter conditions if provided (for products table)
    if ($table === 'products' && isset($data['filter']) && is_array($data['filter'])) {
        foreach ($data['filter'] as $field => $value) {
            if (in_array($field, $tableConfig['filterable'])) {
                $whereConditions[] = "$field = :filter_$field";
                $params[":filter_$field"] = $value;
            }
        }
    }

    // Add search condition if applicable
    if (isset($data['field']) && isset($data['search'])) {
        if (!in_array($data['field'], $tableConfig['fields'])) {
            throw new Exception("Invalid field for the specified table", 400);
        }
        $searchTerm = "%{$data['search']}%";
        $whereConditions[] = "{$data['field']} LIKE :search";
        $params[':search'] = $searchTerm;
    } elseif (isset($data['field']) || isset($data['search'])) {
        throw new Exception("Both field and search parameters must be provided together", 400);
    }

    // Add product ID filter for listings if provided
    if ($table === 'listings' && isset($data['productId'])) {
        $whereConditions[] = "listings.ProductID = :productId";
        $params[':productId'] = (int)$data['productId'];
    }

    // Combine all WHERE conditions
    if (!empty($whereConditions)) {
        $query .= " WHERE " . implode(' AND ', $whereConditions);
    }

    // Add sorting if requested (for listings table)
    $orderBy = '';
    if ($table === 'listings' && isset($data['sort']) && $data['sort'] === 'price') {
        $order = isset($data['order']) && strtoupper($data['order']) === 'DESC' ? 'DESC' : 'ASC';
        $orderBy = " ORDER BY listings.price $order";
    }

    // Final SELECT with DISTINCT if no filtering
    $selectClause = (isset($data['field']) && isset($data['search'])) || !empty($whereConditions)
        ? "SELECT $columns"
        : "SELECT DISTINCT $columns";

    // Compose final query
    $finalQuery = "$selectClause $query $orderBy LIMIT :limit";
    $params[':limit'] = $limit;

    // Prepare and execute
    $stmt = $this->db->prepare($finalQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, $key === ':limit' ? PDO::PARAM_INT : PDO::PARAM_STR);
    }

    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $this->sendSuccess([
        'table' => $table,
        'count' => count($results),
        'results' => $results
    ]);
}


private function handleRating($data) {
    // Validate API key
    if (!isset($data['apikey'])) {
        throw new Exception("API key is required", 400);
    }
    
    if (!$this->validateApiKey($data['apikey'])) {
        throw new Exception("Invalid API key", 401);
    }
    
    // Validate operation parameter
    if (!isset($data['operation'])) {
        throw new Exception("Operation parameter is required", 400);
    }
    
    $operation = strtolower($data['operation']);
    
    if ($operation === 'set') {
        $this->handleSetRating($data);
    } elseif ($operation === 'get') {
        $this->handleGetRating($data);
    } else {
        throw new Exception("Invalid operation. Must be 'get' or 'set'", 400);
    }
}

private function handleSetRating($data) {
    if (!isset($data['rating']) || !isset($data['productId'])) {
        throw new Exception("rating and Product ID are required", 400);
    }

    $rating = (int)$data['rating'];
    $comment = isset($data['comment']) ? $data['comment'] : null;
    if ($rating < 1 || $rating > 5) {
        throw new Exception("rating must be between 1 and 5", 400);
    }

    $productId = (int)$data['productId'];
    $apiKey = $data['apikey'];

    try {
        $stmt = $this->db->prepare("SELECT API_Key FROM users WHERE API_Key = ?");
        $stmt->execute([$apiKey]);
        if (!$stmt->fetch()) throw new Exception("Invalid API key", 401);

        $stmt = $this->db->prepare("SELECT ProductID FROM products WHERE ProductID = ?");
        $stmt->execute([$productId]);
        if (!$stmt->fetch()) throw new Exception("Product not found", 404);

        $stmt = $this->db->prepare("SELECT K FROM productratings WHERE K = ? AND PID = ?");
        $stmt->execute([$apiKey, $productId]);
        $existingRating = $stmt->fetch();

        if ($existingRating) {
            $stmt = $this->db->prepare("UPDATE productratings SET Rating = ?, Comment = ?, Date = NOW() WHERE K = ? AND PID = ?");
            $stmt->execute([$rating, $comment, $apiKey, $productId]);
            $message = "Rating updated successfully";
        } else {
            $stmt = $this->db->prepare("INSERT INTO productratings (K, PID, Rating, Comment, Date) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$apiKey, $productId, $rating, $comment]);
            $message = "Rating added successfully";
        }

        $stmt = $this->db->prepare("SELECT AVG(Rating) as avg_rating, COUNT(*) as total_ratings FROM productratings WHERE PID = ?");
        $stmt->execute([$productId]);
        $ratingStats = $stmt->fetch();

        $this->sendSuccess([
            'message' => $message,
            'productId' => $productId,
            'userRating' => $rating,
            'averageRating' => round($ratingStats['avg_rating'], 2),
            'totalRatings' => (int)$ratingStats['total_ratings']
        ]);
    } catch (PDOException $e) {
        throw new Exception("Database error: " . $e->getMessage(), 500);
    }
}

private function handleGetRating($data) {
    try {
        // Validate that we have at least one identifier
        if (!isset($data['productId']) && !isset($data['apikey'])) {
            throw new Exception("Either productId or apikey parameter is required", 400);
        }

        // If productId is provided, get all ratings for that product
        if (isset($data['productId'])) {
            $productId = (int)$data['productId'];

            // Verify product exists
            $stmt = $this->db->prepare("SELECT ProductID FROM products WHERE ProductID = ?");
            $stmt->execute([$productId]);
            if (!$stmt->fetch()) {
                throw new Exception("Product not found", 404);
            }

            // Get all ratings for the product with user names
            $stmt = $this->db->prepare("
                SELECT r.Rating, r.Comment, r.Date, u.Name 
                FROM productratings r 
                JOIN users u ON r.K = u.API_Key 
                WHERE r.PID = ? 
                ORDER BY r.Date DESC
            ");
            $stmt->execute([$productId]);
            $ratings = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get rating statistics
            $stmt = $this->db->prepare("
                SELECT AVG(Rating) as avg_rating, COUNT(*) as total_ratings 
                FROM productratings 
                WHERE PID = ?
            ");
            $stmt->execute([$productId]);
            $ratingStats = $stmt->fetch();

            $response = [
                'productId' => $productId,
                'averageRating' => $ratingStats['avg_rating'] ? round($ratingStats['avg_rating'], 2) : 0,
                'totalRatings' => (int)$ratingStats['total_ratings'],
                'ratings' => $ratings
            ];
        } 
        // If only apikey is provided, get all ratings by that user
        else {
            $apiKey = $data['apikey'];

            // Verify API key exists
            $stmt = $this->db->prepare("SELECT 1 FROM users WHERE API_Key = ?");
            $stmt->execute([$apiKey]);
            if (!$stmt->fetch()) {
                throw new Exception("Invalid API key", 401);
            }

            // Get all ratings left by this user with product names
            $stmt = $this->db->prepare("
                SELECT r.PID as productId, r.Rating, r.Comment, r.Date, p.Name AS productName 
                FROM productratings r 
                JOIN products p ON r.PID = p.ProductID 
                WHERE r.K = ? 
                ORDER BY r.Date DESC
            ");
            $stmt->execute([$apiKey]);
            $userRatings = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response = [
                'userId' => $apiKey,
                'userRatings' => $userRatings,
                'totalUserRatings' => count($userRatings)
            ];
        }

        $this->sendSuccess($response);
    } catch (PDOException $e) {
        throw new Exception("Database error: " . $e->getMessage(), 500);
    }
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
