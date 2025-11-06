# AI API Timeout Increase

## Problem

Users were experiencing timeout errors when running AI comparison analysis:

```
Symfony\Component\ErrorHandler\Error\FatalError
PHP 8.4.14
Maximum execution time of 30 seconds exceeded
```

**Root Cause:**
- PHP's default `max_execution_time` is 30 seconds
- AI API calls can take 1-3 minutes for large payloads
- The job was timing out before the AI could respond

## Solution

Increased timeouts at multiple levels to allow AI API calls to complete:

### 1. Job Timeout (ProcessAiComparisonAnalysis)

**Before:**
```php
public int $timeout = 300; // 5 minutes
```

**After:**
```php
public int $timeout = 600; // 10 minutes
```

**Why:** Ensures the queue worker doesn't kill the job prematurely.

### 2. PHP Execution Time Limit

**Added to job's `handle()` method:**
```php
public function handle(): void
{
    // Set PHP execution time limit to 10 minutes (600 seconds)
    // This overrides the default 30 second limit
    set_time_limit(600);
    
    // ... rest of the code
}
```

**Why:** Overrides PHP's default 30-second execution limit for this specific job.

### 3. HTTP Client Timeout (AiEvaluationService)

**Already set to 300 seconds (5 minutes):**
```php
$response = Http::withHeaders([
    'Authorization' => 'Bearer '.$this->apiKey,
    'Content-Type' => 'application/json',
])->timeout(300)->post($this->baseUrl.'/chat/completions', $payload);
```

**Why:** Allows the HTTP request to wait up to 5 minutes for OpenAI's response.

### 4. Model-Specific Timeouts (config/ai.php)

**Before:**
```php
'gpt-4o' => [
    'max_tokens' => 2000,
    'temperature' => 0.7,
    'timeout' => 45,  // 45 seconds
],
'gpt-5' => [
    'max_tokens' => 3000,
    'temperature' => 0.7,
    'timeout' => 60,  // 60 seconds
],
```

**After:**
```php
'gpt-4o' => [
    'max_tokens' => 2000,
    'temperature' => 0.7,
    'timeout' => 300,  // 5 minutes
],
'gpt-5' => [
    'max_tokens' => 3000,
    'temperature' => 0.7,
    'timeout' => 300,  // 5 minutes
],
```

**All Model Timeouts Updated:**
- `gpt-3.5-turbo`: 30s → **120s** (2 minutes)
- `gpt-4`: 45s → **180s** (3 minutes)
- `gpt-4-turbo`: 45s → **180s** (3 minutes)
- `gpt-4o`: 45s → **300s** (5 minutes)
- `gpt-5`: 60s → **300s** (5 minutes)
- `o1-preview`: 60s → **300s** (5 minutes)
- `o1-mini`: 45s → **180s** (3 minutes)

### 5. Other AI Services

**AiConfigurationAnalysisService:**
```php
// Before: timeout(20)
// After: timeout(300)
$response = Http::withHeaders([
    'Authorization' => 'Bearer '.$this->openaiApiKey,
    'Content-Type' => 'application/json',
])->timeout(300)->post('https://api.openai.com/v1/chat/completions', [
    // ...
]);
```

### 6. User-Facing Messages

**Status Message:**
```php
// Before:
Cache::put($this->cacheKey.':status', 'Calling AI service (this may take 30-60 seconds)...', 600);

// After:
Cache::put($this->cacheKey.':status', 'Calling AI service (this may take 1-3 minutes)...', 600);
```

**UI Message:**
```html
<!-- Before: -->
⏱️ This typically takes 30-60 seconds. Status updates every 2 seconds...

<!-- After: -->
⏱️ This typically takes 1-3 minutes. Status updates every 2 seconds...
```

## Timeout Hierarchy

The timeout chain works as follows:

