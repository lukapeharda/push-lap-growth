<?php

namespace PushLapGrowth\Tests;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Middleware;
use PHPUnit\Framework\TestCase;
use PushLapGrowth\Client;
use PushLapGrowth\DTO\CreateSaleData;
use PushLapGrowth\DTO\UpdateSaleData;

class ClientTest extends TestCase
{
    protected $container = [];
    protected $mockHandler;
    protected $client;

    protected function setUp(): void
    {
        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);

        // Add history middleware to intercept requests
        $this->container = [];
        $history = Middleware::history($this->container);
        $handlerStack->push($history);

        $guzzleClient = new GuzzleClient(['handler' => $handlerStack]);

        $this->client = new Client('test-token', $guzzleClient);
    }

    public function testCreateSale()
    {
        $this->mockHandler->append(new Response(201, [], json_encode(['status' => 'success', 'id' => 123])));

        $data = new CreateSaleData(100.0, 'ref123');
        $result = $this->client->createSale($data);

        $this->assertEquals(['status' => 'success', 'id' => 123], $result);

        // Verify request
        $this->assertCount(1, $this->container);
        $request = $this->container[0]['request'];
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('sales', $request->getUri()->getPath());
        $this->assertEquals('Bearer test-token', $request->getHeaderLine('Authorization'));

        $body = json_decode($request->getBody(), true);
        $this->assertEquals(100, $body['totalEarned']);
        $this->assertEquals('ref123', $body['referralId']);
    }

    public function testUpdateSale()
    {
        $this->mockHandler->append(new Response(200, [], json_encode(['status' => 'updated'])));

        $data = new UpdateSaleData(123, null, null, 150.0);
        $result = $this->client->updateSale($data);

        $this->assertEquals(['status' => 'updated'], $result);

        // Verify request
        $this->assertCount(1, $this->container);
        $request = $this->container[0]['request'];
        $this->assertEquals('PUT', $request->getMethod());
        $this->assertEquals('sales', $request->getUri()->getPath());

        $body = json_decode($request->getBody(), true);
        $this->assertEquals(123, $body['saleId']);
        $this->assertEquals(150, $body['totalEarned']);
    }

    public function testDeleteSale()
    {
        $this->mockHandler->append(new Response(200, [], json_encode(['status' => 'deleted'])));

        $result = $this->client->deleteSale(123);

        $this->assertEquals(['status' => 'deleted'], $result);

        // Verify request
        $this->assertCount(1, $this->container);
        $request = $this->container[0]['request'];
        $this->assertEquals('DELETE', $request->getMethod());
        $this->assertEquals('sales', $request->getUri()->getPath());

        $body = json_decode($request->getBody(), true);
        $this->assertEquals(123, $body['saleId']);
    }

    public function testDeleteSaleUsingExternalId()
    {
        // Mock two responses: one for getting the sale, one for deleting it
        $this->mockHandler->append(
            new Response(200, [], json_encode([['id' => 999, 'externalId' => 'ext_999']])),
            new Response(200, [], json_encode(['status' => 'deleted']))
        );

        $result = $this->client->deleteSaleUsingExternalId('ext_999');

        $this->assertEquals(['status' => 'deleted'], $result);

        // Verify requests
        $this->assertCount(2, $this->container);

        // Request 1: Get Sale
        $request1 = $this->container[0]['request'];
        $this->assertEquals('GET', $request1->getMethod());
        $this->assertEquals('sales', $request1->getUri()->getPath());
        $this->assertEquals('saleExternalId=ext_999', $request1->getUri()->getQuery());

        // Request 2: Delete Sale
        $request2 = $this->container[1]['request'];
        $this->assertEquals('DELETE', $request2->getMethod());
        $this->assertEquals('sales', $request2->getUri()->getPath());
        $body = json_decode($request2->getBody(), true);
        $this->assertEquals(999, $body['saleId']);
    }

    public function testUpdateSaleUsingExternalId()
    {
        // Mock two responses: one for getting the sale, one for updating it
        $this->mockHandler->append(
            new Response(200, [], json_encode([['id' => 888, 'externalId' => 'ext_888']])),
            new Response(200, [], json_encode(['status' => 'updated']))
        );

        // Create update data without ID initially
        $data = new UpdateSaleData(null, null, null, 200.0);
        $result = $this->client->updateSaleUsingExternalId('ext_888', $data);

        $this->assertEquals(['status' => 'updated'], $result);

        // Verify requests
        $this->assertCount(2, $this->container);

        // Request 1: Get Sale
        $request1 = $this->container[0]['request'];
        $this->assertEquals('GET', $request1->getMethod());
        $this->assertEquals('sales', $request1->getUri()->getPath());
        $this->assertEquals('saleExternalId=ext_888', $request1->getUri()->getQuery());

        // Request 2: Update Sale
        $request2 = $this->container[1]['request'];
        $this->assertEquals('PUT', $request2->getMethod());
        $this->assertEquals('sales', $request2->getUri()->getPath());
        $body = json_decode($request2->getBody(), true);
        $this->assertEquals(888, $body['saleId']);
        $this->assertEquals(200, $body['totalEarned']);
    }

    public function testNotFoundException()
    {
        $this->mockHandler->append(new Response(404, [], json_encode(['message' => 'Sale not found'])));

        $this->expectException(\PushLapGrowth\Exceptions\NotFoundException::class);
        $this->expectExceptionMessage('Sale not found');

        $this->client->deleteSale(999);
    }

    public function testValidationException()
    {
        $this->mockHandler->append(new Response(422, [], json_encode([
            'message' => 'Validation failed',
            'errors' => ['email' => ['Email is required']]
        ])));

        $this->expectException(\PushLapGrowth\Exceptions\ValidationException::class);
        $this->expectExceptionMessage('Validation failed');

        try {
            $data = new CreateSaleData(100.0);
            $this->client->createSale($data);
        } catch (\PushLapGrowth\Exceptions\ValidationException $e) {
            $this->assertEquals(['email' => ['Email is required']], $e->getErrors());
            throw $e;
        }
    }

    public function testApiException()
    {
        $this->mockHandler->append(new Response(500, [], json_encode(['message' => 'Server error'])));

        $this->expectException(\PushLapGrowth\Exceptions\ApiException::class);
        $this->expectExceptionMessage('Server error');

        $this->client->deleteSale(123);
    }
}
