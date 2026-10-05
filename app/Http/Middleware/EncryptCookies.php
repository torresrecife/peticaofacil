<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array
     */
    protected $except = [
        // Lido pelo CKFinder fora do Laravel; o valor ja e assinado.
        \App\Support\FileManagerToken::COOKIE,
    ];
}
