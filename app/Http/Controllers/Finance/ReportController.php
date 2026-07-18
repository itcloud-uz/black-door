<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsClient;
use App\Models\CashAccount;
use App\Models\TransactionCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $type = $request->input('type', 'income_expense');
        $cashAccountId = $request->filled('cash_account_id') ? (int)$request->cash_account_id : null;
        $categoryId = $request->filled('category_id') ? (int)$request->category_id : null;

        $client = new AnalyticsClient();
        $reportData = [];

        try {
            if ($type === 'income_expense') {
                $reportData = $client->getIncomeExpenseReport($startDate, $endDate, $cashAccountId, $categoryId);
            } elseif ($type === 'cash_balances') {
                $reportData = $client->getCashBalancesReport($startDate, $endDate);
            } elseif ($type === 'debt_registry') {
                $reportData = $client->getDebtRegistryReport($endDate);
            } elseif ($type === 'category_breakdown') {
                $reportData = $client->getCategoryBreakdownReport($startDate, $endDate, $request->input('category_type', 'expense'));
            }
        } catch (\Exception $e) {
            Log::error("Failed to generate report in controller: " . $e->getMessage());
            $reportData = ['error' => true, 'message' => $e->getMessage()];
        }

        $cashAccounts = CashAccount::where('is_active', true)->orderBy('name')->get();
        $categories = TransactionCategory::orderBy('name')->get();

        return view('finance.reports.index', compact(
            'reportData',
            'startDate',
            'endDate',
            'type',
            'cashAccountId',
            'categoryId',
            'cashAccounts',
            'categories'
        ));
    }

    public function export(Request $request, string $format)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $type = $request->input('type', 'income_expense');
        $cashAccountId = $request->filled('cash_account_id') ? (int)$request->cash_account_id : null;
        $categoryId = $request->filled('category_id') ? (int)$request->category_id : null;

        $client = new AnalyticsClient();
        $reportData = [];

        try {
            if ($type === 'income_expense') {
                $reportData = $client->getIncomeExpenseReport($startDate, $endDate, $cashAccountId, $categoryId);
            } elseif ($type === 'cash_balances') {
                $reportData = $client->getCashBalancesReport($startDate, $endDate);
            } elseif ($type === 'debt_registry') {
                $reportData = $client->getDebtRegistryReport($endDate);
            } elseif ($type === 'category_breakdown') {
                $reportData = $client->getCategoryBreakdownReport($startDate, $endDate, $request->input('category_type', 'expense'));
            }

            if (isset($reportData['error']) && $reportData['error']) {
                return back()->withErrors(['error' => $reportData['message']]);
            }

            // Call microservice export endpoint
            $exportResult = $client->exportReport($format, $type, $reportData);

            if (isset($exportResult['error']) && $exportResult['error']) {
                return back()->withErrors(['error' => $exportResult['message']]);
            }

            $filename = $exportResult['filename'];
            
            // Download the file from FastAPI and stream it back to the client
            $reportServiceUrl = env('REPORT_SERVICE_URL', 'http://localhost:8001');
            $fileUrl = "{$reportServiceUrl}/reports/download/{$filename}";
            
            $response = Http::get($fileUrl);
            if ($response->successful()) {
                $contentType = $response->header('Content-Type') ?: 'application/octet-stream';
                return response($response->body(), 200, [
                    'Content-Type' => $contentType,
                    'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                ]);
            }

            return back()->withErrors(['error' => 'Hisobot faylini yuklab olishda xatolik yuz berdi.']);

        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
