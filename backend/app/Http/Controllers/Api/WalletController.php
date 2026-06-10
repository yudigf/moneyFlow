<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    /**
     * Display a listing of wallets.
     */
    public function index(): JsonResponse
    {
        $wallets = Wallet::all();

        return response()->json([
            'success' => true,
            'data'    => $wallets,
        ]);
    }

    /**
     * Store a newly created wallet.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'type'    => 'required|string|in:bank,e-wallet,cash',
            'balance' => 'nullable|numeric|min:0',
        ]);

        $wallet = Wallet::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Wallet created successfully.',
            'data'    => $wallet,
        ], 201);
    }

    /**
     * Display the specified wallet.
     */
    public function show(Wallet $wallet): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $wallet,
        ]);
    }

    /**
     * Update the specified wallet.
     */
    public function update(Request $request, Wallet $wallet): JsonResponse
    {
        $validated = $request->validate([
            'name'    => 'sometimes|required|string|max:255',
            'type'    => 'sometimes|required|string|in:bank,e-wallet,cash',
            'balance' => 'nullable|numeric|min:0',
        ]);

        $wallet->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Wallet updated successfully.',
            'data'    => $wallet,
        ]);
    }

    /**
     * Remove the specified wallet.
     */
    public function destroy(Wallet $wallet): JsonResponse
    {
        $wallet->delete();

        return response()->json([
            'success' => true,
            'message' => 'Wallet deleted successfully.',
        ]);
    }
}
