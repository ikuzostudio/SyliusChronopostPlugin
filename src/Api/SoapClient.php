<?php

/*
 * This file was created by the developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * another great project.
 * You can find more information about us on https://bitbag.shop and write us
 * an email on mikolaj.krol@bitbag.pl.
 */

declare(strict_types=1);

namespace Ikuzo\SyliusChronopostPlugin\Api;

use KeepItSimple\Http\Soap\MTOMSoapClient;

final class SoapClient implements SoapClientInterface
{
    /**
     * @throws SoapFault
     */
    public function createShipment($requestData)
    {
        $client = new MTOMSoapClient('https://ws.chronopost.fr/shipping-cxf/ShippingServiceWS?wsdl', [
            'wsdl_cache' => 0,
            'trace' => 1,
            'exceptions' => true,
            'soap_version' => SOAP_1_1,
            'encoding' => 'utf-8'
        ]);

        try {
            $result = $client->shippingV3($requestData);
        } catch (\Throwable $th) {
            dd($th);
        }

        return $result;

    }

    public function findPickupPoints(string $zipcode): array
    {
        $client = new MTOMSoapClient('https://www.chronopost.fr/recherchebt-ws-cxf/PointRelaisServiceWS?wsdl', [
            'wsdl_cache' => 0,
            'trace' => 1,
            'exceptions' => true,
            'soap_version' => SOAP_1_1,
            'encoding' => 'utf-8'
        ]);

        $result = $client->rechercheBtParCodeproduitEtCodepostalEtDate([
            'codePostal' => $zipcode,
            'date' => date('d/m/Y')
        ]);

        return $result->return;
    }

    public function findPickupPoint(string $code): ?stdClass
    {
        $client = new MTOMSoapClient('https://www.chronopost.fr/recherchebt-ws-cxf/PointRelaisServiceWS?wsdl', [
            'wsdl_cache' => 0,
            'trace' => 1,
            'exceptions' => true,
            'soap_version' => SOAP_1_1,
            'encoding' => 'utf-8'
        ]);

        $result = $client->rechercheBtParIdChronopostA2Pas([
            'id' => $code
        ]);

        return $result->return;
    }
}
