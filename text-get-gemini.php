<?php
header('Content-Type: text/html; charset=utf-8');

// === CONFIGURATION ===
$apiKey = 'AIzaSyCExc18KwbZa2_AV3X_25nN095P4S50n2U';          // ← Replace with your real key
$model  = 'gemini-1.5-flash';                  // or gemini-1.5-pro, gemini-2.0-flash etc.

// Get your key → https://aistudio.google.com/app/apikey

// Question we always ask (you can make it dynamic later)
$question = "How is the weather today in Luxembourg? Answer concisely in one sentence.";

// === Prepare the payload for Gemini API ===
$payload = [
    'contents' => [
        [
            'parts' => [
                ['text' => $question]
            ]
        ]
    ]
];

$jsonPayload = json_encode($payload);

// === API endpoint ===
$url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

// === Make the cURL request ===
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

// Optional: increase timeout if needed
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    $error = curl_error($ch);
    curl_close($ch);
    die("cURL error: $error");
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// === Handle response ===
if ($httpCode !== 200) {
    http_response_code($httpCode);
    echo "<h2>Error from Gemini API (HTTP $httpCode)</h2>";
    echo "<pre>";
    var_dump($response);
    echo "</pre>";
    exit;
}

$data = json_decode($response, true);

// Extract the generated text (path can slightly change between models/versions)
$answer = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'No answer received';

$cleanAnswer = trim($answer);

// === Display result ===
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gemini Weather Answer</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 2rem auto; padding: 1rem; line-height: 1.6; }
        h1   { color: #1a73e8; }
        .answer { background: #f8f9fa; border-left: 5px solid #1a73e8; padding: 1rem; margin: 1.5rem 0; white-space: pre-wrap; }
        .info  { color: #555; font-size: 0.9rem; }
    </style>
</head>
<body>

<h1>Asked Gemini: "How is the weather today?"</h1>

<div class="answer">
<strong>Gemini answer:</strong><br><br>
<?= htmlspecialchars($cleanAnswer) ?>
</div>

<p class="info">
    Asked on: <?= date('Y-m-d H:i:s T') ?><br>
    Model used: <?= htmlspecialchars($model) ?>
</p>

<?php
// === Store the answer in a file (append mode) ===
$logFile = 'gemini_weather_answer.txt';
$logLine = sprintf(
    "[%s] Question: %s\nAnswer: %s\n----------------------------------------\n",
    date('Y-m-d H:i:s'),
    $question,
    $cleanAnswer
);

file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);

if (file_exists($logFile)) {
    echo "<p class='info'>Answer also saved to: <code>$logFile</code> (visible if server allows directory listing or you download it)</p>";
} else {
    echo "<p class='info' style='color:red;'>Could not write to log file (check folder permissions).</p>";
}
?>

</body>

</html>
