<?php 

namespace App\Service;

use PaypalServerSdkLib\PaypalServerSdkClientBuilder;
use PaypalServerSdkLib\Authentication\ClientCredentialsAuthCredentialsBuilder;
use PaypalServerSdkLib\Environment;
use PaypalServerSdkLib\Models\Builders\OrderRequestBuilder;
use PaypalServerSdkLib\Models\Builders\PurchaseUnitRequestBuilder;
use PaypalServerSdkLib\Models\Builders\AmountWithBreakdownBuilder;

class PaypalService
{
    private $client;

    public function __construct(string $clientId, string $clientSecret, string $paypalMode)
    {
        $environment = ($paypalMode === 'prod') ? Environment::PRODUCTION : Environment::SANDBOX;

        $this->client = PaypalServerSdkClientBuilder::init()
            ->clientCredentialsAuthCredentials(
                ClientCredentialsAuthCredentialsBuilder::init($clientId, $clientSecret)
            )
            ->environment($environment) 
            ->build();
    }

    public function createOrder(float $totalAmount): array
    {
        $orderBody = [
            "body" => OrderRequestBuilder::init("CAPTURE", [
                PurchaseUnitRequestBuilder::init(
                    AmountWithBreakdownBuilder::init("EUR", (string)$totalAmount)->build()
                )->build()
            ])->build()
        ];

        $apiResponse = $this->client->getOrdersController()->createOrder($orderBody);
        return json_decode($apiResponse->getBody(), true);
    }

    public function captureOrder(string $paypalOrderId): array
    {
        $apiResponse = $this->client->getOrdersController()->captureOrder(['id' => $paypalOrderId]);
        return json_decode($apiResponse->getBody(), true);
    }
}