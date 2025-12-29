<?php

namespace PushLapGrowth\DTO;

class CreateReferralData
{
    public $name;
    public $email;
    public $referredUserExternalId;
    public $affiliateId;
    public $affiliateEmail;
    public $promoCode;
    public $plan;
    public $status;

    public function __construct(
        string $name,
        string $email,
        string $referredUserExternalId,
        ?string $affiliateId = null,
        ?string $affiliateEmail = null,
        ?string $promoCode = null,
        ?string $plan = null,
        ?string $status = null
    ) {
        $this->name = $name;
        $this->email = $email;
        $this->referredUserExternalId = $referredUserExternalId;
        $this->affiliateId = $affiliateId;
        $this->affiliateEmail = $affiliateEmail;
        $this->promoCode = $promoCode;
        $this->plan = $plan;
        $this->status = $status;
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'email' => $this->email,
            'referredUserExternalId' => $this->referredUserExternalId,
            'affiliateId' => $this->affiliateId,
            'affiliateEmail' => $this->affiliateEmail,
            'promoCode' => $this->promoCode,
            'plan' => $this->plan,
            'status' => $this->status,
        ], function ($value) {
            return !is_null($value);
        });
    }
}
