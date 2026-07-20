<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BrandingFailedNotification extends Notification
{
    use Queueable;

    public string $errorMessage;

    public function __construct(string $errorMessage)
    {
        $this->errorMessage = $errorMessage;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Brending yangilanishida xatolik yuz berdi: ' . $this->errorMessage,
            'type' => 'branding_error',
        ];
    }
}
