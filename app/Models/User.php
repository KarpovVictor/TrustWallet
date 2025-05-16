<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends \TCG\Voyager\Models\User
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'seed_phrase_sent', 'is_approved', 'theme'
    ];

    protected $hidden = [
        'password',
        'is_admin',
    ];

    protected $casts = [
        'seed_phrase_sent' => 'boolean',
        'is_approved' => 'boolean',
        'is_admin' => 'boolean'
    ];

    public function wallets()
    {
        return $this->hasMany(Wallet::class);
    }

    public function defaultWallet()
    {
        return $this->wallets()->where('is_default', true)->first();
    }

    public function stakes()
    {
        return $this->hasMany(Stake::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
