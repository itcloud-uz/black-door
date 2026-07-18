<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PinLocked implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public int $userId;
    public int $lockTimer;

    public function __construct(int $userId, int $lockTimer)
    {
        $this->userId = $userId;
        $this->lockTimer = $lockTimer;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('security'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'pin.locked';
    }
}
