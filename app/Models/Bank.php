<?php

namespace App\Models;

use Database\Factories\BankFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bank extends Model
{
    /** @use HasFactory<BankFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'api_key_hash',
        'webhook_secret',
    ];

    protected function casts(): array
    {
        return [
            'webhook_secret' => 'encrypted',
        ];
    }

    /** @return HasMany<Transaction, $this> */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
