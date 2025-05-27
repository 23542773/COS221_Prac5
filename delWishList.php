<?php
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['ProductID']) || !is_numeric($input['ProductID'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid or missing ProductID"
    ]);
    exit;
}

$API_URL = 'http://localhost:8001/api.php';
$API_KEY = 'a1b2c3d4e5';

$data = [
    "api" => "wishlist",
    "apikey" => $API_KEY,
    "operation" => "unset",
    "ProductID" => $input['ProductID']
];

$options = [
    'http' => [
        'header'  => "Content-Type: application/json",
        'method'  => 'POST',
        'content' => json_encode($data)
    ]
];

$context = stream_context_create($options);
$result = @file_get_contents($API_URL, false, $context);

if ($result === FALSE) {
    $error = error_get_last();
    echo json_encode([
        "status" => "error",
        "message" => "API request failed: " . $error['message']
    ]);
    exit;
}

$response = json_decode($result, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid response from API"
    ]);
    exit;
}
echo json_encode($response);
?>
