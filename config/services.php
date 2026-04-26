<?php

return [
    'google' => [
        'service_account_path' => env('GOOGLE_SERVICE_ACCOUNT_PATH', 'storage/app/credentials/service-account.json'),
        'drive_root_folder_id' => env('GOOGLE_DRIVE_ROOT_FOLDER_ID'),
    ],
];
