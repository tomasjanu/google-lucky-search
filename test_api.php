<?php
// Test script to verify Google API credentials
// Load environment variables from .env file if it exists
function loadEnv($path) {
    if (!file_exists($path)) {
        return false;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) {
            continue; // Skip comments
        }
        
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present
            if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                $value = substr($value, 1, -1);
            }
            
            if (!getenv($key)) {
                putenv("$key=$value");
            }
        }
    }
    return true;
}

// Try to load from .env file
loadEnv(__DIR__ . '/.env');

// Get API credentials
$apiKey = getenv('GOOGLE_API_KEY');
$cx = getenv('GOOGLE_CSE_ID');

echo "<h1>Google API Credentials Test</h1>\n";

// Check if credentials are loaded
if (!$apiKey) {
    echo "<p style='color: red;'>❌ GOOGLE_API_KEY not found in environment variables</p>\n";
    echo "<p>Make sure you have a .env file with: GOOGLE_API_KEY=your_api_key_here</p>\n";
    exit;
}

if (!$cx) {
    echo "<p style='color: red;'>❌ GOOGLE_CSE_ID not found in environment variables</p>\n";
    echo "<p>Make sure you have a .env file with: GOOGLE_CSE_ID=your_search_engine_id_here</p>\n";
    exit;
}

echo "<p style='color: green;'>✅ API Key loaded: " . substr($apiKey, 0, 10) . "...</p>\n";
echo "<p style='color: green;'>✅ Search Engine ID loaded: $cx</p>\n";

// Test the API
$testQuery = "test";
$searchUrl = "https://www.googleapis.com/customsearch/v1?key=" . urlencode($apiKey) . "&cx=" . urlencode($cx) . "&q=" . urlencode($testQuery);

echo "<h2>Testing API Connection...</h2>\n";
echo "<p>Testing with query: '$testQuery'</p>\n";

$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'user_agent' => 'GoogleLuckySearch/1.0',
        'ignore_errors' => true
    ]
]);

$response = file_get_contents($searchUrl, false, $context);

if ($response === false) {
    echo "<p style='color: red;'>❌ Failed to connect to Google API</p>\n";
    exit;
}

// Get HTTP status
$httpResponseHeader = $http_response_header ?? [];
$statusLine = $httpResponseHeader[0] ?? '';
$statusCode = 0;

if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $statusLine, $matches)) {
    $statusCode = (int)$matches[1];
}

echo "<p>HTTP Status Code: $statusCode</p>\n";

if ($statusCode === 200) {
    $data = json_decode($response, true);
    if (isset($data['items']) && count($data['items']) > 0) {
        echo "<p style='color: green;'>✅ API test successful! Found " . count($data['items']) . " results.</p>\n";
        echo "<p>First result: " . htmlspecialchars($data['items'][0]['title']) . "</p>\n";
        echo "<p>URL: " . htmlspecialchars($data['items'][0]['link']) . "</p>\n";
    } else {
        echo "<p style='color: orange;'>⚠️ API responded successfully but no results found</p>\n";
    }
} else {
    echo "<p style='color: red;'>❌ API test failed with status code: $statusCode</p>\n";
    
    $errorData = json_decode($response, true);
    if (isset($errorData['error']['message'])) {
        echo "<p>Error message: " . htmlspecialchars($errorData['error']['message']) . "</p>\n";
    }
    
    if ($statusCode === 403) {
        echo "<h3>Troubleshooting 403 Error:</h3>\n";
        echo "<ul>\n";
        echo "<li>Check if your API key is valid at: <a href='https://console.cloud.google.com/apis/credentials' target='_blank'>Google Cloud Console</a></li>\n";
        echo "<li>Enable the Custom Search API: <a href='https://console.cloud.google.com/apis/library/customsearch.googleapis.com' target='_blank'>Enable API</a></li>\n";
        echo "<li>Check your API quotas: <a href='https://console.cloud.google.com/apis/api/customsearch.googleapis.com/quotas' target='_blank'>Check Quotas</a></li>\n";
        echo "<li>Verify your Custom Search Engine ID is correct</li>\n";
        echo "</ul>\n";
    }
}

echo "<h2>Next Steps:</h2>\n";
echo "<p>If the test is successful, you can use your script at: <code>google_lucky.php?query=your+search+terms</code></p>\n";
?> 