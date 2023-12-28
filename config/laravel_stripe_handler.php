<?php

return [
    'public_key' => env('STRIPE_PUBLIC_KEY'),
    'secret_key' => env('STRIPE_SECRET_KEY'),
    'home_page' => route('home') //Change home route in case you want to redirect to other page after payment

];
