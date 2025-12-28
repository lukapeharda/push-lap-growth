# PushLapGrowth PHP Client

A PHP client for the [PushLapGrowth API](https://developers.pushlapgrowth.com/api-reference/).

## Installation

Install via Composer:

```bash
composer require lukapeharda/pushlapgrowth
```

## Requirements

- PHP 7.4 or higher
- Guzzle HTTP Client

## Usage (Standard PHP)

You can use the client in any PHP application by instantiating the `Client` class with your API Token.

```php
use PushLapGrowth\Client;
use PushLapGrowth\DTO\CreateSaleData;
use PushLapGrowth\DTO\UpdateSaleData;
use PushLapGrowth\Exceptions\ValidationException;

// Initialize Client
$client = new Client('YOUR_API_TOKEN');

try {
    // 1. Create a Sale
    $data = new CreateSaleData(
        100.0,          // totalEarned
        'ref_123',      // referralId (optional)
        'user@example.com' // email (optional)
    );
    $response = $client->createSale($data);
    print_r($response);

    // 2. Update a Sale (using Internal ID)
    $updateData = new UpdateSaleData(
        $response['id'], // saleId
        'New Name',      // name
        null,            // email
        150.0            // totalEarned
    );
    $client->updateSale($updateData);

    // 3. Update a Sale (using External ID)
    // Useful if you only store your own system's IDs
    $externalUpdateData = new UpdateSaleData(
        null, // ID looked up automatically
        null,
        null,
        250.0
    );
    $client->updateSaleUsingExternalId('my_external_id_123', $externalUpdateData);

    // 4. Delete a Sale
    $client->deleteSale($response['id']);

    // 5. Delete a Sale (using External ID)
    $client->deleteSaleUsingExternalId('my_external_id_123');

} catch (ValidationException $e) {
    // Handle Validation Errors (422)
    print_r($e->getErrors());
} catch (\Exception $e) {
    // Handle other errors
    echo $e->getMessage();
}
```

## Usage (Laravel)

This package includes a Service Provider and Facade-like behavior for Laravel 5.6+ and above.

### 1. Configuration

Publish the configuration file (optional):

```bash
php artisan vendor:publish --provider="PushLapGrowth\PushLapGrowthServiceProvider" --tag="config"
```

Add your API Token to `.env`:

```env
PUSHLAPGROWTH_API_TOKEN=your_token_here
```

### 2. Dependency Injection

You can type-hint the `Client` class in your Controllers, Jobs, or Services.

```php
use PushLapGrowth\Client;
use PushLapGrowth\DTO\CreateSaleData;

class SaleController extends Controller
{
    public function store(Client $client)
    {
        $data = new CreateSaleData(50.0, 'ref_abc');
        $client->createSale($data);

        return response()->json(['status' => 'success']);
    }
}
```

## Error Handling

The client throws specific exceptions for different error scenarios:

- `PushLapGrowth\Exceptions\ValidationException`: Thrown on 422 Unprocessable Entity. Use `getErrors()` to see details.
- `PushLapGrowth\Exceptions\NotFoundException`: Thrown on 404 Not Found.
- `PushLapGrowth\Exceptions\ApiException`: Thrown on other API errors (500, 400, etc.).

All exceptions extend `PushLapGrowth\Exceptions\PushLapGrowthException`.

## Testing

Run the test suite:

```bash
vendor/bin/phpunit
```
