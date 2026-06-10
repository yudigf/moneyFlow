<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'wallet_id',
        'category_id',
        'amount',
        'type',
        'description',
        'transaction_date',
        'destination_wallet_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount'           => 'decimal:2',
        'transaction_date' => 'datetime',
    ];

    /**
     * Get the source wallet for this transaction.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'wallet_id');
    }

    /**
     * Get the destination wallet (only applicable for transfers).
     */
    public function destinationWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'destination_wallet_id');
    }

    /**
     * Get the category for this transaction.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Boot the model and register event listeners for balance calculations.
     */
    protected static function booted()
    {
        static::created(function ($transaction) {
            $transaction->applyBalance();
        });

        static::deleted(function ($transaction) {
            $transaction->revertBalance();
        });

        static::updating(function ($transaction) {
            // Revert the old values before the update happens
            $original = new self();
            $original->forceFill($transaction->getOriginal());
            $original->revertBalance();
        });

        static::updated(function ($transaction) {
            // Apply the new values
            $transaction->applyBalance();
        });
    }

    /**
     * Apply the transaction amount to the wallets.
     */
    public function applyBalance()
    {
        if ($this->type === 'expense') {
            Wallet::where('id', $this->wallet_id)->decrement('balance', $this->amount);
        } elseif ($this->type === 'income') {
            Wallet::where('id', $this->wallet_id)->increment('balance', $this->amount);
        } elseif ($this->type === 'transfer') {
            Wallet::where('id', $this->wallet_id)->decrement('balance', $this->amount);
            if ($this->destination_wallet_id) {
                Wallet::where('id', $this->destination_wallet_id)->increment('balance', $this->amount);
            }
        }
    }

    /**
     * Revert the transaction amount from the wallets.
     */
    public function revertBalance()
    {
        if ($this->type === 'expense') {
            Wallet::where('id', $this->wallet_id)->increment('balance', $this->amount);
        } elseif ($this->type === 'income') {
            Wallet::where('id', $this->wallet_id)->decrement('balance', $this->amount);
        } elseif ($this->type === 'transfer') {
            Wallet::where('id', $this->wallet_id)->increment('balance', $this->amount);
            if ($this->destination_wallet_id) {
                Wallet::where('id', $this->destination_wallet_id)->decrement('balance', $this->amount);
            }
        }
    }
}
