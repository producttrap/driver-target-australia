<?php

declare(strict_types=1);

namespace ProductTrap\TargetAustralia\Tests;

use ProductTrap\ProductTrapServiceProvider;
use ProductTrap\TargetAustralia\TargetAustraliaServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app): array
    {
        return [ProductTrapServiceProvider::class, TargetAustraliaServiceProvider::class];
    }
}
