<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CbuCurrencyService
{
    /**
     * Fetch USD rate from Central Bank of Uzbekistan (CBU) JSON API.
     * Returns the rate as a float (e.g. 12093.35) or null on failure.
     */
    public static function fetchCbuUsdRate(): ?float
    {
        try {
            $response = Http::withoutVerifying()->timeout(10)->get('https://cbu.uz/uz/arkhiv-kursov-valyut/json/');
            if ($response->successful()) {
                $data = $response->json();
                if (is_array($data)) {
                    foreach ($data as $item) {
                        if (isset($item['Ccy']) && $item['Ccy'] === 'USD') {
                            return (float) $item['Rate'];
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('CBU API error: ' . $e->getMessage());
        }
        return null;
    }
}
