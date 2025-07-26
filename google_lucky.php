<?php
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

// Get API credentials from environment variables
$apiKey = getenv('GOOGLE_API_KEY');
$cx = getenv('GOOGLE_CSE_ID');

// Check if required environment variables are set
if (!$apiKey || !$cx) {
    http_response_code(500);
    echo "Error: Google API credentials not configured. Please set GOOGLE_API_KEY and GOOGLE_CSE_ID environment variables in your .env file or server configuration.";
    exit;
}

// Get query parameter from URL
$query = $_GET['query'] ?? '';

if (empty($query)) {
    http_response_code(400);
    echo "Please provide a search query in the URL like ?query=your+search+terms";
    exit;
}

// Build the Google Custom Search API URL
$searchUrl = "https://www.googleapis.com/customsearch/v1?key=" . urlencode($apiKey) . "&cx=" . urlencode($cx) . "&q=" . urlencode($query);

// Make the API request with better error handling and server-side proxy
$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'user_agent' => 'GoogleLuckySearch/1.0',
        'ignore_errors' => true, // This allows us to get the response even on HTTP errors
        // Remove referer header to bypass referer restrictions
        'header' => [
            'Accept: application/json',
            'Content-Type: application/json'
        ]
    ]
]);

$response = file_get_contents($searchUrl, false, $context);

if ($response === false) {
    http_response_code(500);
    echo "Error: Failed to connect to Google API. Please check your internet connection.";
    exit;
}

// Get HTTP response headers to check status code
$httpResponseHeader = $http_response_header ?? [];
$statusLine = $httpResponseHeader[0] ?? '';
$statusCode = 0;

if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $statusLine, $matches)) {
    $statusCode = (int)$matches[1];
}

// Check for HTTP errors
if ($statusCode >= 400) {
    $errorData = json_decode($response, true);
    $errorMessage = "HTTP Error $statusCode: ";
    
    if ($statusCode === 403) {
        $errorMessage .= "Access Forbidden. This usually means:\n";
        $errorMessage .= "1. Your API key is invalid or disabled\n";
        $errorMessage .= "2. The Custom Search API is not enabled for your project\n";
        $errorMessage .= "3. Your API key doesn't have permission to access the Custom Search API\n";
        $errorMessage .= "4. You've exceeded your API quota\n";
        $errorMessage .= "5. Referer restrictions are blocking your requests\n\n";
        $errorMessage .= "Please check:\n";
        $errorMessage .= "- Google Cloud Console: https://console.cloud.google.com/apis/credentials\n";
        $errorMessage .= "- Enable Custom Search API: https://console.cloud.google.com/apis/library/customsearch.googleapis.com\n";
        $errorMessage .= "- Check API quotas: https://console.cloud.google.com/apis/api/customsearch.googleapis.com/quotas\n";
        $errorMessage .= "- Remove referer restrictions or add your domain to allowed referers";
    } elseif ($statusCode === 400) {
        $errorMessage .= "Bad Request. Check your Custom Search Engine ID.";
    } else {
        $errorMessage .= "Unknown error occurred.";
    }
    
    if (isset($errorData['error']['message'])) {
        $errorMessage .= "\n\nGoogle API Error: " . $errorData['error']['message'];
    }
    
    http_response_code($statusCode);
    echo $errorMessage;
    exit;
}

$data = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo "Error parsing API response: " . json_last_error_msg();
    exit;
}

// Check if we have results
if (isset($data['items']) && count($data['items']) > 0) {
    $firstResult = $data['items'][0];
    $redirectUrl = $firstResult['link'];
    
    // Redirect to the first result
    header("Location: " . $redirectUrl);
    exit;
} else {
    http_response_code(404);
    echo "No results found for: " . htmlspecialchars($query);
    exit;
}
?> 