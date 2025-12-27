<?php

namespace PushLapGrowth;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use PushLapGrowth\DTO\CreateSaleData;
use PushLapGrowth\DTO\UpdateSaleData;

class Client
{
    /**
     * @var GuzzleClient
     */
    protected $client;

    /**
     * @param string $apiToken The API token for authentication.
     * @param GuzzleClient|null $client Optional Guzzle client for testing/customization.
     */
    public function __construct(string $apiToken, ?GuzzleClient $client = null)
    {
        $this->client = $client ?? new GuzzleClient([
            'base_uri' => 'https://www.pushlapgrowth.com/api/v1/',
            'headers' => [
                'Authorization' => 'Bearer ' . $apiToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * Create a new sale.
     *
     * @param CreateSaleData $data The sale data.
     * @return array The response data.
     * @throws GuzzleException
     */
    public function createSale(CreateSaleData $data): array
    {
        $response = $this->client->post('sales', [
            'json' => $data->toArray(),
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Update an existing sale.
     *
     * @param UpdateSaleData $data The update data.
     * @return array The response data.
     * @throws GuzzleException
     */
    public function updateSale(UpdateSaleData $data): array
    {
        $response = $this->client->put('sales', [
            'json' => $data->toArray(),
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Delete a sale.
     *
     * @param int $saleId The ID of the sale to delete.
     * @return array The response data.
     * @throws GuzzleException
     */
    public function deleteSale(int $saleId): array
    {
        $response = $this->client->delete('sales', [
            'json' => ['saleId' => $saleId],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Get a sale by its external ID.
     *
     * @param string $externalId The external ID of the sale.
     * @return array The sale data.
     * @throws \Exception If the sale is not found.
     * @throws GuzzleException
     */
    public function getSaleByExternalId(string $externalId): array
    {
        $response = $this->client->get('sales', [
            'query' => ['saleExternalId' => $externalId],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        // The API returns an array of sales. We expect one if using unique external ID.
        // Assuming the response structure is a list or contains a list.
        // Based on docs, it returns a list of matching sales.

        if (empty($data) || !isset($data[0])) {
            throw new \Exception("Sale with external ID '{$externalId}' not found.");
        }

        return $data[0];
    }

    /**
     * Delete a sale using its external ID.
     *
     * @param string $externalId The external ID of the sale.
     * @return array The response data.
     * @throws GuzzleException
     * @throws \Exception
     */
    public function deleteSaleUsingExternalId(string $externalId): array
    {
        $sale = $this->getSaleByExternalId($externalId);
        return $this->deleteSale($sale['id']);
    }

    /**
     * Update a sale using its external ID.
     *
     * @param string $externalId The external ID of the sale.
     * @param UpdateSaleData $data The update data.
     * @return array The response data.
     * @throws GuzzleException
     * @throws \Exception
     */
    public function updateSaleUsingExternalId(string $externalId, UpdateSaleData $data): array
    {
        $sale = $this->getSaleByExternalId($externalId);

        // Update the saleId in the DTO
        $data->saleId = $sale['id'];

        return $this->updateSale($data);
    }
}
