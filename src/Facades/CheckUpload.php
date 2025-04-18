<?php

namespace Jmjl161100\ChunkUpload\Facades;

use Illuminate\Support\Facades\Facade;

class CheckUpload extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'checkUpload';
    }
}
