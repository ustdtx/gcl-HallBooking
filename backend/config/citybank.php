<?php
return [
    'base_url'     => env('CB_BASE_URL'),
    'username'     => env('CB_USERNAME'),
    'password'     => env('CB_PASSWORD'),
    'merchant_id'  => env('CB_MERCHANT_ID'),
    'cert_path'    => base_path(env('CB_CERT_PATH')),
    'key_path'     => base_path(env('CB_KEY_PATH')),
];