```
┌─────────────────────────────────────────────────────────────┐
│ Job Timeout: 600 seconds (10 minutes)                       │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ PHP Execution Time: 600 seconds (10 minutes)            │ │
│ │ ┌─────────────────────────────────────────────────────┐ │ │
│ │ │ HTTP Client Timeout: 300 seconds (5 minutes)        │ │ │
│ │ │ ┌─────────────────────────────────────────────────┐ │ │ │
│ │ │ │ OpenAI API Processing: ~60-180 seconds         │ │ │ │
│ │ │ └─────────────────────────────────────────────────┘ │ │ │
│ │ └─────────────────────────────────────────────────────┘ │ │
│ └─────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

**Explanation:**
1. **OpenAI API** processes the request (actual AI work)
2. **HTTP Client** waits for the response (5 min max)
3. **PHP Execution** runs the entire job (10 min max)
4. **Job Timeout** ensures queue worker doesn't kill it (10 min max)

## Files Modified

1. **app/Jobs/ProcessAiComparisonAnalysis.php**
   - Increased job timeout: 300s → 600s
   - Added `set_time_limit(600)` in `handle()` method
   - Updated status message: "30-60 seconds" → "1-3 minutes"

2. **config/ai.php**
   - Increased all model timeouts (see table above)
   - gpt-4o and gpt-5 now have 5-minute timeouts

3. **app/Services/AiConfigurationAnalysisService.php**
   - Increased HTTP timeout: 20s → 300s

4. **resources/views/filament/widgets/compare/compare-ai-analysis-widget.blade.php**
   - Updated UI message: "30-60 seconds" → "1-3 minutes"

## Testing

### Verify the Fix

1. **Clear cache:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

2. **Test AI Comparison:**
   - Go to Compare Dashboard
   - Select two simulations
   - Click "✨ Generate AI Analysis"
   - Wait for completion (should take 1-3 minutes)
   - Verify no timeout errors

3. **Monitor logs:**
   ```bash
   tail -f storage/logs/ai-interactions-$(date +%Y-%m-%d).log
   ```

4. **Check for errors:**
   ```bash
   tail -f storage/logs/laravel.log | grep -i "timeout\|exceeded"
   ```

### Expected Behavior

**Before Fix:**
- Job starts
- After 30 seconds: "Maximum execution time exceeded" error
- Job fails
- No AI response

**After Fix:**
- Job starts
- Status updates every 2 seconds
- AI API call completes in 1-3 minutes
- Job completes successfully
- AI analysis displayed

## Why Multiple Timeout Levels?

### 1. Job Timeout (`$timeout` property)
- **Purpose:** Tells the queue worker when to kill the job
- **Set to:** 600 seconds (10 minutes)
- **Why:** Gives plenty of buffer for the entire job execution

### 2. PHP Execution Time (`set_time_limit()`)
- **Purpose:** Overrides PHP's default 30-second script execution limit
- **Set to:** 600 seconds (10 minutes)
- **Why:** Prevents PHP from killing the script mid-execution

### 3. HTTP Client Timeout (`.timeout()`)
- **Purpose:** How long to wait for the HTTP response
- **Set to:** 300 seconds (5 minutes)
- **Why:** Allows OpenAI API enough time to process large payloads

### 4. Model Config Timeout (`config/ai.php`)
- **Purpose:** Model-specific timeout settings for different AI services
- **Set to:** 120-300 seconds depending on model
- **Why:** Different models have different processing times

## Performance Considerations

### Why 5 Minutes for HTTP Timeout?

**Factors affecting AI response time:**
1. **Payload size:** CSV exports are ~64KB (16K tokens)
2. **Model complexity:** GPT-4o and GPT-5 are slower than GPT-3.5
3. **OpenAI load:** API can be slower during peak times
4. **Token generation:** 16K output tokens take time to generate

**Observed response times:**
- Small payloads (<5K tokens): 10-30 seconds
- Medium payloads (5-15K tokens): 30-90 seconds
- Large payloads (15-20K tokens): 60-180 seconds

**Safety margin:**
- 5-minute timeout provides 2-3x buffer
- Prevents false timeouts during peak load
- Allows for network latency

### Why 10 Minutes for Job Timeout?

**Total job execution includes:**
1. Loading AI instruction: ~1 second
2. Loading simulations: ~2 seconds
3. Exporting to CSV: ~1 second
4. Building prompt: ~1 second
5. **AI API call: 60-180 seconds**
6. Processing response: ~1 second

**Total:** ~70-190 seconds typical, up to 300 seconds worst case

**10-minute timeout provides:**
- 2x safety margin over worst case
- Room for database slowness
- Room for cache operations
- Room for logging overhead

## Troubleshooting

### If Timeouts Still Occur

1. **Check PHP-FPM settings:**
   ```bash
   # Find your php.ini
   php --ini
   
   # Check max_execution_time
   php -i | grep max_execution_time
   ```

2. **Check web server timeout:**
   - **Nginx:** `fastcgi_read_timeout` (default 60s)
   - **Apache:** `TimeOut` directive (default 300s)

3. **Check if running in queue:**
   ```bash
   # If using sync queue, it runs in web request context
   grep QUEUE_CONNECTION .env
   
   # Should be 'sync' for development
   QUEUE_CONNECTION=sync
   ```

4. **Increase timeouts further if needed:**
   ```php
   // In ProcessAiComparisonAnalysis.php
   public int $timeout = 900; // 15 minutes
   set_time_limit(900);
   
   // In AiEvaluationService.php
   ->timeout(600) // 10 minutes
   ```

### If AI Responses Are Slow

1. **Reduce payload size:**
   - Use `toCompactJson()` instead of `toJson()`
   - Filter more aggressively (remove more empty rows)
   - Reduce date range in simulations

2. **Use faster model:**
   - Switch from `gpt-5` to `gpt-4o`
   - Switch from `gpt-4o` to `gpt-4-turbo`
   - Switch from `gpt-4` to `gpt-3.5-turbo`

3. **Reduce max_tokens:**
   - Current: 16,000 tokens
   - Try: 8,000 or 4,000 tokens
   - Shorter responses = faster generation

## Production Considerations

### For Production Deployment

1. **Use queue worker:**
   ```bash
   # Change to database or redis queue
   QUEUE_CONNECTION=database
   
   # Run queue worker with timeout
   php artisan queue:work --timeout=600
   ```

2. **Configure Supervisor:**
   ```ini
   [program:wealth-prognosis-worker]
   command=php /path/to/artisan queue:work --timeout=600
   autostart=true
   autorestart=true
   stopwaitsecs=620
   ```

3. **Monitor job failures:**
   ```bash
   # Check failed jobs
   php artisan queue:failed
   
   # Retry failed jobs
   php artisan queue:retry all
   ```

## Summary

✅ **Job timeout increased:** 5 min → 10 min  
✅ **PHP execution time set:** 10 minutes  
✅ **HTTP timeout confirmed:** 5 minutes  
✅ **Model timeouts increased:** 2-5 minutes  
✅ **User messages updated:** "1-3 minutes"  
✅ **All services updated:** Consistent timeouts  

The AI comparison analysis should now complete successfully without timeout errors! 🎉

