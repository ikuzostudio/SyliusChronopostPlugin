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
            'recipientValue' => $this->getRecipientValue($shippingGateway, $shipment),
            'refValue' => $this->getRefValue($shippingGateway, $shipment),
            'skybillValue' => $this->getSkybillValue($shippingGateway, $shipment, $weight),
            'skybillParamsValue' => $this->getSkybillParams($shippingGateway),
            'password' => $shippingGateway->getConfigValue('password'),
        ]);

        $expirationDays = $shippingGateway->getConfigValue('fresh_expiration_days');

        if (
            in_array($this->guessProductType($shippingGateway, $shipment), ['CHRONOFRESH13', 'CHRONOFRESH18'], true)
            && (int) $expirationDays > 0
        ) {
            $data['scheduledValue'] = [
                'expirationDate' => (new \DateTimeImmutable('now'))->modify(sprintf('+%d days', (int) $expirationDays))->format('c'),
            ];
        }

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
        if ($shippingGateway->getConfigValue('weight_unit') === 'g') {
            $weight /= 1000;
        }

        return [
            'evtCode' => 'DC',
            'productCode' => $this->guessProductCode($this->guessProductType($shippingGateway, $shipment)),
            'shipDate' => date('c'),
            'shipHour' => date('G'),
            'weight' => ($weight > 0) ? $weight : 1,
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
            'accountNumber' => (int) $shippingGateway->getConfigValue('contractNumber'),
            'idEmit' => 'CHRFR',
            'identWebPro' => null,
            'subAccount' => $shippingGateway->getConfigValue('subAccount') ?: null,
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
        $order = $shipment->getOrder();
        $shippingAddress = $order->getShippingAddress();
        $customer = $order->getCustomer();

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

        if ($customer !== null) {
            $data['customerEmail'] = $customer->getEmail();
            switch ($customer->getGender()) {
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

    private function getRecipientValue($shippingGateway, $shipment): array
    {
        $order = $shipment->getOrder();
        $customer = $order->getCustomer();
        $shippingAddress = $order->getShippingAddress();

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
            'recipientPreAlert' => $shippingGateway->getConfigValue('recipientPreAlert') ? '1' : '0'
        ];

        if ($customer !== null) {
            $data['recipientEmail'] = $customer->getEmail();
            switch ($customer->getGender()) {
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
        $productCodes = [
            'CHRONO13' => '01',
            'CHRONO10' => '02',
            'CHRONO18' => '16',
            'CHRONORELAIS' => '86',
            'RELAISDOM' => '86',
            'CHRONOCLASSIC' => '44',
            'CHRONOEXPRESS' => '17',
            'RELAISEUROPE' => '49',
            'SAMEDAY' => '2P',
            'CHRONORDV' => '2E',
            'CHRONOFRESH13' => '2R',
            'CHRONOFRESH18' => '5Z',
            'CHRONOFREEZE13' => '2S',
            'SHOPTOSHOP' => '5X'
        ];

        return $productCodes[$productType] ?? '';
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
