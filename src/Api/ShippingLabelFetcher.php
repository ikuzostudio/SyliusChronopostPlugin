<?php

declare(strict_types=1);

namespace Ikuzo\SyliusChronopostPlugin\Api;

use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

class ShippingLabelFetcher implements ShippingLabelFetcherInterface
{
    private FlashBagInterface $flashBag;
    private SoapClientInterface $soapClient;

    public function __construct(FlashBagInterface $flashBag, SoapClientInterface $soapClient)
    {
        $this->flashBag = $flashBag;
        $this->soapClient = $soapClient;
    }

    public function createShipment($shippingGateway, $shipment, float $weight): void
    {
        $data = $this->removeNullFromArray([
            'headerValue' => $this->getHeaderValue($shippingGateway),
            'shipperValue' => $this->getShipperValue($shippingGateway),
            'customerValue' => $this->getCustomerValue($shipment),
            'recipientValue' => $this->getRecipientValue($shipment),
            'refValue' => $this->getRefValue($shippingGateway, $shipment),
            'skybillValue' => $this->getSkybillValue($shippingGateway, $shipment, $weight),
            'skybillParamsValue' => $this->getSkybillParams($shippingGateway),
            'password' => $shippingGateway->getConfigValue('password'),
        ]);

        try {
            $this->response = $this->soapClient->createShipment($data);
        } catch (\SoapFault $exception) {
            $this->flashBag->add(
                'error',
                sprintf(
                    'Chronopost Service for #%s order: %s',
                    $shipment->getOrder()->getNumber(),
                    $exception->getMessage()
                )
            );
        }

        return;
    }

    private function getSkybillParams($shippingGateway): array
    {
        return [
            'mode' => $shippingGateway->getConfigValue('print_mode')
        ];
    }

    private function getSkybillValue($shippingGateway, $shipment, $weight): array
    {
        return [
            'evtCode' => 'DC',
            'productCode' => $this->guessProductCode($this->guessProductType($shippingGateway, $shipment)),
            'shipDate' => date('c'),
            'shipHour' => date('G'),
            'Weight' => ($weight > 0) ? $weight : 1,
            'weightUnit' => 'KGM',
            'service' => 0,
            'objectType' => 'MAR',
        ];
    }

    private function getRefValue($shippingGateway, $shipment): array
    {
        $data = [
            'shipperRef' => $shipment->getOrder()->getNumber(),
            'recipientRef' => $shipment->getOrder()->getCustomer()->getId(),
            'customerSkybillNumber' => $shipment->getId()
        ];

        if ($this->guessProductType($shippingGateway, $shipment) === 'CHRONORELAIS')
        {
            if (method_exists($shipment, 'getPickupPointId') && $shipment->getPickupPointId() && preg_match('/^.*---([a-zA-Z0-9]+)---.*$/', $shipment->getPickupPointId(), $matches)) {
                $data['recipientRef'] = $matches[1];
            } else {
                throw new \Exception("Cannot guess pickup point ID for :".$shipment->getPickupPointId(), 1);
            }
        }

        return $data;
    }

    private function getHeaderValue($shippingGateway): array
    {
        return [
            'accountNumber' => (int)$shippingGateway->getConfigValue('contractNumber'),
            'idEmit' => 'CHRFR',
            'identWebPro' => null,
            'subAccount' => null,
        ];
    }

    private function getShipperValue($shippingGateway): array
    {
        return [
            'shipperAdress1' => $shippingGateway->getConfigValue('expeditorAddress')['address1'],
            'shipperAdress2' => '',
            'shipperCity' => $shippingGateway->getConfigValue('expeditorAddress')['city'],
            'shipperCivility' => 'M',
            'shipperContactName' => $shippingGateway->getConfigValue('expeditorAddress')['company'],
            'shipperCountry' => $shippingGateway->getConfigValue('expeditorAddress')['country'],
            'shipperEmail' => $shippingGateway->getConfigValue('expeditorAddress')['email'],
            'shipperMobilePhone' => $shippingGateway->getConfigValue('expeditorAddress')['mobile'],
            'shipperName' => $shippingGateway->getConfigValue('expeditorAddress')['company'],
            'shipperName2' => null,
            'shipperPhone' => $shippingGateway->getConfigValue('expeditorAddress')['phone'],
            'shipperPreAlert' => '0',
            'shipperZipCode' => $shippingGateway->getConfigValue('expeditorAddress')['zipcode'],
        ];
    }

