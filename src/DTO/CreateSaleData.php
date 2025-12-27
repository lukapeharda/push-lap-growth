<?php

namespace PushLapGrowth\DTO;

class CreateSaleData
{
    public $totalEarned;
    public $referralId;
    public $email;
    public $promoCode;
    public $name;
    public $externalId;
    public $externalInvoiceId;
    public $commissionRate;

    public function __construct(
        float $totalEarned,
        ?string $referralId = null,
        ?string $email = null,
        ?string $promoCode = null,
        ?string $name = null,
        ?string $externalId = null,
        ?string $externalInvoiceId = null,
        ?float $commissionRate = null
    ) {
        $this->totalEarned = $totalEarned;
        $this->referralId = $referralId;
        $this->email = $email;
        $this->promoCode = $promoCode;
        $this->name = $name;
        $this->externalId = $externalId;
        $this->externalInvoiceId = $externalInvoiceId;
        $this->commissionRate = $commissionRate;
    }

    public function toArray(): array
    {
        return array_filter([
            'totalEarned' => $this->totalEarned,
            'referralId' => $this->referralId,
            'email' => $this->email,
            'promoCode' => $this->promoCode,
            'name' => $this->name,
            'externalId' => $this->externalId,
            'externalInvoiceId' => $this->externalInvoiceId,
            'commissionRate' => $this->commissionRate,
        ], function ($value) {
            return !is_null($value);
        });
    }
}
