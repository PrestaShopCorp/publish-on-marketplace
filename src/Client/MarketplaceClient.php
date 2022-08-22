<?php

declare(strict_types=1);

namespace PrestaShop\Marketplace\Client;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

class MarketplaceClient
{
    const MARKETPLACE_URL = 'https://api.addons.prestashop.com/request/index.php';

    /**
     * @var Client
     */
    private $client;

    public function __construct(string $apiKey)
    {
        $this->client = new Client(
            [
                'base_uri' => self::MARKETPLACE_URL,
                'headers' => ['api-key' => $apiKey],
            ]
        );
    }

    public function publishExtension(array $data, string $archivePath): ResponseInterface
    {
        $multipart = [
            [
                //'Content-type' => 'multipart/form-data',
                'name' => 'zip',
                'contents' => fopen($archivePath, 'r'),
            ],
        ];
        foreach ($data as $key => $value) {
            $multipart[] = [
                'name' => $key,
                'contents' => $value,
            ];
        }

        return $this->client->request('POST', '?method=module_push', [
            'multipart' => $multipart,
        ]);
    }
}
