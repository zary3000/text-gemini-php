<?php
/**
 * List Available Gemini Models
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$apiKey = 'AIzaSyCExc18KwbZa2_AV3X_25nN095P4S50n2U';
$url = "https://generativelanguage.googleapis.com/v1/models?key={$apiKey}";

echo "Fetching available models...\n";
echo str_repeat('-', 50) . "\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo "cURL Error: " . curl_error($ch) . "\n";
    curl_close($ch);
    exit(1);
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "API Error (HTTP {$httpCode}):\n";
    echo $response . "\n";
    exit(1);
}

$data = json_decode($response, true);

if (isset($data['models'])) {
    echo "Available models:\n\n";
    foreach ($data['models'] as $model) {
        echo "Name: " . $model['name'] . "\n";
        if (isset($model['supportedGenerationMethods'])) {
            echo "  Supported methods: " . implode(', ', $model['supportedGenerationMethods']) . "\n";
        }
        echo "\n";
    }
} else {
    echo "Unexpected response:\n";
    echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
}
?>
