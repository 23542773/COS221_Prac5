<?php
header("Content-Type: application/json");
require_once 'config_cos221.php'; 


$response = [
    'status' => 'error',
    'message' => 'Invalid request',
    'timestamp' => time()
];

$requestData = json_decode(file_get_contents('php://input'), true);

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
                case 'getAllCategories':
                    $this->handleGetAllCategories($data);
                    break;
                case 'wishlist':
                    $this->handleWishlist($data);
                    break;
                case 'getAllUsers':
                    $this->handleGetAllUsers($data);
                    break;
                case 'admin':
                    $this->handleAdmin($data);
                    break;
                case 'addToCart':
                    $this->handleAddToCart($data);
                    break;
                default:
                    throw new Exception("Unknown API endpoint", 400);
            }
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    private function handleAddToCart($data) {
    
    if (!isset($data['apikey']) || !isset($data['operation'])) {
        throw new Exception("API key and operation parameters are required", 400);
    }

    $apiKey = $data['apikey'];
    $operation = strtolower($data['operation']);

    try {
        $stmt = $this->db->prepare("SELECT 1 FROM users WHERE API_Key = ?");
        $stmt->execute([$apiKey]);
        if (!$stmt->fetch()) {
            throw new Exception("Invalid API key", 401);
        }

        switch ($operation) {
            case 'get':
                $this->handleGetCart($apiKey);
                break;
            case 'set':
                if (!isset($data['PID']) || !isset($data['Quantity'])) {
                    throw new Exception("ProductID and Quantity are required for set operation", 400);
                }
                $this->handleSetCart($apiKey, (int)$data['PID'], (int)$data['Quantity']);
                break;
            case 'unset':
                if (!isset($data['PID'])) {
                    throw new Exception("ProductID is required for unset operation", 400);
                }
                $this->handleUnsetCart($apiKey, (int)$data['PID']);
                break;
            default:
                throw new Exception("Invalid operation. Must be 'get', 'set', or 'unset'", 400);
        }
    } catch (PDOException $e) {
        throw new Exception("Database error: " . $e->getMessage(), 500);
    }
}

