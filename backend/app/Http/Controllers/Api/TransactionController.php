<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    /**
     * Display a listing of transactions.
     */
    public function index(): JsonResponse
    {
        $transactions = Transaction::with(['wallet', 'category', 'destinationWallet'])
            ->orderByDesc('transaction_date')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $transactions,
        ]);
    }

    /**
     * Store a newly created transaction.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'wallet_id'              => 'required|exists:wallets,id',
            'category_id'            => 'nullable|exists:categories,id',
            'amount'                 => 'required|numeric|min:0.01',
            'type'                   => 'required|string|in:income,expense,transfer',
            'description'            => 'nullable|string',
            'transaction_date'       => 'required|date',
            'destination_wallet_id'  => 'nullable|exists:wallets,id|required_if:type,transfer|different:wallet_id',
        ]);

        $transaction = Transaction::create($validated);
        $transaction->load(['wallet', 'category', 'destinationWallet']);

        return response()->json([
            'success' => true,
            'message' => 'Transaction created successfully.',
            'data'    => $transaction,
        ], 201);
    }

    /**
     * Display the specified transaction.
     */
    public function show(Transaction $transaction): JsonResponse
    {
        $transaction->load(['wallet', 'category', 'destinationWallet']);

        return response()->json([
            'success' => true,
            'data'    => $transaction,
        ]);
    }

    /**
     * Update the specified transaction.
     */
    public function update(Request $request, Transaction $transaction): JsonResponse
    {
        $validated = $request->validate([
            'wallet_id'              => 'sometimes|required|exists:wallets,id',
            'category_id'            => 'nullable|exists:categories,id',
            'amount'                 => 'sometimes|required|numeric|min:0.01',
            'type'                   => 'sometimes|required|string|in:income,expense,transfer',
            'description'            => 'nullable|string',
            'transaction_date'       => 'sometimes|required|date',
            'destination_wallet_id'  => 'nullable|exists:wallets,id|required_if:type,transfer|different:wallet_id',
        ]);

        $transaction->update($validated);
        $transaction->load(['wallet', 'category', 'destinationWallet']);

        return response()->json([
            'success' => true,
            'message' => 'Transaction updated successfully.',
            'data'    => $transaction,
        ]);
    }

    /**
     * Remove the specified transaction.
     */
    public function destroy(Transaction $transaction): JsonResponse
    {
        $transaction->delete();

        return response()->json([
            'success' => true,
            'message' => 'Transaction deleted successfully.',
        ]);
    }
}
