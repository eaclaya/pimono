<?php

namespace App\Exceptions;

use Exception;

class TransactionFailedException extends Exception
{
    protected $code = 500;

    public function render($request)
    {
        return response()->json([
            'message' => $this->getMessage(),
            'error' => 'transaction_failed',
        ], 500);
    }
}
