<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnalyticsClient
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = env('REPORT_SERVICE_URL', 'http://localhost:8001');
    }

    /**
     * Get income and expense report.
     */
    public function getIncomeExpenseReport(string $startDate, string $endDate, ?int $cashAccountId = null, ?int $categoryId = null): array
    {
        $payload = [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        if ($cashAccountId !== null) {
            $payload['cash_account_id'] = $cashAccountId;
        }

        if ($categoryId !== null) {
            $payload['category_id'] = $categoryId;
        }

        return $this->post('/reports/income-expense', $payload);
    }

    /**
     * Get cash balances.
     */
    public function getCashBalancesReport(string $startDate, string $endDate): array
    {
        return $this->post('/reports/cash-balances', [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    /**
     * Get debt registry.
     */
    public function getDebtRegistryReport(?string $asOfDate = null): array
    {
        $payload = [];
        if ($asOfDate !== null) {
            $payload['as_of_date'] = $asOfDate;
        }
        return $this->post('/reports/debt-registry', $payload);
    }

    /**
     * Get category breakdown.
     */
    public function getCategoryBreakdownReport(string $startDate, string $endDate, string $type): array
    {
        return $this->post('/reports/category-breakdown', [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'type' => $type,
        ]);
    }

    /**
     * Get object daily report.
     */
    public function getObjectDailyReport(int $objectId, string $date): array
    {
        return $this->post('/reports/object-daily', [
            'object_id' => $objectId,
            'date' => $date,
        ]);
    }

    /**
     * Export a report.
     */
    public function exportReport(string $format, string $reportType, array $data): array
    {
        return $this->post("/reports/export/{$format}", [
            'report_type' => $reportType,
            'data' => $data,
        ]);
    }

    /**
     * Helper to perform POST requests.
     */
    protected function post(string $endpoint, array $payload): array
    {
        try {
            $response = Http::timeout(10)->post($this->baseUrl . $endpoint, $payload);
            if ($response->successful()) {
                return $response->json();
            }
            Log::error("FastAPI report service returned status {$response->status()}: " . $response->body());
            return ['error' => true, 'message' => "Xizmat xatosi: " . $response->status()];
        } catch (\Exception $e) {
            Log::error("Failed to connect to FastAPI report service: " . $e->getMessage());
            return ['error' => true, 'message' => "FastAPI mikroservisiga ulanib bo'lmadi."];
        }
    }
}
