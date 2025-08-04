<?php
// Test file to debug the update API
header('Content-Type: application/json');

echo json_encode([
    'method' => $_SERVER['REQUEST_METHOD'],
    'request_uri' => $_SERVER['REQUEST_URI'],
    'query_string' => $_SERVER['QUERY_STRING'],
    'php_input' => file_get_contents('php://input'),
    'get_params' => $_GET,
    'post_params' => $_POST,
    'headers' => getallheaders()
]);
?>
