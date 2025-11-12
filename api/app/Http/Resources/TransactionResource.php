<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sender' => UserResource::make($this->sender),
            'receiver' => UserResource::make($this->receiver),
            'amount' => $this->amount,
            'commission_fee' => $this->commission_fee,
            'created_at' => $this->created_at,
        ];
    }
}
