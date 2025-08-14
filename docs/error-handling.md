# Error Handling & Retry Logic

This document describes the enhanced error handling and retry logic implemented in the ElevenLabs Laravel package.

## Overview

The package now includes sophisticated error handling with:
- **Centralized exception handling** returning typed errors
- **Exponential backoff** with jitter for 429/5xx responses  
- **Descriptive logging** to `storage/logs/elevenlabs.log`
- **Backward compatibility** with existing array-based responses

## Features

### 1. Typed Exceptions

All HTTP errors are now converted to typed exceptions:

- `RateLimitException` - For 429 rate limit errors
- `ServerErrorException` - For 500, 502, 503, 504 server errors
- `ClientErrorException` - For other 4xx client errors
- `ElevenLabsException` - Base exception class

### 2. Automatic Retry Logic

The package automatically retries:
- **429 (Rate Limited)** responses - with respect for `Retry-After` header
- **5xx (Server Error)** responses - with exponential backoff

**Non-retryable errors:**
- 4xx client errors (except 429) are not retried as they indicate client issues

### 3. Exponential Backoff

Retry delays use exponential backoff:
- **Base delay**: 1000ms (configurable)
- **Formula**: `base_delay * (2 ^ (attempt - 1))`
- **Jitter**: ±10% random variance to prevent thundering herd
- **Max delay**: 60 seconds (configurable)

### 4. Retry-After Header Support

For 429 responses, the `Retry-After` header is respected:
- Takes precedence over exponential backoff
- Supports both seconds format (`Retry-After: 60`) and date format
- Capped at configured maximum delay

## Configuration

Configure retry behavior in `config/elevenlabs.php`:

```php
'retry' => [
    'enabled' => env('ELEVENLABS_RETRY_ENABLED', true),
    'max_attempts' => env('ELEVENLABS_RETRY_MAX_ATTEMPTS', 3),
    'base_delay_ms' => env('ELEVENLABS_RETRY_BASE_DELAY_MS', 1000),
    'max_delay_ms' => env('ELEVENLABS_RETRY_MAX_DELAY_MS', 60000),
    'respect_retry_after' => env('ELEVENLABS_RETRY_RESPECT_RETRY_AFTER', true),
    'use_jitter' => env('ELEVENLABS_RETRY_USE_JITTER', true),
],

'logging' => [
    'enabled' => env('ELEVENLABS_LOGGING_ENABLED', true),
    'log_requests' => env('ELEVENLABS_LOG_REQUESTS', false),
    'log_responses' => env('ELEVENLABS_LOG_RESPONSES', true),
    'log_retries' => env('ELEVENLABS_LOG_RETRIES', true),
    'log_rate_limits' => env('ELEVENLABS_LOG_RATE_LIMITS', true),
    'channel' => env('ELEVENLABS_LOG_CHANNEL', null),
],
```

### Environment Variables

Add these to your `.env` file:

```bash
# Retry Configuration
ELEVENLABS_RETRY_ENABLED=true
ELEVENLABS_RETRY_MAX_ATTEMPTS=3
ELEVENLABS_RETRY_BASE_DELAY_MS=1000
ELEVENLABS_RETRY_MAX_DELAY_MS=60000
ELEVENLABS_RETRY_RESPECT_RETRY_AFTER=true
ELEVENLABS_RETRY_USE_JITTER=true

# Logging Configuration
ELEVENLABS_LOGGING_ENABLED=true
ELEVENLABS_LOG_REQUESTS=false
ELEVENLABS_LOG_RESPONSES=true
ELEVENLABS_LOG_RETRIES=true
ELEVENLABS_LOG_RATE_LIMITS=true
```

## Usage Examples

### Basic Usage (Backward Compatible)

Existing code continues to work unchanged:

```php
use Samandar\LaravelElevenLabs\Services\Audio\AudioService;

$audioService = new AudioService($apiKey);
$result = $audioService->textToSpeech('Hello World');

if ($result['success']) {
    // Success - audio data available in $result['audio']
    file_put_contents('output.mp3', $result['audio']);
} else {
    // Error - details in $result['error']
    echo "Error: " . $result['error'];
}
```

### Advanced Error Handling

Handle specific error types:

```php
use Samandar\LaravelElevenLabs\Services\Voice\VoiceService;
use Samandar\LaravelElevenLabs\Exceptions\RateLimitException;
use Samandar\LaravelElevenLabs\Exceptions\ServerErrorException;

$voiceService = new VoiceService($apiKey);
$result = $voiceService->getVoices();

if (!$result['success']) {
    if (isset($result['exception'])) {
        $exception = $result['exception'];
        
        if ($exception instanceof RateLimitException) {
            $retryAfter = $exception->getRetryAfterSeconds();
            echo "Rate limited! Retry after: {$retryAfter} seconds";
            
        } elseif ($exception instanceof ServerErrorException) {
            echo "Server error: " . $exception->getStatusCode();
            echo "This was automatically retried";
            
        } else {
            echo "Client error: " . $exception->getMessage();
            echo "Status: " . $exception->getStatusCode();
        }
        
        // Get additional error details
        $responseBody = $exception->getResponseBody();
        $errorData = $exception->getErrorData();
    }
}
```

