<?php

// Simple API test for track operations

$trackId = 713; // The ID we're trying to test
$apiUrl = "http://" . $_SERVER['HTTP_HOST'] . "/api/tracks/{$trackId}/stop";

// Make cURL request
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, []);
curl_setopt($ch, CURLOPT_HEADER, true);

// Execute request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

curl_close($ch);

// Output the response
header('Content-Type: application/json');
echo json_encode([
    'api_url' => $apiUrl,
    'status_code' => $httpCode,
    'response_headers' => $headers,
    'response_body' => $body ? json_decode($body) : null,
], JSON_PRETTY_PRINT); 