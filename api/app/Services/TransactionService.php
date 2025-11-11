<?php
namespace App\Services;

use App\Models\Transaction;
use App\Http\Resources\TransactionResource;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    public function createTransaction($user, $data): Transaction
    {
        DB::beginTransaction();

        $transaction = Transaction::create([
            'sender_id' => $user->id,
            'receiver_id' => $data['receiver_id'],
            'amount' => $data['amount'],
        ]);

        $user->update([
            'balance' => $user->balance - $data['amount'],
        ]);

        $receiver->update([
            'balance' => $receiver->balance + $data['amount'],
        ]);

        DB::commit();

        return $transaction;
    }
}
