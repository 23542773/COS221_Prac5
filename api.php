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
                 case 'admin':
                    $this->handlAdmin($data);
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

    private function handlAdmin($data) {
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
    // CREATE: Add new admin
private function handleUniversalCreate($data) {
    //  1. Check admin API key
    if (!isset($data['apikey']) || !$this->isAdmin($data['apikey'])) {
        throw new Exception("Admin access required", 403);
    }

    //  2. Validate table name
    if (!isset($data['table'])) {
        throw new Exception("Table parameter is required", 400);
    }

    //  3. Define allowed tables and insertable fields
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

    // 4. Validate values
    if (!isset($data['values']) || !is_array($data['values']) || empty($data['values'])) {
        throw new Exception("Values must be a non-empty object", 400);
    }

    //  5. Filter to allowed columns
    $validFields = $allowedTables[$table];
    $insertFields = [];
    $insertValues = [];

    foreach ($data['values'] as $field => $value) {
        if (!in_array($field, $validFields)) {
            throw new Exception("Field '{$field}' is not allowed in '{$table}'", 400);
        }
        $insertFields[] = $field;
        $insertValues[] = $value;
    }

    //  6. Construct SQL query
    $columnsSql = implode(', ', $insertFields);
    $placeholdersSql = implode(', ', array_fill(0, count($insertFields), '?'));
    $sql = "INSERT INTO `$table` ($columnsSql) VALUES ($placeholdersSql)";

    //   7. Execute safely
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

// READ: Get specific admin by API key
private function handleGetAdmin($data) {
    if (!isset($data['userApiKey'])) {
        throw new Exception("userApiKey parameter is required", 400);
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
        //fetches the next row from the result set of a PDO statement as an associative array.
        
        if (!$admin) {
            throw new Exception("Admin not found", 404);
        }
        
        $this->sendSuccess($admin);
        
    } catch (PDOException $e) {
        throw new Exception("Database error: " . $e->getMessage(), 500);
    }
}

// UPDATE:universal update 
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
    
    $table = $data['table'];
    $updates = $data['updates'];
    $where = $data['where'];
    
    // Define allowed tables and their primary keys/identifiers
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

// DELETE: ability to delete an anything in the database
private function handleUniversalDelete($data) {
   // Validate required fields
    if (!isset($data['table'])) {
        throw new Exception("table parameter is required", 400);
    }
    
    if (!isset($data['where']) || !is_array($data['where'])) {
        throw new Exception("where parameter is required and must be an array", 400);
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

// LIST: Get all admins
private function handleListAdmins($data) {
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
            'columns' => ['products.ProductID', 'Name', 'Description', 'Brand', 'Category', 'Thumbnail']
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
            ]
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

    // Add search condition if applicable
    $whereClause = '';
    if (isset($data['field']) && isset($data['search'])) {
        if (!in_array($data['field'], $tableConfig['fields'])) {
            throw new Exception("Invalid field for the specified table", 400);
        }
        $searchTerm = "%{$data['search']}%";
        $whereClause = " WHERE {$data['field']} LIKE :search";
        $params[':search'] = $searchTerm;
    } elseif (isset($data['field']) || isset($data['search'])) {
        throw new Exception("Both field and search parameters must be provided together", 400);
    }

    // Final SELECT with DISTINCT if no filtering
    $selectClause = isset($data['field']) && isset($data['search'])
        ? "SELECT $columns"
        : "SELECT DISTINCT $columns";

    // Compose final query
    $finalQuery = "$selectClause $query $whereClause LIMIT :limit";
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
