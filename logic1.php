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

function handleGetAllProducts($data) {
    global $response;
    
    // Validate API key
    if (!validateApiKey($data['api'])) {
        $response['message'] = 'Invalid API key';
        echo json_encode($response);
        return;
    }

    // Initialize base query with joins for prices, retailers, and reviews
    $query = "SELECT 
                p.id, 
                p.name, 
                p.description,
                p.category_id,
                p.brand_id,
                GROUP_CONCAT(DISTINCT r.id) AS retailer_ids,
                GROUP_CONCAT(DISTINCT r.name) AS retailers,
                GROUP_CONCAT(pr.price ORDER BY pr.price ASC) AS prices,
                AVG(rv.rating) AS avg_rating
              FROM products p
              LEFT JOIN prices pr ON p.id = pr.product_id
              LEFT JOIN retailers r ON pr.retailer_id = r.id
              LEFT JOIN reviews rv ON p.id = rv.product_id
              WHERE 1=1";

    $params = [];
    $allowedFilters = [
        'category' => 'p.category_id',
        'brand' => 'p.brand_id',
        'min_price' => 'pr.price >=',
        'max_price' => 'pr.price <=',
        'retailer' => 'r.id',
        'min_rating' => 'HAVING AVG(rv.rating) >='
    ];

    // Add filter conditions
    foreach ($allowedFilters as $param => $condition) {
        if (isset($data[$param])) {
            // Handle special case for rating (HAVING clause)
            if ($param === 'min_rating') {
                $query .= " HAVING AVG(rv.rating) >= :min_rating";
                $params[':min_rating'] = (float)$data['min_rating'];
                continue;
            }

            // Handle array parameters (categories, brands, retailers)
            if (is_array($data[$param])) {
                $placeholders = implode(',', array_map(fn($i) => ":{$param}_$i", array_keys($data[$param])));
                $query .= " AND $condition IN ($placeholders)";
                foreach ($data[$param] as $i => $value) {
                    $params[":{$param}_$i"] = $value;
                }
            } else {
                $query .= " AND $condition :$param";
                $params[":$param"] = $data[$param];
            }
        }
    }

    // Handle search
    if (!empty($data['search'])) {
        $searchTerm = "%{$data['search']}%";
        $query .= " AND (p.name LIKE :search OR p.description LIKE :search)";
        $params[':search'] = $searchTerm;
    }

    // Handle sorting
    $validSortFields = [
        'price' => 'MIN(pr.price)',
        'rating' => 'avg_rating',
        'popularity' => '(SELECT COUNT(*) FROM reviews WHERE product_id = p.id)'
    ];
    
    if (!empty($data['sort']) && isset($validSortFields[$data['sort']])) {
        $order = strtoupper($data['order'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
        $query .= " ORDER BY {$validSortFields[$data['sort']]} $order";
    }

    // Pagination
    $limit = isset($data['limit']) ? min(max(1, (int)$data['limit']), 800) : 50;
    $page = isset($data['page']) ? max(1, (int)$data['page']) : 1;
    $offset = ($page - 1) * $limit;
    $query .= " LIMIT :limit OFFSET :offset";
    $params[':limit'] = $limit;
    $params[':offset'] = $offset;

    // Execute query with prepared statement
    try {
        $stmt = $pdo->prepare($query);
        foreach ($params as $key => &$val) {
            $type = is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $val, $type);
        }
        
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process price data
        foreach ($products as &$product) {
            $product['prices'] = array_map('floatval', explode(',', $product['prices']));
            $product['retailers'] = explode(',', $product['retailers']);
            $product['avg_rating'] = round((float)$product['avg_rating'], 1);
        }

        $response['status'] = 'success';
        $response['data'] = $products;
    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }

    echo json_encode($response);
}

function handleGetAllRetailers($data) {
    global $response;
    
    if (!validateApiKey($data['api'])) {
        $response['message'] = 'Invalid API key';
        echo json_encode($response);
        return;
    }

    $query = "SELECT 
                r.id, 
                r.name, 
                r.country,
                COUNT(DISTINCT pr.product_id) AS product_count,
                AVG(rv.rating) AS avg_rating
              FROM retailers r
              LEFT JOIN prices pr ON r.id = pr.retailer_id
              LEFT JOIN reviews rv ON pr.product_id = rv.product_id
              WHERE 1=1";

    $params = [];
    $allowedFilters = [
        'country' => 'r.country',
        'product' => 'pr.product_id',
        'min_rating' => 'HAVING AVG(rv.rating) >='
    ];

    foreach ($allowedFilters as $param => $condition) {
        if (isset($data[$param])) {
            if ($param === 'min_rating') {
                $query .= " HAVING AVG(rv.rating) >= :min_rating";
                $params[':min_rating'] = (float)$data['min_rating'];
                continue;
            }

            $query .= " AND $condition = :$param";
            $params[":$param"] = $data[$param];
        }
    }

    // Sorting
    $validSortFields = ['name', 'product_count', 'avg_rating'];
    if (!empty($data['sort']) && in_array($data['sort'], $validSortFields)) {
        $order = strtoupper($data['order'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
        $query .= " ORDER BY {$data['sort']} $order";
    }

    // Pagination
    $limit = isset($data['limit']) ? min(max(1, (int)$data['limit']), 20) : 10;
    $query .= " LIMIT :limit";
    $params[':limit'] = $limit;

    try {
        $stmt = $pdo->prepare($query);
        foreach ($params as $key => &$val) {
            $type = is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $val, $type);
        }
        
        $stmt->execute();
        $retailers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response['status'] = 'success';
        $response['data'] = array_map(function($ret) {
            $ret['avg_rating'] = round((float)$ret['avg_rating'], 1);
            return $ret;
        }, $retailers);
    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }

    echo json_encode($response);
}

function handleGetDistinct($data) {
    global $response;
    
    if (!validateApiKey($data['api'])) {
        $response['message'] = 'Invalid API key';
        echo json_encode($response);
        return;
    }

    $validFields = [
        'brand' => 'brand_id', 
        'category' => 'category_id',
        'country' => 'country_of_origin'
    ];
    
    if (empty($data['field']) || !isset($validFields[$data['field']])) {
        $response['message'] = 'Invalid field parameter';
        echo json_encode($response);
        return;
    }

    $field = $validFields[$data['field']];
    $query = "SELECT DISTINCT $field AS value FROM products WHERE 1=1";
    $params = [];

    // Apply additional filters
    if (!empty($data['filters'])) {
        foreach ($data['filters'] as $filter => $value) {
            if (in_array($filter, ['category', 'brand'])) {
                $query .= " AND $validFields[$filter] = :$filter";
                $params[":$filter"] = $value;
            }
        }
    }

    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $response['status'] = 'success';
        $response['data'] = $results;
    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }

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