### Exception Methods

All ElevenLabs exceptions provide useful methods:

```php
$exception->getStatusCode();        // HTTP status code
$exception->getResponseBody();      // Raw response body
$exception->getErrorData();         // Parsed error data
$exception->isRateLimited();        // true for 429 errors
$exception->isServerError();        // true for 5xx errors  
$exception->isClientError();        // true for 4xx errors
$exception->isRetryable();          // true if error should be retried
$exception->getRetryAfterSeconds(); // Retry-After value for 429s
```

## Logging

### Log Location

Logs are written to `storage/logs/elevenlabs.log` (with rotating files):
- `elevenlabs-2023-12-01.log`
- `elevenlabs-2023-12-02.log` 
- etc.

### Log Content

Logs include:
- **Request details**: Method, endpoint, headers (sanitized)
- **Response details**: Status, duration, error bodies
- **Retry attempts**: Attempt number, delay, reason
- **Rate limit events**: Retry-After values, headers

### Sensitive Data Protection

The logger automatically sanitizes sensitive data:
- API keys: `sk-1234***abcd`  
- Auth tokens: `Bear***123`
- Passwords, secrets: `***`

### Sample Log Output

```
[2023-12-01 10:30:15] INFO: ElevenLabs API Request: POST /text-to-speech {"method":"POST","endpoint":"\/text-to-speech","headers":{"xi-api-key":"sk-1***xyz"}}

[2023-12-01 10:30:16] ERROR: ElevenLabs API Response: POST /text-to-speech [429] {"method":"POST","endpoint":"\/text-to-speech","status_code":429,"duration_ms":1250.5}

[2023-12-01 10:30:16] WARNING: ElevenLabs API Retry: Attempt 1/3 for POST /text-to-speech {"attempt":1,"max_attempts":3,"context":"POST \/text-to-speech","error":"Rate limited","delay_ms":5000}

[2023-12-01 10:30:21] INFO: ElevenLabs API Response: POST /text-to-speech [200] {"method":"POST","endpoint":"\/text-to-speech","status_code":200,"duration_ms":2100.25}
```

## Performance Considerations

### Retry Impact

- **Default**: Up to 3 attempts with exponential backoff
- **Total delay**: Can be several minutes for persistent errors
- **Circuit breaking**: Consider implementing application-level circuit breakers for repeated failures

### Logging Performance

- **Async logging**: Consider using queued log drivers for high-volume applications
- **Log rotation**: Old logs are automatically rotated daily
- **Selective logging**: Disable request logging in production if not needed

## Troubleshooting

### Common Issues

**High retry delays:**
- Reduce `max_delay_ms` for faster failure
- Disable jitter for predictable delays
- Reduce `max_attempts` for faster failure

**Too much logging:**
- Set `ELEVENLABS_LOG_REQUESTS=false`
- Use Laravel's log level filtering
- Configure separate log channel

**Memory usage:**
- Large response bodies are truncated in logs
- Binary data is summarized as `[Binary data - X bytes]`

### Debug Configuration

For development, enable verbose logging:

```bash
ELEVENLABS_LOGGING_ENABLED=true
ELEVENLABS_LOG_REQUESTS=true
ELEVENLABS_LOG_RESPONSES=true
ELEVENLABS_LOG_RETRIES=true
```

For production, use minimal logging:

```bash
ELEVENLABS_LOGGING_ENABLED=true
ELEVENLABS_LOG_REQUESTS=false  
ELEVENLABS_LOG_RESPONSES=true  # Errors only
ELEVENLABS_LOG_RETRIES=true
```

## Migration

### From Previous Versions

**No breaking changes** - existing code continues to work:

```php
// This still works exactly as before
$result = $audioService->textToSpeech('Hello');
if ($result['success']) {
    // Success handling unchanged
} else {
    // Error handling unchanged  
    echo $result['error'];
}
```

**Optional enhancements** - access new features:

```php  
// NEW: Access detailed exception information
if (!$result['success'] && isset($result['exception'])) {
    $exception = $result['exception'];
    if ($exception instanceof RateLimitException) {
        // Handle rate limits specifically
    }
}
```

### Disabling New Features

To disable retry logic entirely:

```bash
ELEVENLABS_RETRY_ENABLED=false
```

To disable logging:

```bash
ELEVENLABS_LOGGING_ENABLED=false
```

## Implementation Details

### Architecture

- **BaseElevenLabsService**: Centralized HTTP handling with retry logic
- **RetryHandler**: Exponential backoff and retry decision logic  
- **ElevenLabsLogger**: Specialized logging with data sanitization
- **Typed Exceptions**: Structured error information

### Backward Compatibility

The implementation maintains 100% backward compatibility:

1. **Array responses**: All methods still return arrays with `['success' => bool]`
2. **Error format**: Errors still include `['error' => string, 'code' => int]`  
3. **Method signatures**: No changes to public method signatures
4. **Configuration**: New config is optional with sensible defaults

The new exception objects are available as `$result['exception']` for advanced error handling, but existing error handling code remains unchanged.
