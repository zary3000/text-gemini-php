<?php
/**
 * Gemini API Weather Query Script
 * Sends a question to Google's Gemini API and prints the response
 */

// Configuration
$apiKey = 'AIzaSyCExc18KwbZa2_AV3X_25nN095P4S50n2U';
$model = 'gemini-1.5-flash';
$question = 'How is the weather today?';

// API endpoint
$url = "https://generativelanguage.googleapis.com/v1/models/{$model}:generateContent?key={$apiKey}";
// Prepare the request payload
$payload = [
    'contents' => [
        [
            'parts' => [
                ['text' => $question]
            ]
        ]
    ]
];

// Initialize cURL
$ch = curl_init($url);

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

// Execute the request
echo "Sending request to Gemini API...\n";
echo "Question: {$question}\n";
echo str_repeat('-', 50) . "\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Check for cURL errors
if (curl_errno($ch)) {
    echo "cURL Error: " . curl_error($ch) . "\n";
    curl_close($ch);
    exit(1);
}

curl_close($ch);

// Check HTTP response code
if ($httpCode !== 200) {
    echo "API Error (HTTP {$httpCode}):\n";
    echo $response . "\n";
    exit(1);
}

// Parse the response
$responseData = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "JSON Parse Error: " . json_last_error_msg() . "\n";
    echo "Raw Response: " . $response . "\n";
    exit(1);
}

// Extract and display the text response
if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
    $answer = $responseData['candidates'][0]['content']['parts'][0]['text'];
    echo "Gemini Response:\n";
    echo str_repeat('-', 50) . "\n";
    echo $answer . "\n";
} else {
    echo "Unexpected response structure:\n";
    echo json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
}

// Store the full response in a variable for potential further use
$fullResponse = $responseData;

echo str_repeat('-', 50) . "\n";
echo "Done!\n";
?>

