<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Get a summary of transactions for a specific month and year.
     */
    public function summary(Request $request): JsonResponse
    {
        $month = $request->query('month', Carbon::now()->month);
        $year = $request->query('year', Carbon::now()->year);

        // Fetch transactions for the given month and year
        $transactions = Transaction::with('category')
            ->whereMonth('transaction_date', $month)
            ->whereYear('transaction_date', $year)
            ->get();

        // Calculate Totals
        $totalIncome = $transactions->where('type', 'income')->sum('amount');
        $totalExpense = $transactions->where('type', 'expense')->sum('amount');

        // Calculate Expense by Category (Doughnut Chart)
        $expenseByCategory = $transactions->where('type', 'expense')
            ->groupBy(function ($tx) {
                return $tx->category ? $tx->category->name : 'Uncategorized';
            })
            ->map(function ($group) {
                return $group->sum('amount');
            })
            ->sortDesc() // Sort by highest expense
            ->toArray();

        // Calculate Daily Transactions (Bar Chart)
        // Group by day format 'Y-m-d'
        $dailyGroup = $transactions->groupBy(function ($tx) {
            return Carbon::parse($tx->transaction_date)->format('Y-m-d');
        });

        $dailyTransactions = [];
        // We want to fill missing days up to today or end of month, but returning just the days with data is fine for a simple chart.
        // Actually, to make the chart look complete, it's better to just return the days that have data.
        foreach ($dailyGroup as $date => $txs) {
            $dailyTransactions[$date] = [
                'income' => $txs->where('type', 'income')->sum('amount'),
                'expense' => $txs->where('type', 'expense')->sum('amount'),
            ];
        }

        // Sort by date ascending
        ksort($dailyTransactions);

        return response()->json([
            'success' => true,
            'data' => [
                'period' => ['month' => (int)$month, 'year' => (int)$year],
                'totals' => [
                    'income' => $totalIncome,
                    'expense' => $totalExpense,
                    'net' => $totalIncome - $totalExpense,
                ],
                'expense_by_category' => $expenseByCategory,
                'daily_transactions' => $dailyTransactions,
            ],
        ]);
    }
}
