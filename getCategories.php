<?php
$API_URL = 'http://localhost:8001/api.php';
$API_KEY = 'a1b2c3d4e5';

function fetchCategories() {
    global $API_URL, $API_KEY;

    $data = [
        "api" => "getAllCategories",
    "apikey" => "a1b2c3d4e5",
    "limit" => 20
    ];

    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($data),
            'ignore_errors' => true,
        ],
    ];

    $context = stream_context_create($options);
    $result = @file_get_contents($API_URL, false, $context);

    if ($result === FALSE) {
        $error = error_get_last();
        die("API Request Failed: " . $error['message']);
    }

    $response = json_decode($result, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "Invalid JSON:\n";
        echo $result;
        die("\n\nDecode error: " . json_last_error_msg());
    }

    return $response;
}
?>
