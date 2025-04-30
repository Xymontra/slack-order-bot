<?php

return [

    // This is the channel ID where the bot will respond to commands.
    'allowed_channel' => getenv('ALLOWED_CHANNEL_ID'),
    // This is the URL of the API endpoint.
    'api_url' => getenv('API_URL'),
    // This is the API key for authentication.
    'api_keys' => [
        'otohikaye' => getenv('OTOHIKAYE_API_KEY'),
        'otolike'   => getenv('OTOLIKE_API_KEY'),
    ],
    // This is the R2D service ID for drip-feed.
    'hikaye_services' => [
        'TR'     => 6297,
        'GLOBAL' => 5343,
        'AZE'    => 6713
    ],
    // This is the R2D service ID for likes.
    'like_service_id' => 8131
];
