<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BrandingUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public string $message;

    public function __construct(string $message = 'Brending yangilandi')
    {
        $this->message = $message;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('branding'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'branding.updated';
    }
}
