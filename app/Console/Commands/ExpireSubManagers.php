<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ObjectSubManager;
use App\Services\AuditLogger;

class ExpireSubManagers extends Command
{
    /**
     * @var string
     */
    protected $signature = 'submanagers:expire';

    /**
     * @var string
     */
    protected $description = 'Process expired temporary sub-manager assignments and log to audit';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $today = today()->toDateString();
        
        $expired = ObjectSubManager::where('end_date', '<', $today)
            ->where('processed', false)
            ->get();
            
        if ($expired->isEmpty()) {
            $this->info('No expired sub-manager assignments found.');
            return Command::SUCCESS;
        }
        
        foreach ($expired as $asm) {
            $asm->processed = true;
            $asm->save();
            
            // Log expiration
            AuditLogger::log('sub_manager_expired', $asm->user, null, [
                'object_id' => $asm->object_id,
                'user_id' => $asm->user_id,
                'start_date' => $asm->start_date->toDateString(),
                'end_date' => $asm->end_date->toDateString(),
            ]);
            
            $this->info("Sub-manager ID {$asm->user_id} expired for Object {$asm->object_id}");
        }
        
        return Command::SUCCESS;
    }
}
