<?php

declare(strict_types=1);

namespace Tests\Ikuzo\SyliusChronopostPlugin\Api;

use Ikuzo\SyliusChronopostPlugin\Api\ShippingLabelFetcher;
use Ikuzo\SyliusChronopostPlugin\Api\SoapClientInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

final class ShippingLabelFetcherTest extends TestCase
{
    /** @dataProvider weightProvider */
    public function testWeightIsConvertedToKilograms(array $config, float $expectedWeight): void
    {
        $fetcher = new ShippingLabelFetcher(
            $this->createMock(FlashBagInterface::class),
            $this->createMock(SoapClientInterface::class)
        );

        $gateway = new class($config) {
            public function __construct(private array $config)
            {
            }

            public function getConfigValue(string $key): mixed
            {
                return $this->config[$key] ?? null;
            }

            public function getConfig(): array
            {
                return $this->config;
            }
        };

        $method = new class {
            public function getId(): int
            {
                return 1;
            }
        };

        $shipment = new class($method) {
            public function __construct(private object $method)
            {
            }

            public function getMethod(): object
            {
                return $this->method;
            }
        };

        $reflectionMethod = new \ReflectionMethod(ShippingLabelFetcher::class, 'getSkybillValue');
        $reflectionMethod->setAccessible(true);

        $skybill = $reflectionMethod->invoke($fetcher, $gateway, $shipment, 1500.0);

        self::assertSame($expectedWeight, $skybill['weight']);
        self::assertSame('KGM', $skybill['weightUnit']);
    }

    public static function weightProvider(): iterable
    {
        yield 'grams' => [
            ['weight_unit' => 'g', 'product_CHRONO13' => [1]],
            1.5,
        ];

        yield 'kilograms' => [
            ['weight_unit' => 'kg', 'product_CHRONO13' => [1]],
            1500.0,
        ];

        yield 'missing configuration keeps weight unchanged' => [
            ['product_CHRONO13' => [1]],
            1500.0,
        ];
    }
}