private function handleGetCart($apiKey) {
    $stmt = $this->db->prepare("
        SELECT c.PID, p.Name, p.Description, p.Brand, p.Category, p.Thumbnail, 
               c.Quantity, SUM(l.remaining) as Available
        FROM carts c
        JOIN products p ON c.PID = p.ProductID
        LEFT JOIN listings l ON p.ProductID = l.ProductID
        WHERE c.K = ?
        GROUP BY c.PID
    ");
    $stmt->execute([$apiKey]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $this->sendSuccess([
        'carts' => $cartItems,
        'count' => count($cartItems)
    ]);
}

private function handleSetCart($apiKey, $productId, $quantity) {
    if ($quantity <= 0) {
        throw new Exception("Quantity must be greater than 0", 400);
    }

    $stmt = $this->db->prepare("SELECT Name FROM products WHERE ProductID = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    if (!$product) {
        throw new Exception("Product not found", 404);
    }

    $stmt = $this->db->prepare("SELECT SUM(remaining) as available FROM listings WHERE ProductID = ?");
    $stmt->execute([$productId]);
    $available = $stmt->fetch()['available'];
    
    if ($available < $quantity) {
        throw new Exception("Not enough stock available (only $available left)", 400);
    }

    $stmt = $this->db->prepare("SELECT Quantity FROM carts WHERE K = ? AND PID = ?");
    $stmt->execute([$apiKey, $productId]);
    $existingItem = $stmt->fetch();

    if ($existingItem) {
        $stmt = $this->db->prepare("UPDATE carts SET Quantity = ? WHERE K = ? AND PID = ?");
        $stmt->execute([$quantity, $apiKey, $productId]);
        $message = "Carts item quantity updated";
    } else {
        
        $stmt = $this->db->prepare("INSERT INTO carts (K, PID, Quantity) VALUES (?, ?, ?)");
        $stmt->execute([$apiKey, $productId, $quantity]);
        $message = "Item added to carts";
    }

    $this->sendSuccess([
        'message' => $message,
        'cartsItem' => [
            'PID' => $productId,
            'Name' => $product['Name'],
            'Quantity' => $quantity,
            'Available' => $available
        ]
    ]);
}

private function handleUnsetCart($apiKey, $productId) {
    $stmt = $this->db->prepare("DELETE FROM carts WHERE K = ? AND PID = ?");
    $stmt->execute([$apiKey, $productId]);

    if ($stmt->rowCount() === 0) {
        throw new Exception("Product not found in carts", 404);
    }

    $this->sendSuccess([
        'message' => 'Product removed from carts',
        'productId' => $productId
    ]);
}

    private function handleGetAllUsers($data) {
    if (!isset($data['apikey'])) {
        throw new Exception("API key is required", 400);
    }
    
    try {
        $stmt = $this->db->prepare("SELECT 1 FROM admin WHERE K = ?");
        $stmt->execute([$data['apikey']]);
        if (!$stmt->fetch()) {
            throw new Exception("Unauthorized - Admin access required", 403);
        }

        $limit = isset($data['limit']) ? min(max(1, (int)$data['limit']), 100) : 50;
        
        $stmt = $this->db->prepare("
            SELECT API_Key, Name, Surname, Email, Phone_Number 
            FROM users 
            ORDER BY Name ASC 
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $this->sendSuccess([
            'count' => count($users),
            'users' => $users
        ]);
    } catch (PDOException $e) {
        throw new Exception("Database error: " . $e->getMessage(), 500);
    }
}

    private function handleGetAllCategories($data) {
    if (!isset($data['apikey'])) {
        throw new Exception("API key is required", 400);
    }
    
    if (!$this->validateApiKey($data['apikey'])) {
        throw new Exception("Invalid API key", 401);
    }

    try {
        $limit = isset($data['limit']) ? min(max(1, (int)$data['limit']), 100) : 50;
        
        $stmt = $this->db->prepare("SELECT DISTINCT Category FROM categories ORDER BY Category ASC LIMIT :limit");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        
        $this->sendSuccess([
            'count' => count($categories),
            'categories' => $categories
        ]);
    } catch (PDOException $e) {
        throw new Exception("Database error: " . $e->getMessage(), 500);
    }
}

    private function handleWishlist($data) {
    if (!isset($data['apikey']) || !isset($data['operation'])) {
        throw new Exception("API key and operation parameters are required", 400);
    }

    $apiKey = $data['apikey'];
    $operation = strtolower($data['operation']);

    try {
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
    $stmt = $this->db->prepare("
        SELECT p.ProductID, p.Name, p.Description, p.Brand, p.Category, p.Thumbnail
        FROM wishlist w
        JOIN products p ON w.PID = p.ProductID
        WHERE w.K = ?
    ");
    $stmt->execute([$apiKey]);
    $wishlistItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $this->sendSuccess([
        'wishlist' => $wishlistItems,
        'count' => count($wishlistItems)
    ]);
}

private function handleSetWishlist($apiKey, $productId) {
    $stmt = $this->db->prepare("SELECT 1 FROM products WHERE ProductID = ?");
    $stmt->execute([$productId]);
    if (!$stmt->fetch()) {
        throw new Exception("Product not found", 404);
    }
    $stmt = $this->db->prepare("SELECT 1 FROM wishlist WHERE K = ? AND PID = ?");
    $stmt->execute([$apiKey, $productId]);
    if ($stmt->fetch()) {
        throw new Exception("Product already in wishlist", 409);
    }

    $stmt = $this->db->prepare("INSERT INTO wishlist (K, PID) VALUES (?, ?)");
    $stmt->execute([$apiKey, $productId]);

    $this->sendSuccess([
        'message' => 'Product added to wishlist',
        'productId' => $productId
    ]);
}

private function handleUnsetWishlist($apiKey, $productId) {
    $stmt = $this->db->prepare("DELETE FROM wishlist WHERE K = ? AND PID = ?");
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
        // Get user credentials 
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
    $required = ['Name', 'Surname', 'Email', 'Password', 'phoneNumber'];
    
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("$field is required", 400);
        }
    }
    
    $email = trim($data['Email']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format", 400);
    }
    
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\d\s:])([^\s]){8,}$/', $data['Password'])) {
        throw new Exception("Password must be 8+ chars with uppercase, lowercase, number, and special character", 400);
    }
    
    try {
        $stmt = $this->db->prepare("SELECT Email FROM users WHERE Email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new Exception("Email already registered", 409);
        }

        $phoneNumber = $data['phoneNumber'];
        $stmt = $this->db->prepare("SELECT Phone_Number FROM users WHERE Phone_Number = ?");
        $stmt->execute([$phoneNumber]);
        if ($stmt->fetch()) {
            throw new Exception("Phone number already registered", 409);
        }
        
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
        
        $this->sendSuccess([
            'message' => 'Registration successful',
            'apikey' => $apiKey
        ]);
        
    } catch (PDOException $e) {
        throw new Exception("Database error: " . $e->getMessage(), 500);
    }
}

     private function handleAdmin($data) {
    // Validate API key
    if (!isset($data['apikey'])) {
        throw new Exception("API key is required", 400);
    }
    
    if (!$this->validateApiKey($data['apikey'])) {
        throw new Exception("Invalid API key", 401);
    }
    
    // Check if user is admin - only admins can perform admin operations
    if (!$this->isAdmin($data['apikey'])) {
        throw new Exception("Access denied. Admin privileges required", 403);
    }
    
    // Validate operation parameter
    if (!isset($data['operation'])) {
        throw new Exception("Operation parameter is required", 400);
    }
    
    $operation = strtolower($data['operation']);
    
    switch ($operation) {
        case 'create':
            $this->handleUniversalCreate($data);
            break; 
        case 'createadmin': // Changed from 'createAdmin' to 'createadmin'
            $this->handleCreateAdmin($data);
            break; 
        case 'get':
            $this->handleGetAdmin($data);
            break;
        case 'update':
            $this->handleUniversalUpdate($data);
            break;
        case 'delete':
            $this->handleUniversalDelete($data);
            break;
        case 'list':
            $this->handleListAdmins($data);
            break;
        default:
            throw new Exception("Invalid operation. Must be 'create', 'read', 'update', 'delete', or 'list'", 400);
    }
}
// Helper method to check if user is admin
private function isAdmin($apiKey) {
    try {
        $stmt = $this->db->prepare("SELECT 1 FROM admin WHERE K = ?");
        $stmt->execute([$apiKey]);
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        return false;
    }
}


    private function handleGetAllProducts($data) {
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

        // Base query - now including average rating
        $query = "SELECT p.*, 
                 COALESCE(AVG(pr.Rating), 0) as averageRating,
                 COUNT(pr.Rating) as ratingCount
                 FROM products p 
                 LEFT JOIN productratings pr ON p.ProductID = pr.PID
                 LEFT JOIN listings l ON p.ProductID=l.ProductID";
        $params = [];
        $whereConditions = [];
        
        // Handle search parameters
        if (isset($data['search']) && is_array($data['search'])) {
            $validSearchColumns = [
                'ProductID', 'Name', 'Description', 'Brand', 'Category', 
                'quantity', 'priceMin', 'priceMax', 'minRating'
            ];

            foreach ($data['search'] as $column => $value) {
                if (in_array($column, $validSearchColumns)) {
                    if ($column === "ProductID") {
                        $whereConditions[] = "p.ProductID = ?";
                        $params[] = $value;
                    } elseif ($column === "priceMin") {
                        $whereConditions[] = "l.price >= ?";
                        $params[] = $value;
                    } elseif ($column === "priceMax") {
                        $whereConditions[] = "l.price <= ?";
                        $params[] = $value;
                    } elseif ($column === "minRating") {
                        $whereConditions[] = "(
                            SELECT AVG(Rating) 
                            FROM productratings 
                            WHERE PID = p.ProductID
                        ) >= ?";
                        $params[] = $value;
                    } elseif ($column === "Name") {
                        // Fuzzy search for Name using LIKE with wildcards
                        $whereConditions[] = "p.Name LIKE ?";
                        $params[] = "%" . $value . "%";
                    } else {
                        // Default to exact match for other fields
                        $whereConditions[] = "p.$column = ?";
                        $params[] = $value;
                    }
                }
            }
        }
        
        // Add WHERE clause if we have conditions
        if (!empty($whereConditions)) {
            $query .= " WHERE " . implode(" AND ", $whereConditions);
        }
        
        // Group by product ID for the average rating calculation
        $query .= " GROUP BY p.ProductID";
        
        // Handle sorting
        if (isset($data['sort'])) {
            $validSortColumns = ['Name', 'averageRating', 'ratingCount', 'price'];
            $sort = $data['sort'];
            $order = isset($data['order']) && strtoupper($data['order']) === 'DESC' ? 'DESC' : 'ASC';
            
            if (in_array($sort, $validSortColumns)) {
                $query .= " ORDER BY $sort $order";
            }
        }
        
        // Handle limit
        $limit = isset($data['limit']) ? min(max(1, (int)$data['limit']), 800) : 50;
        $query .= " LIMIT " . (int)$limit;

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $this->sendSuccess($products);
    } catch (PDOException $e) {
        throw new Exception("Database error: " . $e->getMessage(), 500);
    }
}
private function handleUniversalCreate($data) {
    //  Check admin API key
    if (!isset($data['apikey']) || !$this->isAdmin($data['apikey'])) {
        throw new Exception("Admin access required", 403);
    }

    //  Validate table name
    if (!isset($data['table'])) {
        throw new Exception("Table parameter is required", 400);
    }

     // Define allowed tables and insertable fields
    $allowedTables = [
        'admin' => ['K', 'Privilege'],
        'carts' => ['K', 'PID', 'Quantity'],
        'categories' => ['RID', 'Category'],
        'listings' => ['ProductID', 'RID', 'quantity', 'price', 'remaining'],
        'orderitems' => ['OID', 'PID', 'RID', 'Quantity', 'UnitPrice'],
        'orders' => ['OrderID', 'K', 'OrderDate', 'Total'], // exclude OrderID if auto-increment
        'preferences' => ['K', 'Pref_Slot', 'Theme', 'Display_Name'],
        'productimgs' => ['PID', 'URL'],
        'productratings' => ['K', 'PID', 'Rating', 'Comment', 'Date'],
        'products' => ['ProductID', 'Name', 'Description', 'Brand', 'Category', 'Thumbnail'],
        'retailers' => ['RetailerID', 'Name', 'URL'],
        'users' => ['API_Key', 'Name', 'Surname', 'Password', 'Salt', 'Email', 'Phone_Number'],
        'wishlist' => ['K', 'PID']
    ];

    $table = $data['table'];
    if (!array_key_exists($table, $allowedTables)) {
        throw new Exception("Invalid table", 400);
    }

    //Validate values
    if (!isset($data['values']) || !is_array($data['values']) || empty($data['values'])) {
        throw new Exception("Values must be a non-empty object", 400);
    }

    //Filter to allowed columns
    $validFields = $allowedTables[$table];
    $insertFields = [];
    $insertValues = [];

    // Special handling for 'users' table
    if ($table === 'users') {
        if (!isset($data['values']['API_Key'])) {
            $data['values']['API_Key'] = $this->generateApiKey();
        }
        if (!isset($data['values']['Salt'])) {
            $data['values']['Salt'] = bin2hex(random_bytes(16));
        }
        if (!isset($data['values']['Password'])) {
            throw new Exception("Password is required for users", 400);
        }
        $data['values']['Password'] = hash('sha256', $data['values']['Password'] . $data['values']['Salt']);
    }

    foreach ($data['values'] as $field => $value) {
        if (!in_array($field, $validFields)) {
            throw new Exception("Field '{$field}' is not allowed in '{$table}'", 400);
        }
        $insertFields[] = $field;
        $insertValues[] = $value;
    }

    //Construct SQL query
    $columnsSql = implode(', ', $insertFields);
    $placeholdersSql = implode(', ', array_fill(0, count($insertFields), '?'));
    $sql = "INSERT INTO `$table` ($columnsSql) VALUES ($placeholdersSql)";

    //Execute safely
    try {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($insertValues);

        $this->sendSuccess([
            'message' => "Row inserted into {$table} successfully",
            'table' => $table,
            'inserted' => $data['values']
        ]);
    } catch (PDOException $e) {
        throw new Exception("Database error: " . $e->getMessage(), 500);
    }
}

