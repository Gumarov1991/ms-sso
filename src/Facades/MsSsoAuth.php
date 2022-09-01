<?php

namespace GumarovDev\MicrosoftSsoAuth\Facades;

use Illuminate\Support\Facades\Facade;

class MsSsoAuth extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \GumarovDev\MicrosoftSsoAuth\MsSsoAuth::class;
    }
}
