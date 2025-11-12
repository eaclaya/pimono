<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransactionStoreRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\TransactionService;

class TransactionController extends Controller
{
    public function __construct(private TransactionService $transactionService) {}

    public function index()
    {
        $transactions = Transaction::with(['sender:id,name,email', 'receiver:id,name,email'])
            ->where('receiver_id', auth()->id())
            ->orWhere('sender_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return TransactionResource::collection($transactions);
    }

    public function store(TransactionStoreRequest $request)
    {
        $senderId = auth()->id();
        $receiverId = $request->input('receiver_id');
        $amount = $request->input('amount');

        $transaction = $this->transactionService->createTransaction($senderId, $receiverId, $amount);

        return new TransactionResource($transaction);
    }
}
