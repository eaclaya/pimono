<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransactionStoreRequest;
use App\Models\Transaction;
use App\Http\Resources\TransactionResource;
use App\Services\TransactionService;

class TransactionController extends Controller
{
    public function __construct(private TransactionService $transactionService)
    {
    }

    public function index()
    {
        $transactions = auth()->user()
            ->transactions()
            ->with('sender', 'receiver')
            ->get();

        return TransactionResource::collection($transactions);
    }

    public function store(TransactionStoreRequest $request)
    {
        $transaction = $this->transactionService->createTransaction(auth()->user(), $request->validated());

        return new TransactionResource($transaction);
    }
}
