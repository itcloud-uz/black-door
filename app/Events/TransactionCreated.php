<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionCreated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public array $transaction;

    public function __construct(array $transaction)
    {
        $this->transaction = $transaction;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('finance'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'transaction.created';
    }
}
