<?php

declare(strict_types=1);

namespace Ikuzo\SyliusChronopostPlugin\Api;

interface ShippingLabelFetcherInterface
{
    public function createShipment($shippingGateway, $shipment, float $weight): void;

    public function getLabelContent(): ?array;
}
