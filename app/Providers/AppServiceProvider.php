<?php

namespace App\Providers;

use App\Repositories\Contracts\TranslationRepositoryInterface;
use App\Repositories\TranslationRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            TranslationRepositoryInterface::class,
            TranslationRepository::class,
        );
    }

    public function boot(): void
    {
        //
    }
}
