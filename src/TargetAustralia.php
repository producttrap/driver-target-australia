<?php

declare(strict_types=1);

namespace ProductTrap\TargetAustralia;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Str;
use ProductTrap\Contracts\Driver;
use ProductTrap\DTOs\Brand;
use ProductTrap\DTOs\Currency;
use ProductTrap\DTOs\Price;
use ProductTrap\DTOs\Product;
use ProductTrap\DTOs\Results;
use ProductTrap\DTOs\UnitAmount;
use ProductTrap\DTOs\UnitPrice;
use ProductTrap\Enums\Status;
use ProductTrap\Enums\Unit;
use ProductTrap\Exceptions\ProductTrapDriverException;
use ProductTrap\Traits\DriverCache;
use ProductTrap\Traits\DriverCrawler;

class TargetAustralia implements Driver
{
    use DriverCache;
    use DriverCrawler;

    public const IDENTIFIER = 'target_australia';

    public const BASE_URI = 'https://www.target.com.au';

    public function __construct(CacheRepository $cache)
    {
        $this->cache = $cache;
    }

    public function getName(): string
    {
        return static::IDENTIFIER;
    }

    /**
     * @param  array<string, mixed>  $parameters
     *
     * @throws ProductTrapDriverException
     */
    public function find(string $identifier, array $parameters = []): Product
    {
        $html = $this->remember($identifier, now()->addDay(), fn () => $this->scrape($this->url($identifier)));
        $crawler = $this->crawl($html);

        file_put_contents(base_path('temp.html'), (string) $html);

        // Title
        $title = Str::of(
            $crawler->filter('title')->first()->html()
        )->trim()->before(' | ');

        // Description
        try {
            $description = $crawler->filter('meta[name="description"]')->first()->attr('content');
        } catch (\Exception $e) {
            $description = null;
        }

        //SKU
        $sku = (string) Str::of($crawler->filter('.prod-code [itemprop="productID"]')->first()->text())->replace(' ', '');

        // Gtin
        $gtin = null;

        // Brand
        preg_match('/MVT_BRAND_CODE = "(.+)";/', $html, $matches);
        $brand = isset($matches[1]) ? new Brand(
            name: ucfirst(strtolower((string) $matches[1])),
            identifier: $matches[1],
        ) : null;

        // Price
        $price = null;
        try {
            $price = Str::of(
                $crawler->filter('.price-regular .Price')->first()->text()
            )->replace(['$', ',', ' '], '')->toFloat();
        } catch (\Exception $e) {
        }
        $price = ($price !== null)
            ? new Price(
                amount: $price,
            )
            : null;

        // Currency
        $currency = new Currency(
            code: 'AUD',
        );

        // Images
        $images = [];
        $crawler->filter('.GalleryList-item img')->each(function ($node) use (&$images) {
            $src = $node->attr('src');

            if (str_contains($src, 'product_portrait_thumb')) {
                return;
            }

            $images[] = $node->attr('src');
        });
        $images = array_values(array_unique($images));

        // Status
        $status = ($price !== null) ? Status::Unavailable : Status::Available;

        // Unit Amount (e.g. 85g or 1kg)
        $unitAmount = null;

        // Unit Price (e.g. $2 per kg)
        $unitPrice = null;

        // URL
        $url = $crawler->filter('link[rel="canonical"]')->first()->attr('href');

        $product = new Product([
            'identifier' => $identifier,
            'sku' => $sku,
            'name' => $title,
            'description' => $description,
            'url' => $url,
            'price' => $price,
            'currency' => $currency,
            'status' => $status,
            'brand' => $brand,
            'gtin' => $gtin,
            'unitAmount' => $unitAmount,
            'unitPrice' => $unitPrice,
            'ingredients' => null,
            'images' => $images,
            'raw' => [
                'html' => $html,
            ],
        ]);

        dd($product);

        return $product;
    }

    public function url(string $identifier): string
    {
        return self::BASE_URI.'/p/product/'.$identifier;
    }

    /**
     * @param  array<string, mixed>  $parameters
     *
     * @throws ProductTrapDriverException
     */
    public function search(string $keywords, array $parameters = []): Results
    {
        return new Results();
    }
}
