<?php declare(strict_types=1);

namespace PrestaShop\Marketplace\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;

class MarketplaceClient
{
    /**
     * @var Client
     */
    private $client;

    public function __construct(string $apiKey)
    {
        $this->client = new Client(
            [
                'base_uri' => 'https://addons.prestashop.com/request/index.php',
                'headers' => ['api-key' => $apiKey],
            ],
        );

    }

    public function publishExtension(array $data, string $archivePath = ''): Response
    {
        $multipart = [
            [
                'Content-type' => 'multipart/form-data',
                'name' => 'zip',
                'contents' => fopen($archivePath, 'r'),
            ],
        ];
        foreach ($data as $key => $value) {
            $multipart[] = [
                'name' => $key,
                'contents' => 'value',
            ];
        }

        return $this->client->request('POST', '?method=module_push', [
            'multipart' => $multipart,
        ]);
    }
}