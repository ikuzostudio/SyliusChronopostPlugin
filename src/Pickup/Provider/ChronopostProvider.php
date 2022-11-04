<?php

declare(strict_types=1);

namespace Ikuzo\SyliusChronopostPlugin\Pickup\Provider;

use Ikuzo\SyliusChronopostPlugin\Api\SoapClientInterface;
use Setono\SyliusPickupPointPlugin\Model\PickupPoint;
use Setono\SyliusPickupPointPlugin\Model\PickupPointCode;
use Setono\SyliusPickupPointPlugin\Model\PickupPointInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Webmozart\Assert\Assert;
use Setono\SyliusPickupPointPlugin\Provider\Provider;
use stdClass;

final class ChronopostProvider extends Provider
{
    public function __construct(
        private SoapClientInterface $soapClient, 
        private FactoryInterface $pickupPointFactory
    )
    {
    }

    public function transform(stdClass $point): PickupPointInterface
    {
        $pickupPoint = $this->pickupPointFactory->createNew();
        Assert::isInstanceOf($pickupPoint, PickupPointInterface::class);   

        $pickupPoint->setCode(new PickupPointCode($point->identifiantChronopostPointA2PAS, $this->getCode(), 'FR'));
        $pickupPoint->setName($point->nomEnseigne);
        $pickupPoint->setAddress($point->adresse1);
        $pickupPoint->setZipCode($point->codePostal);
        $pickupPoint->setCity($point->localite);
        $pickupPoint->setCountry('FR');
        $pickupPoint->setLatitude($point->coordGeoLatitude);
        $pickupPoint->setLongitude($point->coordGeoLongitude);

        return $pickupPoint;
    }

    /**
     * Will return an array of pickup points
     *
     * @return iterable<PickupPointInterface>
     */
    public function findPickupPoints(OrderInterface $order): iterable
    {
        $pickupPoints = [];
        $shippingAddress = $order->getShippingAddress();
        
        if (null === $shippingAddress) {
            return [];
        }

        $points = $this->soapClient->findPickupPoints($shippingAddress->getPostcode());

        foreach ($points as $point) {
            $pickupPoints[] = $this->transform($point);
        }

        return $pickupPoints;
    }

    public function findPickupPoint(PickupPointCode $code): ?PickupPointInterface
    {
        $point = $this->soapClient->findPickupPoint($code->getIdPart());

        if ($point) {
            return $this->transform($point);
        }

        return null;
    }

    public function findAllPickupPoints(): iterable
    {
        dd('ok');
    }

    public function getCode(): string
    {
        return 'chronopost';
    }

    public function getName(): string
    {
        return 'Chronopost';
    }
}