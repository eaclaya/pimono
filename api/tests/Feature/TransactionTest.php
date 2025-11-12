<?php

use App\Events\TransactionCreated;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Prevent actual event broadcasting during tests
    Queue::fake();
    Event::fake([TransactionCreated::class]);

    // Configure Hash for testing environment
    Hash::setRounds(4);
});

describe('Transaction Creation', function () {
    test('authenticated user can create a transaction with sufficient balance', function () {
        $sender = User::factory()->create(['balance' => 1000.00]);
        $receiver = User::factory()->create(['balance' => 500.00]);

        $response = $this->actingAs($sender)
            ->postJson('/api/transactions', [
                'receiver_id' => $receiver->id,
                'amount' => 100.00,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'sender',
                    'receiver',
                    'amount',
                    'created_at',
                ],
            ]);

        // Verify transaction was created
        $this->assertDatabaseHas('transactions', [
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'amount' => 100.00,
        ]);

        // Verify balances were updated (100 + 1.5% commission = 101.50 deducted)
        $sender->refresh();
        $receiver->refresh();

        expect($sender->balance)->toBe('898.5000');  // 1000 - 100 - 1.50
        expect($receiver->balance)->toBe('600.0000'); // 500 + 100

        // Verify event was dispatched
        Event::assertDispatched(TransactionCreated::class);
    });

    test('transaction includes correct commission fee', function () {
        $sender = User::factory()->create(['balance' => 1000.00]);
        $receiver = User::factory()->create(['balance' => 0.00]);

        $this->actingAs($sender)
            ->postJson('/api/transactions', [
                'receiver_id' => $receiver->id,
                'amount' => 200.00,
            ]);

        $transaction = Transaction::latest()->first();

        // 1.5% of 200.00 = 3.00
        expect($transaction->commission_fee)->toBe('3.0000');
        expect($transaction->amount)->toBe('200.0000');

        // Verify sender paid amount + commission
        $sender->refresh();
        expect($sender->balance)->toBe('797.0000'); // 1000 - 200 - 3
    });

    test('multiple transactions update balances correctly', function () {
        $sender = User::factory()->create(['balance' => 1000.00]);
        $receiver = User::factory()->create(['balance' => 0.00]);

        // First transaction: 100 + 1.50 commission
        $this->actingAs($sender)
            ->postJson('/api/transactions', [
                'receiver_id' => $receiver->id,
                'amount' => 100.00,
            ]);

        // Second transaction: 50 + 0.75 commission
        $this->actingAs($sender)
            ->postJson('/api/transactions', [
                'receiver_id' => $receiver->id,
                'amount' => 50.00,
            ]);

        $sender->refresh();
        $receiver->refresh();

        // 1000 - (100 + 1.50) - (50 + 0.75) = 847.75
        expect($sender->balance)->toBe('847.7500');
        // 0 + 100 + 50 = 150
        expect($receiver->balance)->toBe('150.0000');
    });
});

