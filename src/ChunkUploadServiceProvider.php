<?php

namespace Jmjl161100\ChunkUpload;

use Illuminate\Support\ServiceProvider;

class ChunkUploadServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        //
    }

    public function register(): void
    {
        $this->app->singleton('checkUpload', function ($app) {
            return new CheckUpload;
        });
    }
}
