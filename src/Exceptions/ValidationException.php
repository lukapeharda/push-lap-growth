<?php

namespace PushLapGrowth\Exceptions;

class ValidationException extends PushLapGrowthException
{
    /**
     * @var array
     */
    protected $errors;

    public function __construct(string $message, array $errors = [], int $code = 422)
    {
        parent::__construct($message, $code);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
