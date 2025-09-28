<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Provider Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for AI services used in the
    | Wealth Prognosis application, particularly for the AI Assistant.
    |
    */

    'provider' => env('AI_PROVIDER', 'openai'),

    'api_key' => env('AI_API_KEY', env('OPENAI_API_KEY')),

    'model' => env('AI_MODEL', 'gpt-4'),

    /*
    |--------------------------------------------------------------------------
    | Available Models
    |--------------------------------------------------------------------------
    |
    | List of available AI models for different providers
    |
    */

    'models' => [
        'openai' => [
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
            'gpt-4' => 'GPT-4',
            'gpt-4-turbo' => 'GPT-4 Turbo',
            'gpt-4o' => 'GPT-4o',
            'gpt-5' => 'GPT-5',
            'o1-preview' => 'o1 Preview',
            'o1-mini' => 'o1 Mini',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for different AI models
    |
    */

    'settings' => [
        'gpt-3.5-turbo' => [
            'max_tokens' => 1000,
            'temperature' => 0.7,
            'timeout' => 30,
        ],
        'gpt-4' => [
            'max_tokens' => 1500,
            'temperature' => 0.7,
            'timeout' => 45,
        ],
        'gpt-4-turbo' => [
            'max_tokens' => 2000,
            'temperature' => 0.7,
            'timeout' => 45,
        ],
        'gpt-4o' => [
            'max_tokens' => 2000,
            'temperature' => 0.7,
            'timeout' => 45,
        ],
        'gpt-5' => [
            'max_tokens' => 3000,
            'temperature' => 0.7,
            'timeout' => 60,
        ],
        'o1-preview' => [
            'max_tokens' => 2000,
            'temperature' => 1.0, // o1 models use fixed temperature
            'timeout' => 60,
        ],
        'o1-mini' => [
            'max_tokens' => 1500,
            'temperature' => 1.0, // o1 models use fixed temperature
            'timeout' => 45,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for AI interaction logging
    |
    */

    'logging' => [
        'enabled' => env('AI_LOGGING_ENABLED', true),
        'log_requests' => env('AI_LOG_REQUESTS', true),
        'log_responses' => env('AI_LOG_RESPONSES', true),
        'log_context' => env('AI_LOG_CONTEXT', true),
        'log_errors' => env('AI_LOG_ERRORS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limiting configuration for AI API calls
    |
    */

    'rate_limiting' => [
        'enabled' => env('AI_RATE_LIMITING_ENABLED', true),
        'max_requests_per_minute' => env('AI_MAX_REQUESTS_PER_MINUTE', 60),
        'max_requests_per_hour' => env('AI_MAX_REQUESTS_PER_HOUR', 1000),
    ],
];