private function handleCreateAdmin($data){
    $userApiKey = $data['values']['userApiKey'] ?? null;
    $privilege = $data['values']['privilege'] ?? null;

    if (!$userApiKey || !$privilege) {
        throw new Exception("userApiKey and privilege are required", 400);
    }

    $validPrivileges = ['Super Admin', 'Listings Admin', 'User Admin'];
    if (!in_array($privilege, $validPrivileges)) {
        throw new Exception("Invalid privilege. Must be 'Super Admin', 'Listings Admin', or 'User Admin'", 400);
    }

    try {
        $stmt = $this->db->prepare("SELECT API_Key FROM users WHERE API_Key = ?");
        $stmt->execute([$userApiKey]);
        if (!$stmt->fetch()) {
            throw new Exception("User not found", 404);
        }

        $stmt = $this->db->prepare("SELECT K FROM admin WHERE K = ?");
        $stmt->execute([$userApiKey]);
        if ($stmt->fetch()) {
            throw new Exception("User is already an admin", 409);
        }

        $stmt = $this->db->prepare("INSERT INTO admin (K, Privilege) VALUES (?, ?)");
        $stmt->execute([$userApiKey, $privilege]);

        $this->sendSuccess([
            'message' => 'Admin created successfully',
            'userApiKey' => $userApiKey,
            'privilege' => $privilege
        ]);

    } catch (PDOException $e) {
        throw new Exception("Database error: " . $e->getMessage(), 500);
    }
}

