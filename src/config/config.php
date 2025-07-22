<?php
// src/config/config.php

// Set timezone to match Norwegian time
date_default_timezone_set('Europe/Oslo');

define('DB_HOST', 'localhost');
define('DB_NAME', 'cvp60zaqj_apprackdb');
define('DB_USER', 'cvp60zaqj_apprackdb'); // Sett inn ditt faktiske brukernavn
define('DB_PASS', 'Loker1978_');    // Sett inn ditt faktiske passord
define('DB_CHARSET', 'utf8mb4');

// AI Configuration
define('AI_CONFIG', [
    // OpenAI API Configuration - Set your API key as environment variable
    'openai_api_key' => getenv('OPENAI_API_KEY') ?: '',
    
    // Default model settings
    'default_model' => 'gpt-3.5-turbo',
    'default_temperature' => 0.7,
    'default_max_tokens' => 2000,
    
    // Cache settings
    'cache_duration_hours' => 24,
    'enable_caching' => true,
    
    // Rate limiting
    'max_requests_per_user_per_hour' => 20,
    'max_tokens_per_user_per_day' => 50000,
    
    // Analysis types configuration
    'analysis_types' => [
        'summary' => [
            'max_tokens' => 2000,
            'temperature' => 0.7,
            'cache_duration_hours' => 24
        ],
        'timeline' => [
            'max_tokens' => 2500,
            'temperature' => 0.6,
            'cache_duration_hours' => 12
        ],
        'risk_assessment' => [
            'max_tokens' => 2500,
            'temperature' => 0.5,
            'cache_duration_hours' => 6
        ],
        'relationship_analysis' => [
            'max_tokens' => 2000,
            'temperature' => 0.6,
            'cache_duration_hours' => 24
        ],
        'trend_analysis' => [
            'max_tokens' => 3000,
            'temperature' => 0.4,
            'cache_duration_hours' => 48
        ]
    ],
    
    // Security settings
    'allowed_domains' => [
        'localhost',
        '127.0.0.1',
        'your-domain.com'
    ],
    
    // Data privacy settings
    'anonymize_personal_data' => true,
    'exclude_sensitive_fields' => [
        'contract_number',
        'contract_responsible'
    ],
    
    // Logging settings
    'log_ai_requests' => true,
    'log_level' => 'info', // debug, info, warning, error
    
    // Performance settings
    'timeout_seconds' => 60,
    'retry_attempts' => 3,
    'retry_delay_seconds' => 2
]);

// FontAwesome Pro Configuration
define('FONTAWESOME_PRO_LICENSE', 'FAPS-KWQH-GHQP-BBAG-4960');
define('FONTAWESOME_PRO_KIT_ID', 'your-kit-id'); // Replace with your actual Kit ID from FontAwesome

?>