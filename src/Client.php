<?php

namespace PushLapGrowth;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use PushLapGrowth\DTO\CreateSaleData;
use PushLapGrowth\DTO\CreateReferralData;
use PushLapGrowth\DTO\UpdateSaleData;
use PushLapGrowth\Exceptions\ApiException;
use PushLapGrowth\Exceptions\NotFoundException;
use PushLapGrowth\Exceptions\PushLapGrowthException;
use PushLapGrowth\Exceptions\ValidationException;

class Client
{
    /**
     * @var GuzzleClient
     */
    protected $client;

    /**
     * @var string
     */
    protected $apiToken;

    /**
     * @param string $apiToken The API token for authentication.
     * @param GuzzleClient|null $client Optional Guzzle client for testing/customization.
     */
    public function __construct(string $apiToken, ?GuzzleClient $client = null)
    {
        $this->apiToken = $apiToken;
        $this->client = $client ?? new GuzzleClient([
            'base_uri' => 'https://www.pushlapgrowth.com/api/v1/',
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'http_errors' => true, // Ensure Guzzle throws exceptions on 4xx/5xx
        ]);
    }

    /**
     * Create a new sale.
     *
     * @param CreateSaleData $data The sale data.
     * @return array The response data.
     * @throws PushLapGrowthException
     */
    public function createSale(CreateSaleData $data): array
    {
        return $this->request('POST', 'sales', [
            'json' => $data->toArray(),
        ]);
    }

    /**
     * Update an existing sale.
     *
     * @param UpdateSaleData $data The update data.
     * @return array The response data.
     * @throws PushLapGrowthException
     */
    public function updateSale(UpdateSaleData $data): array
    {
        return $this->request('PUT', 'sales', [
            'json' => $data->toArray(),
        ]);
    }

    /**
     * Delete a sale.
     *
     * @param int $saleId The ID of the sale to delete.
     * @return array The response data.
     * @throws PushLapGrowthException
     */
    public function deleteSale(int $saleId): array
    {
        return $this->request('DELETE', 'sales', [
            'json' => ['saleId' => $saleId],
        ]);
    }

    /**
     * Get a sale by its external ID.
     *
     * @param string $externalId The external ID of the sale.
     * @return array The sale data.
     * @throws PushLapGrowthException If the sale is not found.
     */
    public function getSaleByExternalId(string $externalId): array
    {
        $data = $this->request('GET', 'sales', [
            'query' => ['saleExternalId' => $externalId],
        ]);

        // The API returns an array of sales. We expect one if using unique external ID.
        if (empty($data) || !isset($data[0])) {
            throw new NotFoundException("Sale with external ID '{$externalId}' not found.");
        }

        return $data[0];
    }

    /**
     * Delete a sale using its external ID.
     *
     * @param string $externalId The external ID of the sale.
     * @return array The response data.
     * @throws PushLapGrowthException
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
     * @throws PushLapGrowthException
     */
    public function updateSaleUsingExternalId(string $externalId, UpdateSaleData $data): array
    {
        $sale = $this->getSaleByExternalId($externalId);

        // Update the saleId in the DTO
        $data->saleId = $sale['id'];

        return $this->updateSale($data);
    }

    /**
     * Create a new referral.
     *
     * @param CreateReferralData $data The referral data.
     * @return array The response data.
     * @throws PushLapGrowthException
     */
    public function createReferral(CreateReferralData $data): array
    {
        return $this->request('POST', 'referrals', [
            'json' => $data->toArray(),
        ]);
    }

    /**
     * Make a request to the API and handle exceptions.
     *
     * @param string $method
     * @param string $uri
     * @param array $options
     * @return array
     * @throws PushLapGrowthException
     */
    protected function request(string $method, string $uri, array $options = []): array
    {
        // Force Authorization header
        $options['headers']['Authorization'] = 'Bearer ' . $this->apiToken;

        try {
            $response = $this->client->request($method, $uri, $options);
            return json_decode($response->getBody()->getContents(), true);
        } catch (ClientException $e) {
            $statusCode = $e->getResponse()->getStatusCode();
            $body = $e->getResponse()->getBody()->getContents();
            $data = json_decode($body, true) ?? [];
            $message = $data['message'] ?? $e->getMessage();

            if ($statusCode === 404) {
                throw new NotFoundException($message, $statusCode);
            }

            if ($statusCode === 422) {
                $errors = $data['errors'] ?? [];
                throw new ValidationException($message, $errors, $statusCode);
            }

            throw new ApiException($message, $statusCode);
        } catch (GuzzleException $e) {
            throw new ApiException($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            throw new ApiException($e->getMessage(), $e->getCode());
        }
    }
}
