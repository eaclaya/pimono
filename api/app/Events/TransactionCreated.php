<?php

namespace App\Events;

use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Transaction $transaction)
    {
        //
    }

    public function broadcastAs(): string
    {
        return 'TransactionCreated';
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('users.'.$this->transaction->receiver_id),
            new PrivateChannel('users.'.$this->transaction->sender_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'transaction' => new TransactionResource($this->transaction),
        ];
    }
}
