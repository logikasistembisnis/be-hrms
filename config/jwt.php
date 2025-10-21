<?php

return [
    'secret' => env('JWT_SECRET'),
    'ttl' => 60, // Token berlaku 60 menit
    'refresh_ttl' => 20160, // 2 minggu
    'blacklist_enabled' => true,
];