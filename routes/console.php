<?php

use Illuminate\Support\Facades\Schedule;
use App\Models\Obj;
use App\Services\AnalyticsClient;
use Illuminate\Support\Facades\Log;

Schedule::call(function () {
    Log::info('Kunlik obyekt hisobotlarini shakllantirish boshlandi...');
    $objects = Obj::where('is_active', true)->get();
    $analytics = new AnalyticsClient();
    $today = now()->toDateString();
    
    foreach ($objects as $object) {
        try {
            $report = $analytics->getObjectDailyReport((int)$object->id, $today);
            Log::info("Kunlik hisobot shakllantirildi: {$object->name}");
        } catch (\Exception $e) {
            Log::error("Hisobot shakllantirishda xatolik ({$object->name}): " . $e->getMessage());
        }
    }
})->dailyAt('23:00');

Schedule::command('currency:sync-cbu')->dailyAt('09:00');
Schedule::command('db:backup')->dailyAt('02:00');
Schedule::command('submanagers:expire')->dailyAt('00:05');

