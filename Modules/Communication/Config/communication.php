<?php

return [
    'notification' => [
        'fcm' => [
            'driver' => env('FCM_DRIVER', 'http'),
            'server_key' => env('FCM_SERVER_KEY'),
            'project_id' => env('FCM_PROJECT_ID'),
            'credentials_path' => env('FCM_CREDENTIALS_PATH'),
        ],
        'pusher' => [
            'app_id' => env('PUSHER_APP_ID'),
            'key' => env('PUSHER_KEY'),
            'secret' => env('PUSHER_SECRET'),
            'cluster' => env('PUSHER_CLUSTER', 'mt1'),
            'use_tls' => env('PUSHER_USE_TLS', true),
            'encrypted' => env('PUSHER_ENCRYPTED', true),
        ],
        'channels' => [
            'new_message' => ['pusher', 'database'],
            'new_offer' => ['pusher', 'fcm', 'database'],
            'booking_confirmed' => ['fcm', 'database'],
            'property_update' => ['pusher', 'database'],
            'admin_alert' => ['fcm', 'pusher', 'database'],
        ],
    ],
    'chat' => [
        'max_message_length' => 5000,
        'allowed_file_types' => [
            'image/jpeg',
            'image/png',
            'image/webp',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ],
        'max_file_size_mb' => 10,
        'attachment_disk' => env('CHAT_ATTACHMENT_DISK', 'local'),
    ],
];