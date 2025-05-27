<?php
$API_URL = 'http://localhost:8001/api.php';
$API_KEY = 'a1b2c3d4e5';

function fetchUsers() {
    global $API_URL, $API_KEY;

    $data = [
         "api" => "getAllUsers",
    "apikey" => "a1b2c3d4e5"
    ];

    $options = [
        'http' => [
            'header'  => "Content-Type: application/json",
            'method'  => 'POST',
            'content' => json_encode($data),
        ],
    ];

    $context = stream_context_create($options);
    $result = @file_get_contents($API_URL, false, $context);

    if ($result === FALSE) {
        $error = error_get_last();
        die("file_get_contents failed: " . $error['message']);
    }

    // if not valid json
    $response = json_decode($result, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "Raw response:";
        echo $result;
        die("JSON decode error: " . json_last_error_msg());
    }
    return $response;
}
