<?php

declare(strict_types=1);

use ProductTrap\Contracts\Factory;
use ProductTrap\DTOs\Product;
use ProductTrap\Enums\Status;
use ProductTrap\Exceptions\ApiConnectionFailedException;
use ProductTrap\Facades\ProductTrap as FacadesProductTrap;
use ProductTrap\ProductTrap;
use ProductTrap\Spider;
use ProductTrap\TargetAustralia\TargetAustralia;

function getMockTargetAustralia($app, string $response): void
{
    Spider::fake([
        '*' => $response,
    ]);
}

it('can add the TargetAustralia driver to ProductTrap', function () {
    /** @var ProductTrap $client */
    $client = $this->app->make(Factory::class);

    $client->extend('target_other', fn () => new TargetAustralia(
        cache: $this->app->make('cache.store'),
    ));

    expect($client)->driver(TargetAustralia::IDENTIFIER)->toBeInstanceOf(TargetAustralia::class)
        ->and($client)->driver('target_other')->toBeInstanceOf(TargetAustralia::class);
});

it('can call the ProductTrap facade', function () {
    expect(FacadesProductTrap::driver(TargetAustralia::IDENTIFIER)->getName())->toBe('Target Australia');
});

it('can retrieve the TargetAustralia driver from ProductTrap', function () {
    expect($this->app->make(Factory::class)->driver(TargetAustralia::IDENTIFIER))->toBeInstanceOf(TargetAustralia::class);
});

it('can call `find` on the TargetAustralia driver and handle failed connection', function () {
    getMockTargetAustralia($this->app, '');

    $this->app->make(Factory::class)->driver(TargetAustralia::IDENTIFIER)->find('7XX1000');
})->throws(ApiConnectionFailedException::class, 'The connection to https://www.target.com.au/p/product/7XX1000 has failed for the Target Australia driver');

it('can call `find` on the TargetAustralia driver and handle a successful response', function () {
    $html = file_get_contents(__DIR__.'/../fixtures/successful_response.html');
    getMockTargetAustralia($this->app, $html);

    $data = $this->app->make(Factory::class)->driver(TargetAustralia::IDENTIFIER)->find('64183213');
    unset($data->raw);

    expect($this->app->make(Factory::class)->driver(TargetAustralia::IDENTIFIER)->find('64183213'))
        ->toBeInstanceOf(Product::class)
        ->identifier->toBe('64183213')
        ->status->toEqual(Status::Available)
        ->name->toBe('Sony Extra Bass Wireless Speaker SRSXB23')
        ->description->toBe('Take the party with you Compact, lightweight and easy to carry – wherever you’re heading, make the SRS-XB23 the first thing you pack. Whether you’re camping with friends or relaxing in the park, the compact and lightweight SRS-XB23 fits into your plans as easily as it fits into your bag.')
        ->price->amount->toBe(85.0)
        ->brand->name->toBe('Sony')
        ->images->toBe([
            // todo
        ]);
});