    public function getCustomerValue($shipment): array
    {
        $shippingAddress = $shipment->getOrder()->getShippingAddress();

        $data = [
            'customerCivility' => 'E',
            'customerName' => $shippingAddress->getLastName(),
            'customerName2' => $shippingAddress->getFirstName(),
            'customerAdress1' => $shippingAddress->getStreet(),
            'customerZipCode' => $shippingAddress->getPostcode(),
            'customerCity' => $shippingAddress->getCity(),
            'customerCountry' => $shippingAddress->getCountryCode(),
            'customerContactName' => $shippingAddress->getLastName().' '.$shippingAddress->getFirstName(),
            'customerEmail' => null,
            'customerPhone' => $shippingAddress->getPhoneNumber(),
            'customerMobilePhone' => $shippingAddress->getPhoneNumber(),
            'customerPreAlert' => '0'
        ];

        if ($shippingAddress->getCustomer() !== null) {
            $data['customerEmail'] = $shippingAddress->getCustomer()->getEmail();
        }

        if ($shippingAddress->getCustomer() !== null) {
            switch ($shippingAddress->getCustomer()->getGender()) {
                case 'm':
                    $data['customerCivility'] = 'M';
                    break;

                case 'f':
                    $data['customerCivility'] = 'L';
                    break;
                
                default:
                    $data['customerCivility'] = 'E';
                    break;
            }
        }

        return $data;
    }

    private function getRecipientValue($shipment): array
    {
        $shippingAddress = $shipment->getOrder()->getShippingAddress();

        $data = [
            'recipientCivility' => 'E',
            'recipientName' => $shippingAddress->getLastName(),
            'recipientName2' => $shippingAddress->getFirstName(),
            'recipientAdress1' => $shippingAddress->getStreet(),
            'recipientZipCode' => $shippingAddress->getPostcode(),
            'recipientCity' => $shippingAddress->getCity(),
            'recipientCountry' => $shippingAddress->getCountryCode(),
            'recipientContactName' => $shippingAddress->getLastName().' '.$shippingAddress->getFirstName(),
            'recipientEmail' => null,
            'recipientPhone' => $shippingAddress->getPhoneNumber(),
            'recipientMobilePhone' => $shippingAddress->getPhoneNumber(),
            'recipientPreAlert' => '0'
        ];

        if ($shippingAddress->getCustomer() !== null) {
            $data['recipientEmail'] = $shippingAddress->getCustomer()->getEmail();
        }

        if ($shippingAddress->getCustomer() !== null) {
            switch ($shippingAddress->getCustomer()->getGender()) {
                case 'm':
                    $data['recipientCivility'] = 'M';
                    break;

                case 'f':
                    $data['recipientCivility'] = 'L';
                    break;
                
                default:
                    $data['recipientCivility'] = 'E';
                    break;
            }
        }

        return $data;
    }

    private function removeNullFromArray(array $array): array
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = $this->removeNullFromArray($value);
            }

            if ($value === null) {
                if ($key == 'subAccount' || $key == 'identWebPro') {
                    continue;
                }

                unset($array[$key]);
            }
        }

        return $array;
    }

    private function guessProductCode(string $productType): string
    {
        switch ($productType) {
            case 'CHRONO13':
                return '01';
                break;

            case 'CHRONO10':
                return '02';
                break;

            case 'CHRONO18':
                return '16';
                break;

            case 'CHRONORELAIS':
            case 'RELAISDOM':
                return '86';
                break;

            case 'CHRONOCLASSIC':
                return '44';
                break;

            case 'CHRONOEXPRESS':
                return '17';
                break;
            
            case 'RELAISEUROPE':
                return '49';
                break;

            case 'SAMEDAY':
                return '2P';
                break;

            case 'CHRONORDV':
                return '2E';
                break;
        }
    }

    public function getLabelContent(): ?array
    {
        if (!isset($this->response->return->skybill)) {
            $this->flashBag->add('error', $this->response->return->errorMessage);
            return null;
        }

        $this->flashBag->add('success', 'bitbag.ui.shipment_data_has_been_exported');

        return [
            'parcelNumber' => $this->response->return->skybillNumber,
            'label' => $this->response->return->skybill
        ];
    }

    private function guessProductType($shippingGateway, $shipment): string
    {
        $method = $shipment->getMethod();

        // if (method_exists($shipment, 'getPickupPointId') && $shipment->getPickupPointId() && preg_match('/^.*---(\d{6})---.*$/', $shipment->getPickupPointId(), $matches)) {
        //     if (method_exists($shipment, 'getColishipPickupRaw') && !empty($shipment->getColishipPickupRaw()) && isset($shipment->getColishipPickupRaw()['type'])) {
        //         // if ($this->isProductCodePickupMethod($shipment->getColishipPickupRaw()['type'])) {
        //         //     return $shipment->getColishipPickupRaw()['type'];
        //         // }
        //     }
        // }

        foreach ($shippingGateway->getConfig() as $key => $value) {
            if (str_starts_with($key, 'product_')) {
                if (in_array($method->getId(), $value)) {
                    $productArr = explode('_', $key);
                    $productName = strtoupper($productArr[1]);
                    $productName = str_replace(':', '+', $productName);
                    // $product = str_replace('product_', '', $key);
                    return $productName;

                }
            }
        }

        throw new \Exception("Cant guess product type for this expedition. Checkout your gateway config", 1);

    }

}