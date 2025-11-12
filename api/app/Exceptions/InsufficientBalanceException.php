<?php

namespace App\Exceptions;

use Exception;

class InsufficientBalanceException extends Exception
{
    protected $message = 'Insufficient balance for this transaction';

    protected $code = 422;

    public function __construct(float $required, float $available)
    {
        parent::__construct(
            "Insufficient balance. Required: {$required}, Available: {$available}"
        );
    }

    public function render($request)
    {
        return response()->json([
            'message' => $this->getMessage(),
            'error' => 'insufficient_balance',
        ], 422);
    }
}
