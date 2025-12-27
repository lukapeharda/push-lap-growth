<?php

namespace PushLapGrowth\DTO;

class UpdateSaleData
{
    public $saleId;
    public $name;
    public $email;
    public $totalEarned;
    public $commissionRate;

    public function __construct(
        ?int $saleId = null,
        ?string $name = null,
        ?string $email = null,
        ?float $totalEarned = null,
        ?float $commissionRate = null
    ) {
        $this->saleId = $saleId;
        $this->name = $name;
        $this->email = $email;
        $this->totalEarned = $totalEarned;
        $this->commissionRate = $commissionRate;
    }

    public function toArray(): array
    {
        return array_filter([
            'saleId' => $this->saleId,
            'name' => $this->name,
            'email' => $this->email,
            'totalEarned' => $this->totalEarned,
            'commissionRate' => $this->commissionRate,
        ], function ($value) {
            return !is_null($value);
        });
    }
}
