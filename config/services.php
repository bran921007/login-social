<?php

return [
    'google' => ['client_id' => env('GOOGLE_CLIENT_ID'), 'client_secret' => env('GOOGLE_CLIENT_SECRET'), 'redirect' => env('GOOGLE_REDIRECT_URI')],
    'facebook' => ['client_id' => env('FACEBOOK_CLIENT_ID'), 'client_secret' => env('FACEBOOK_CLIENT_SECRET'), 'redirect' => env('FACEBOOK_REDIRECT_URI')],
    'twitter' => ['client_id' => env('TWITTER_CLIENT_ID'), 'client_secret' => env('TWITTER_CLIENT_SECRET'), 'redirect' => env('TWITTER_REDIRECT_URI')],
];
