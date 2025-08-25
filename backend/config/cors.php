<?php

return [
    'allowed_origins' => [
        'http://localhost:5173', // Vite development server
        'http://localhost:3000', // Vue.js development server
        'http://localhost:8080', // Vue.js alternative port
        'http://127.0.0.1:5173',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:8080'
    ],
    
    'allowed_methods' => [
        'GET',
        'POST',
        'PUT',
        'DELETE',
        'OPTIONS'
    ],
    
    'allowed_headers' => [
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'Accept',
        'Origin'
    ],
    
    'exposed_headers' => [
        'Authorization'
    ],
    
    'max_age' => 86400, // 24 hours
    
    'supports_credentials' => true
];
