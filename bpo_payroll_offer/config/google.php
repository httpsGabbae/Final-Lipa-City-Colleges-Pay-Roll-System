<?php
// Google Auth: 'demo' works with no keys. Switch to 'real' after composer install + .env.
return [
    'mode' => getenv('GOOGLE_MODE') ?: 'demo',
    'client_id' => getenv('GOOGLE_CLIENT_ID') ?: '',
    'client_secret' => getenv('GOOGLE_CLIENT_SECRET') ?: '',
    'redirect_url' => getenv('GOOGLE_REDIRECT_URL') ?: '',
];
