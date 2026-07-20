<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    /**
     * Write an audit log entry.
     */
    public static function log(string $action, Model $model, ?array $oldValues = null, ?array $newValues = null): void
    {
        $userId = auth()->id();
        $ipAddress = Request::ip();
        $userAgent = Request::header('User-Agent');

        $asSubManager = false;
        if (auth()->check() && isset($model->object_id)) {
            $asSubManager = \App\Models\ObjectSubManager::isCurrentUserSubManager((int)$model->object_id);
        }

        AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'auditable_type' => get_class($model),
            'auditable_id' => $model->getKey() ?: 0,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'as_sub_manager' => $asSubManager,
        ]);
    }
}
