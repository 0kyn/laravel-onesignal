<?php

return [
    /**
     * OneSignal APP id
     */
    'app_id' => env('ONESIGNAL_APP_ID'),

    /**
     * OneSignal API keys
     */
    'rest_api_key' => env('ONESIGNAL_REST_API_KEY'),
    'user_auth_key' => env('ONESIGNAL_USER_AUTH_KEY'),

    /**
     * SSL verify
     */
    'ssl_verify' => env('ONESIGNAL_SSL_VERIFY', true)
];
