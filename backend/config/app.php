<?php

return [
    'name' => $_ENV['APP_NAME'] ?? 'Medical Clinic Management',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'url' => $_ENV['APP_URL'] ?? 'http://localhost:8000',
    
    'jwt' => [
        'secret' => $_ENV['JWT_SECRET'] ?? 'your_secret_key_here',
        'access_secret' => $_ENV['JWT_ACCESS_SECRET'] ?? $_ENV['JWT_SECRET'] ?? 'your_access_secret_key_here',
        'refresh_secret' => $_ENV['JWT_REFRESH_SECRET'] ?? $_ENV['JWT_SECRET'] ?? 'your_refresh_secret_key_here',
        'expiry' => (int) ($_ENV['JWT_EXPIRY'] ?? 86400), // 24 hours (legacy)
        'access_expiry' => (int) ($_ENV['JWT_ACCESS_EXPIRY'] ?? 900), // 15 minutes
        'refresh_expiry' => (int) ($_ENV['JWT_REFRESH_EXPIRY'] ?? 604800), // 7 days
        'algorithm' => 'HS256'
    ],
    
    'mail' => [
        'host' => $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com',
        'port' => (int) ($_ENV['MAIL_PORT'] ?? 587),
        'username' => $_ENV['MAIL_USERNAME'] ?? '',
        'password' => $_ENV['MAIL_PASSWORD'] ?? '',
        'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
        'from' => [
            'address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@clinic.com',
            'name' => $_ENV['MAIL_FROM_NAME'] ?? 'Medical Clinic'
        ]
    ],
    
    'sms' => [
        'twilio' => [
            'sid' => $_ENV['TWILIO_SID'] ?? '',
            'token' => $_ENV['TWILIO_TOKEN'] ?? '',
            'from' => $_ENV['TWILIO_FROM'] ?? ''
        ]
    ],
    
    'upload' => [
        'max_size' => (int) ($_ENV['UPLOAD_MAX_SIZE'] ?? 10485760), // 10MB
        'allowed_types' => explode(',', $_ENV['UPLOAD_ALLOWED_TYPES'] ?? 'jpg,jpeg,png,pdf,doc,docx')
    ],
    
    'security' => [
        'bcrypt_rounds' => (int) ($_ENV['BCRYPT_ROUNDS'] ?? 12),
        'rate_limit' => [
            'requests' => (int) ($_ENV['RATE_LIMIT_REQUESTS'] ?? 100),
            'window' => (int) ($_ENV['RATE_LIMIT_WINDOW'] ?? 3600) // 1 hour
        ]
    ],
    
    'reminders' => [
        'lead_time_hours' => (int) ($_ENV['REMINDER_LEAD_TIME_HOURS'] ?? 24),
        'second_notice_hours' => (int) ($_ENV['REMINDER_SECOND_NOTICE_HOURS'] ?? 2)
    ]
];
