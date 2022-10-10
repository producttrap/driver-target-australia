<?php

declare(strict_types=1);

namespace ProductTrap\TargetAustralia;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\ServiceProvider;
use ProductTrap\Contracts\Factory;
use ProductTrap\ProductTrap;

class TargetAustraliaServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        /** @var ProductTrap $factory */
        $factory = $this->app->make(Factory::class);

        $factory->extend(TargetAustralia::IDENTIFIER, function () {
            /** @var ConfigRepository $config */
            $config = $this->app->make(ConfigRepository::class);
            /** @var CacheRepository $cache */
            $cache = $this->app->make(CacheRepository::class);

            return new TargetAustralia(
                cache: $cache,
            );
        });
    }
}
