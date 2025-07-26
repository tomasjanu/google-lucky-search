# Google Lucky Search

A PHP script that implements Google's "I'm Feeling Lucky" functionality using the Google Custom Search API. This script redirects users directly to the first search result for their query.

## Features

- Direct redirect to the first Google search result
- Secure API key management using environment variables
- Built-in `.env` file support for easy configuration
- Comprehensive error handling for missing queries and API failures
- Compatible with Chrome search engine shortcuts
- Includes a test script to verify API credentials

## Setup

### 1. Get Google API Credentials

1. **Google API Key**: 
   - Go to [Google Cloud Console](https://console.cloud.google.com/apis/credentials)
   - Create a new project or select an existing one
   - Enable the Custom Search API
   - Create credentials (API Key)

2. **Custom Search Engine ID**:
   - Go to [Google Custom Search](https://cse.google.com/cse/)
   - Create a new search engine
   - Note your Search Engine ID (cx parameter)

### 2. Configure Environment Variables

Create a `.env` file in the same directory as the script and add your credentials:

```bash
GOOGLE_API_KEY=your_actual_api_key_here
GOOGLE_CSE_ID=your_actual_search_engine_id_here
```

The script automatically loads environment variables from the `.env` file if it exists. If the `.env` file is not found, it will fall back to server environment variables.

### 3. Test Your Configuration

Before using the main script, test your API credentials:

```bash
php test_api.php
```

This will verify that your API key and search engine ID are working correctly and provide troubleshooting information if there are any issues.

### 4. Server Configuration

Make sure your web server is configured to:
- Execute PHP files
- Read environment variables from `.env` file (or set them in your server configuration)

For Apache, you can add this to your `.htaccess` file:
```apache
SetEnv GOOGLE_API_KEY your_api_key_here
SetEnv GOOGLE_CSE_ID your_search_engine_id_here
```

For Nginx, add to your server block:
```nginx
fastcgi_param GOOGLE_API_KEY "your_api_key_here";
fastcgi_param GOOGLE_CSE_ID "your_search_engine_id_here";
```

## Usage

### Direct URL Access
```
https://yourdomain.com/google_lucky.php?query=your+search+terms
```

### Chrome Search Engine Setup

1. Open Chrome Settings
2. Go to "Search engine" → "Manage search engines and site search"
3. Click "Add" and configure:
   - **Search engine**: Google Lucky
   - **Shortcut**: `@l` (or any shortcut you prefer)
   - **URL**: `https://yourdomain.com/google_lucky.php?query=%s`

### Example Usage
- Search: `@lucky how to make coffee`
- URL: `https://yourdomain.com/google_lucky.php?query=how+to+make+coffee`
- Result: Direct redirect to the first Google search result

## Security Notes

- Never commit your actual API keys to version control
- The `.env` file is already included in `.gitignore`
- Consider implementing rate limiting for production use
- Monitor your Google API usage to stay within quotas

## Error Handling

The script handles various error conditions with detailed error messages:

- **Missing query parameter**: Returns 400 error with usage instructions
- **Missing API credentials**: Returns 500 error with configuration instructions
- **API request failures**: Returns appropriate HTTP status codes with troubleshooting information
- **403 Forbidden errors**: Provides specific guidance for API key issues, quota limits, and referer restrictions
- **No search results found**: Returns 404 error with the search query

## Files

- `google_lucky.php` - Main script that performs the search and redirect
- `test_api.php` - Test script to verify API credentials and connectivity
- `.env` - Environment file for API credentials (create this file)
- `.gitignore` - Prevents sensitive files from being committed

## License

This project is open source and available under the MIT License.