private function handleGetAdmin($data) {
    if (!isset($data['userApiKey'])) {
        throw new Exception("userApiKey parameter is required", 400);
    }
     // Check admin API key
    if (!isset($data['apikey']) || !$this->isAdmin($data['apikey'])) {
        throw new Exception("Admin access required", 403);
    }

    try {
        $userApiKey = $data['userApiKey'];
        $stmt = $this->db->prepare("
            SELECT a.K, a.Privilege, u.Name, u.Surname, u.Email 
            FROM admin a 
            JOIN users u ON a.K = u.API_Key 
            WHERE a.K = ?
        ");
        $stmt->execute([$userApiKey]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$admin) {
            throw new Exception("Admin not found", 404);
        }
        
        $this->sendSuccess($admin);
        
    } catch (PDOException $e) {
        throw new Exception("Database error: " . $e->getMessage(), 500);
    }
}

private function handleUniversalUpdate($data) {
    // Validate required fields
    if (!isset($data['table'])) {
        throw new Exception("table parameter is required", 400);
    }

    if (!isset($data['updates']) || !is_array($data['updates'])) {
        throw new Exception("updates parameter is required and must be an array", 400);
    }

    if (!isset($data['where']) || !is_array($data['where'])) {
        throw new Exception("where parameter is required and must be an array", 400);
    }
    //Check admin API key
    if (!isset($data['apikey']) || !$this->isAdmin($data['apikey'])) {
        throw new Exception("Admin access required", 403);
    }

    $table = $data['table'];
    $updates = $data['updates'];
    $where = $data['where'];

    // Define allowed tables and their primary keys
    $allowedTables = [
        'retailers' => ['RetailerID'],
        'listings' => ['ProductID', 'RID'],
        'categories' => ['RID', 'Category'],
        'orders' => ['OrderID'],
        'productratings' => ['K', 'PID'],
        'users' => ['API_Key'],
        'orderitems' => ['OID', 'PID', 'RID'],
        'products' => ['ProductID'],
        'preferences' => ['K', 'Pref_Slot']
    ];

    if (!array_key_exists($table, $allowedTables)) {
        throw new Exception("Invalid table. Allowed tables: " . implode(', ', array_keys($allowedTables)), 400);
    }

    try {
        // Special handling for user password update
        if ($table === 'users' && isset($updates['Password'])) {
            if (!isset($updates['Salt'])) {
                $updates['Salt'] = bin2hex(random_bytes(16));
            }
            $updates['Password'] = hash('sha256', $updates['Password'] . $updates['Salt']);
        }
        // Build SET clause
        $setParts = [];
        $setValues = [];
        foreach ($updates as $column => $value) {
            $setParts[] = "`$column` = ?";
            $setValues[] = $value;
        }
        $setClause = implode(', ', $setParts);

        // Build WHERE clause
        $whereParts = [];
        $whereValues = [];
        foreach ($where as $column => $value) {
            $whereParts[] = "`$column` = ?";
            $whereValues[] = $value;
        }
        $whereClause = implode(' AND ', $whereParts);

         // Combine values
        $allValues = array_merge($setValues, $whereValues);

        // Build and execute query
        $query = "UPDATE `$table` SET $setClause WHERE $whereClause";
        $stmt = $this->db->prepare($query);
        $stmt->execute($allValues);

        $affectedRows = $stmt->rowCount();

        if ($affectedRows === 0) {
            throw new Exception("No records found matching the criteria or no changes made", 404);
        }

        $this->sendSuccess([
            'message' => 'Update successful',
            'table' => $table,
            'affectedRows' => $affectedRows,
            'updates' => $updates,
            'where' => $where
        ]);

    } catch (PDOException $e) {
        throw new Exception("Database error: " . $e->getMessage(), 500);
    }
}

private function handleUniversalDelete($data) {
   // Validate required fields
    if (!isset($data['table'])) {
        throw new Exception("table parameter is required", 400);
    }
    
    if (!isset($data['where']) || !is_array($data['where'])) {
        throw new Exception("where parameter is required and must be an array", 400);
    }

     //Check admin API key
    if (!isset($data['apikey']) || !$this->isAdmin($data['apikey'])) {
        throw new Exception("Admin access required", 403);
    }
    
    $table = $data['table'];
    $where = $data['where'];
    
    // Define allowed tables
    $allowedTables = [
        'admin', 'users', 'products', 'retailers', 'listings', 
        'carts', 'wishlist', 'productratings', 'productimgs', 
        'purchases', 'preferences'
    ];
    
    if (!in_array($table, $allowedTables)) {
        throw new Exception("Invalid table. Allowed tables: " . implode(', ', $allowedTables), 400);
    }
    
    // Prevent self-deletion from admin table
    if ($table === 'admin' && isset($where['K']) && $where['K'] === $data['apikey']) {
        throw new Exception("Cannot delete your own admin privileges", 403);
    }
    
    try {
        // Build WHERE clause
        $whereParts = [];
        $whereValues = [];
        foreach ($where as $column => $value) {
            $whereParts[] = "`$column` = ?";
            $whereValues[] = $value;
        }
        $whereClause = implode(' AND ', $whereParts);
        
        // Build and execute query
        $query = "DELETE FROM `$table` WHERE $whereClause";
        $stmt = $this->db->prepare($query);
        $stmt->execute($whereValues);
        
        $affectedRows = $stmt->rowCount();
        
        if ($affectedRows === 0) {
            throw new Exception("No records found matching the criteria", 404);
        }
        
        $this->sendSuccess([
            'message' => 'Delete successful',
            'table' => $table,
            'affectedRows' => $affectedRows,
            'where' => $where
        ]);
        
    } catch (PDOException $e) {
        throw new Exception("Database error: " . $e->getMessage(), 500);
    }
}

private function handleListAdmins($data) {
     // Check admin API key
    if (!isset($data['apikey']) || !$this->isAdmin($data['apikey'])) {
        throw new Exception("Admin access required", 403);
    }
    try {
        // Set limit with bounds 
        $limit = isset($data['limit']) ? min(max(1, (int)$data['limit']), 50) : 20;
        $offset = isset($data['offset']) ? max(0, (int)$data['offset']) : 0;
        
        // Get total count
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM admin");
        $stmt->execute();
        $totalCount = $stmt->fetch()['total'];
        
        // Get admins with user information
        $stmt = $this->db->prepare("
            SELECT a.K, a.Privilege, u.Name, u.Surname, u.Email 
            FROM admin a 
            JOIN users u ON a.K = u.API_Key 
            ORDER BY a.Privilege ASC, u.Name ASC 
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $this->sendSuccess([
            'admins' => $admins,
            'pagination' => [
                'total' => (int)$totalCount,
                'limit' => $limit,
                'offset' => $offset,
                'hasMore' => ($offset + $limit) < $totalCount
            ]
        ]);
        
    } catch (PDOException $e) {
        throw new Exception("Database error: " . $e->getMessage(), 500);
    }
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
            'columns' => [
                'products.ProductID', 'Name', 'Description', 'Brand', 'Category', 'Thumbnail',
                'COALESCE(AVG(productratings.Rating), 0) as averageRating',
                'COUNT(productratings.Rating) as ratingCount'
            ],
            'filterable' => ['Brand', 'Category'],
            'joins' => [
                'productratings' => ['products.ProductID' => 'productratings.PID']
            ],
            'sortable' => ['averageRating', 'ratingCount', 'Name', 'Brand', 'Category'],
            'idFields' => ['products.ProductID'] // Specify which fields are IDs
        ],
        'retailers' => [
            'fields' => ['retailers.RetailerID', 'Name'],
            'columns' => ['retailers.RetailerID', 'Name', 'URL'],
            'idFields' => ['retailers.RetailerID']
        ],
        'productratings' => [
            'fields' => ['productratings.K', 'productratings.PID', 'Rating', 'Date'],
            'columns' => ['productratings.K', 'productratings.PID', 'Rating', 'Comment', 'Date'],
            'idFields' => ['productratings.K', 'productratings.PID']
        ],
        'listings' => [
            'fields' => ['listings.ProductID', 'listings.RID', 'quantity', 'price', 'remaining'],
            'columns' => ['listings.ProductID', 'listings.RID', 'quantity', 'price', 'remaining'],
            'joins' => [
                'products' => ['listings.ProductID' => 'products.ProductID'],
                'retailers' => ['listings.RID' => 'retailers.RetailerID']
            ],
            'sortable' => ['price'],
            'idFields' => ['listings.ProductID', 'listings.RID']
        ],
        'orders' => [
            'fields' => ['orders.OrderID', 'orders.K', 'OrderDate', 'Total'],
            'columns' => ['orders.OrderID', 'orders.K', 'OrderDate', 'Total'],
            'joins' => [
                'users' => ['orders.K' => 'users.API_Key']
            ],
            'idFields' => ['orders.OrderID', 'orders.K']
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

    // Add filter conditions if provided
    if (isset($data['filter']) && is_array($data['filter'])) {
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
        
        // Check if this is an ID field (exact match) or regular field (fuzzy search)
        if (isset($tableConfig['idFields']) && in_array($data['field'], $tableConfig['idFields'])) {
            // Exact match for ID fields
            $whereConditions[] = "{$data['field']} = :search";
            $params[':search'] = $data['search'];
        } else {
            // Fuzzy search for non-ID fields
            $searchTerm = "%{$data['search']}%";
            $whereConditions[] = "{$data['field']} LIKE :search";
            $params[':search'] = $searchTerm;
        }
    } elseif (isset($data['field']) || isset($data['search'])) {
        throw new Exception("Both field and search parameters must be provided together", 400);
    }

    // Add min rating filter for products
    if ($table === 'products' && isset($data['minRating'])) {
        $whereConditions[] = "(
            SELECT AVG(Rating) 
            FROM productratings 
            WHERE PID = products.ProductID
        ) >= :minRating";
        $params[':minRating'] = (float)$data['minRating'];
    }

    // Combine all WHERE conditions
    if (!empty($whereConditions)) {
        $query .= " WHERE " . implode(' AND ', $whereConditions);
    }

    // Group by for products table to calculate averages
    if ($table === 'products') {
        $query .= " GROUP BY products.ProductID";
    }

    // Handle sorting
    $orderBy = '';
    if (isset($data['sort']) && in_array($data['sort'], $tableConfig['sortable'] ?? [])) {
        $order = isset($data['order']) && strtoupper($data['order']) === 'DESC' ? 'DESC' : 'ASC';
        $orderBy = " ORDER BY {$data['sort']} $order";
    }

    // Final SELECT
    $selectClause = "SELECT $columns";

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

        try {
            $stmt = $this->db->prepare("SELECT 1 FROM users WHERE `API_Key` = ?");
            $stmt->execute([trim($apiKey)]);
            return (bool)$stmt->fetch();
        } catch (PDOException $e) {
            error_log("API key validation failed: " . $e->getMessage());
            return false;
        }
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
