<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LowStockWarning implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public array $warning;

    public function __construct(array $warning)
    {
        $this->warning = $warning;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('warehouse'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'stock.low';
    }
}
