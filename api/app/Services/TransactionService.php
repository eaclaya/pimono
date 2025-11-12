<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\TransactionFailedException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Events\TransactionCreated;

class TransactionService
{
    const COMMISSION_PERCENTAGE = 0.015;

    public function createTransaction(int $senderId, int $receiverId, float $amount): Transaction
    {
        try {
            return DB::transaction(function () use ($senderId, $receiverId, $amount) {
                $ids = [$senderId, $receiverId];
                sort($ids);

                $users = User::whereIn('id', $ids)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                // Validate both users exist
                if ($users->count() !== 2) {
                    throw new TransactionFailedException('One or both users not found');
                }

                $sender = $users[$senderId];
                $receiver = $users[$receiverId];

                // Prevent self-transaction
                if ($senderId === $receiverId) {
                    throw new TransactionFailedException('Cannot transaction to yourself');
                }

                // Calculate commission (1.5% of transfer amount)
                $commission = round($amount * self::COMMISSION_PERCENTAGE, 4);
                $totalDebit = $amount + $commission;

                // Check if sender has sufficient balance for amount + commission
                if ($sender->balance < $totalDebit) {
                    throw new InsufficientBalanceException($totalDebit, $sender->balance);
                }

                // Debit sender: amount + commission
                DB::update('UPDATE users SET balance = balance - ? WHERE id = ? AND balance >= ?', [$totalDebit, $senderId, $totalDebit]);

                // Credit receiver: amount only (no commission)
                DB::update('UPDATE users SET balance = balance + ? WHERE id = ?', [$amount, $receiverId]);

                // Create transaction record with commission
                $transaction = Transaction::create([
                    'sender_id' => $senderId,
                    'receiver_id' => $receiverId,
                    'amount' => $amount,
                    'commission_fee' => $commission,
                ]);

                event(new TransactionCreated($transaction));

                return $transaction;
            });

        } catch (InsufficientBalanceException $e) {
            // Re-throw validation exceptions
            throw $e;

        } catch (\Exception $e) {

            Log::error('Transaction failed', [
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'amount' => $amount,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new TransactionFailedException(
                'Transaction failed: ' . $e->getMessage()
            );
        }
    }
}