describe('Validation Rules', function () {
    test('transaction requires authentication', function () {
        $receiver = User::factory()->create();

        $response = $this->postJson('/api/transactions', [
            'receiver_id' => $receiver->id,
            'amount' => 100.00,
        ]);

        $response->assertStatus(401);
    });

    test('transaction requires receiver_id', function () {
        $sender = User::factory()->create(['balance' => 1000.00]);

        $response = $this->actingAs($sender)
            ->postJson('/api/transactions', [
                'amount' => 100.00,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['receiver_id']);
    });

    test('transaction requires amount', function () {
        $sender = User::factory()->create(['balance' => 1000.00]);
        $receiver = User::factory()->create();

        $response = $this->actingAs($sender)
            ->postJson('/api/transactions', [
                'receiver_id' => $receiver->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    });

    test('amount must be greater than zero', function () {
        $sender = User::factory()->create(['balance' => 1000.00]);
        $receiver = User::factory()->create();

        $response = $this->actingAs($sender)
            ->postJson('/api/transactions', [
                'receiver_id' => $receiver->id,
                'amount' => 0,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    });

    test('amount must be numeric', function () {
        $sender = User::factory()->create(['balance' => 1000.00]);
        $receiver = User::factory()->create();

        $response = $this->actingAs($sender)
            ->postJson('/api/transactions', [
                'receiver_id' => $receiver->id,
                'amount' => 'invalid',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    });

    test('receiver_id must exist in database', function () {
        $sender = User::factory()->create(['balance' => 1000.00]);

        $response = $this->actingAs($sender)
            ->postJson('/api/transactions', [
                'receiver_id' => 99999,
                'amount' => 100.00,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['receiver_id']);
    });

    test('cannot send transaction to yourself', function () {
        $sender = User::factory()->create(['balance' => 1000.00]);

        $response = $this->actingAs($sender)
            ->postJson('/api/transactions', [
                'receiver_id' => $sender->id,
                'amount' => 100.00,
            ]);

        // The service layer throws TransactionFailedException with 500 status
        // because the validation happens after locking users
        $response->assertStatus(500);

        // Verify no transaction was created
        $this->assertDatabaseCount('transactions', 0);
    });
});

describe('Insufficient Balance', function () {
    test('transaction fails when sender has insufficient balance for amount', function () {
        $sender = User::factory()->create(['balance' => 50.00]);
        $receiver = User::factory()->create();

        $response = $this->actingAs($sender)
            ->postJson('/api/transactions', [
                'receiver_id' => $receiver->id,
                'amount' => 100.00,
            ]);

        $response->assertStatus(422);

        // Verify no transaction was created
        $this->assertDatabaseCount('transactions', 0);

        // Verify balances unchanged
        $sender->refresh();
        expect($sender->balance)->toBe('50.0000');
    });

    test('transaction fails when sender has insufficient balance including commission', function () {
        // Sender has exactly 100, but needs 100 + 1.50 commission = 101.50
        $sender = User::factory()->create(['balance' => 100.00]);
        $receiver = User::factory()->create();

        $response = $this->actingAs($sender)
            ->postJson('/api/transactions', [
                'receiver_id' => $receiver->id,
                'amount' => 100.00,
            ]);

        // InsufficientBalanceException returns 422 status
        $response->assertStatus(422);

        $this->assertDatabaseCount('transactions', 0);
    });

    test('transaction succeeds when sender has exact amount including commission', function () {
        // 100 + 1.50 commission = 101.50
        $sender = User::factory()->create(['balance' => 101.50]);
        $receiver = User::factory()->create(['balance' => 0.00]);

        $response = $this->actingAs($sender)
            ->postJson('/api/transactions', [
                'receiver_id' => $receiver->id,
                'amount' => 100.00,
            ]);

        $response->assertStatus(201);

        $sender->refresh();
        expect($sender->balance)->toBe('0.0000');
    });
});

describe('Transaction History', function () {
    test('user can view their transaction history', function () {
        $user = User::factory()->create(['balance' => 1000.00]);
        $otherUser1 = User::factory()->create(['balance' => 1000.00]);
        $otherUser2 = User::factory()->create(['balance' => 1000.00]);

        // Create transactions where user is sender
        $this->actingAs($user)->postJson('/api/transactions', [
            'receiver_id' => $otherUser1->id,
            'amount' => 50.00,
        ]);

        // Create transaction where user is receiver
        $this->actingAs($otherUser2)->postJson('/api/transactions', [
            'receiver_id' => $user->id,
            'amount' => 75.00,
        ]);

        // Fetch user's transaction history
        $response = $this->actingAs($user)
            ->getJson('/api/transactions');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    });

    test('transaction history includes sender and receiver details', function () {
        $sender = User::factory()->create(['balance' => 1000.00]);
        $receiver = User::factory()->create(['balance' => 0.00]);

        $this->actingAs($sender)->postJson('/api/transactions', [
            'receiver_id' => $receiver->id,
            'amount' => 100.00,
        ]);

        $response = $this->actingAs($sender)
            ->getJson('/api/transactions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'sender' => ['id', 'name', 'email'],
                        'receiver' => ['id', 'name', 'email'],
                        'amount',
                        'created_at',
                    ],
                ],
            ]);
    });

    test('users can only see their own transactions', function () {
        $user1 = User::factory()->create(['balance' => 1000.00]);
        $user2 = User::factory()->create(['balance' => 1000.00]);
        $user3 = User::factory()->create(['balance' => 1000.00]);

        // Transaction between user2 and user3 (user1 not involved)
        $this->actingAs($user2)->postJson('/api/transactions', [
            'receiver_id' => $user3->id,
            'amount' => 50.00,
        ]);

        // User1 should not see this transaction
        $response = $this->actingAs($user1)
            ->getJson('/api/transactions');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    });
});

describe('Data Integrity', function () {
    test('transaction is atomic and rolls back on failure', function () {
        $sender = User::factory()->create(['balance' => 1000.00]);
        $initialBalance = $sender->balance;

        // Attempt transaction with non-existent receiver (will fail validation)
        $this->actingAs($sender)
            ->postJson('/api/transactions', [
                'receiver_id' => 99999,
                'amount' => 100.00,
            ]);

        // Verify balance was not changed
        $sender->refresh();
        expect($sender->balance)->toBe($initialBalance);

        // Verify no transaction was created
        $this->assertDatabaseCount('transactions', 0);
    });

    test('transaction uses soft deletes', function () {
        $sender = User::factory()->create(['balance' => 1000.00]);
        $receiver = User::factory()->create();

        $this->actingAs($sender)->postJson('/api/transactions', [
            'receiver_id' => $receiver->id,
            'amount' => 100.00,
        ]);

        $transaction = Transaction::latest()->first();

        // Soft delete the transaction
        $transaction->delete();

        // Transaction should not appear in normal queries
        expect(Transaction::count())->toBe(0);

        // But should exist with trashed
        expect(Transaction::withTrashed()->count())->toBe(1);
    });

    test('decimal precision is maintained', function () {
        $sender = User::factory()->create(['balance' => 1000.1234]);
        $receiver = User::factory()->create(['balance' => 0.00]);

        $this->actingAs($sender)->postJson('/api/transactions', [
            'receiver_id' => $receiver->id,
            'amount' => 123.4567,
        ]);

        $transaction = Transaction::latest()->first();

        // Commission: 123.4567 * 0.015 = 1.8519 (rounded to 4 decimals = 1.8519)
        expect($transaction->amount)->toBe('123.4567');
        expect($transaction->commission_fee)->toBe('1.8519');

        // Verify balance calculations
        $sender->refresh();
        // 1000.1234 - 123.4567 - 1.8519 = 874.8148
        expect($sender->balance)->toBe('874.8148');
    });
});

describe('Edge Cases', function () {
    test('handles very small transaction amounts', function () {
        $sender = User::factory()->create(['balance' => 10.00]);
        $receiver = User::factory()->create(['balance' => 0.00]);

        $response = $this->actingAs($sender)
            ->postJson('/api/transactions', [
                'receiver_id' => $receiver->id,
                'amount' => 0.01,
            ]);

        $response->assertStatus(201);

        $transaction = Transaction::latest()->first();
        expect($transaction->amount)->toBe('0.0100');
        // Commission: 0.01 * 0.015 = 0.00015 (rounds to 0.0002)
        expect($transaction->commission_fee)->toBe('0.0002');
    });

    test('handles transactions with deleted users gracefully', function () {
        $sender = User::factory()->create(['balance' => 1000.00]);
        $receiver = User::factory()->create(['balance' => 0.00]);

        $this->actingAs($sender)->postJson('/api/transactions', [
            'receiver_id' => $receiver->id,
            'amount' => 100.00,
        ]);

        // Soft delete receiver
        $receiver->delete();

        // Fetch transaction history - should still work
        $response = $this->actingAs($sender)
            ->getJson('/api/transactions');

        $response->assertStatus(200);
    });

    test('handles concurrent transaction attempts', function () {
        $sender = User::factory()->create(['balance' => 100.00]);
        $receiver = User::factory()->create(['balance' => 0.00]);

        // Simulate concurrent transactions
        $results = [];
        for ($i = 0; $i < 3; $i++) {
            $results[] = $this->actingAs($sender)
                ->postJson('/api/transactions', [
                    'receiver_id' => $receiver->id,
                    'amount' => 50.00, // 50 + 0.75 commission = 50.75 each
                ]);
        }

        // Due to pessimistic locking, only transactions that fit should succeed
        // First transaction: 100 - 50.75 = 49.25 remaining
        // Second transaction: Should fail (49.25 < 50.75 needed)
        // Note: In test environment with sync queue, transactions run sequentially

        $successCount = collect($results)->filter(fn ($r) => $r->status() === 201)->count();
        $failCount = collect($results)->filter(fn ($r) => $r->status() === 422)->count();

        // Exactly 1 should succeed
        expect($successCount)->toBe(1);

        // Exactly 2 should fail due to insufficient balance (422)
        expect($failCount)->toBe(2);

        // Verify final balances
        $sender->refresh();
        $receiver->refresh();
        expect($sender->balance)->toBe('49.2500'); // 100 - 50 - 0.75
        expect($receiver->balance)->toBe('50.0000');
    });
});
