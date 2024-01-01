<?php

return [
    'public_key' => env('STRIPE_PUBLIC_KEY'),
    'secret_key' => env('STRIPE_SECRET_KEY'),
    'home_route' => env('STRIPE_HOME_ROUTE'),
    'currency' =>   env('STRIPE_CURRENCY'),
    'file_format' => 'xml',

];
