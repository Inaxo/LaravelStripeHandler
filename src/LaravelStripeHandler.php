<?php

namespace Inaxo\LaravelStripeHandler;

class LaravelStripeHandler
{
    protected $publicKey;
    protected $privateKey;

    public function __construct(){
        $this->publicKey = config('laravel_stripe_handler.public_key');
        $this->privateKey = config('laravel_stripe_handler.secret_key');
    }

    public function getPublicKey(){
        return $this->publicKey;
    }

    public function getPrivateKey(){
        return $this->privateKey;
    }

}
