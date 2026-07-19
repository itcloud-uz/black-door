<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CbuCurrencyService;
use App\Models\CurrencyRate;
use App\Services\AuditLogger;
use App\Models\User;

class SyncCbuCurrencyRate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'currency:sync-cbu';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Sync USD currency rate from Central Bank of Uzbekistan';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Markaziy Bankdan USD kursini olish boshlandi...');
        $cbuRate = CbuCurrencyService::fetchCbuUsdRate();

        if ($cbuRate !== null) {
            $rateTiyin = (int) round($cbuRate * 100);
            $today = now()->toDateString();

            // Find first admin or system user
            $systemUser = User::where('role', 'super_admin')->first();
            $userId = $systemUser ? $systemUser->id : 1;

            $rate = CurrencyRate::whereDate('effective_date', $today)->first();
            if ($rate) {
                $rate->update([
                    'rate_uzs_per_usd' => $rateTiyin,
                    'set_by' => $userId,
                    'note' => 'Markaziy Bank API orqali avtomatik yangilandi.',
                ]);
            } else {
                $rate = CurrencyRate::create([
                    'effective_date' => $today,
                    'rate_uzs_per_usd' => $rateTiyin,
                    'set_by' => $userId,
                    'note' => 'Markaziy Bank API orqali avtomatik yangilandi.',
                ]);
            }

            $this->info("Kurs muvaffaqiyatli sinxronizatsiya qilindi: 1 USD = {$cbuRate} UZS");
            AuditLogger::log('sync_currency_rate_cbu', $rate, null, $rate->toArray());
            return Command::SUCCESS;
        }

        $this->error('Markaziy Bankdan USD kursini olib bo\'lmadi.');
        return Command::FAILURE;
    }
}
