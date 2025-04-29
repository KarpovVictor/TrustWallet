<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'crypto_id',
        'transaction_type',
        'amount',
        'tx_hash',
        'address_from',
        'address_to',
        'status',
        'notes',
        'details'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'amount' => 'decimal:8',
    ];

    /**
     * Get the user associated with the transaction.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the crypto associated with the transaction.
     */
    public function crypto()
    {
        return $this->belongsTo(Crypto::class);
    }

    /**
     * Scope a query to only include transactions of a specific type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('transaction_type', $type);
    }

    /**
     * Scope a query to only include transactions with a specific status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Get a formatted date for the transaction.
     *
     * @return string
     */
    public function getFormattedDateAttribute()
    {
        return $this->created_at->format('d.m.Y H:i');
    }

    /**
     * Get an icon class based on transaction type.
     *
     * @return string
     */
    public function getIconClassAttribute()
    {
        $icons = [
            'deposit' => 'text-green-500',
            'withdrawal' => 'text-red-500',
            'staking' => 'text-blue-500',
            'unstaking' => 'text-yellow-500',
            'exchange_in' => 'text-green-500',
            'exchange_out' => 'text-red-500',
        ];

        return $icons[$this->transaction_type] ?? 'text-gray-500';
    }

    /**
     * Get a human-readable transaction type.
     *
     * @return string
     */
    public function getTypeTextAttribute()
    {
        $types = [
            'deposit' => 'Пополнение',
            'withdrawal' => 'Вывод',
            'staking' => 'Стейкинг',
            'unstaking' => 'Вывод из стейкинга',
            'exchange_in' => 'Обмен',
            'exchange_out' => 'Обмен',
        ];

        return $types[$this->transaction_type] ?? $this->transaction_type;
    }

    /**
     * Get a human-readable status.
     *
     * @return string
     */
    public function getStatusTextAttribute()
    {
        $statuses = [
            'pending' => 'В процессе',
            'completed' => 'Выполнено',
            'failed' => 'Ошибка',
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * Check if transaction is a deposit.
     *
     * @return bool
     */
    public function isDeposit()
    {
        return $this->transaction_type === 'deposit';
    }

    /**
     * Check if transaction is a withdrawal.
     *
     * @return bool
     */
    public function isWithdrawal()
    {
        return $this->transaction_type === 'withdrawal';
    }

    /**
     * Check if transaction is related to staking.
     *
     * @return bool
     */
    public function isStakingRelated()
    {
        return in_array($this->transaction_type, ['staking', 'unstaking']);
    }

    /**
     * Check if transaction is completed.
     *
     * @return bool
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Check if transaction is pending.
     *
     * @return bool
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if transaction has failed.
     *
     * @return bool
     */
    public function hasFailed()
    {
        return $this->status === 'failed';
    }
}