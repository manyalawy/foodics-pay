<?php

namespace Database\Factories;

use App\Models\Bank;
use App\Models\Client;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Transaction> */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'bank_id' => Bank::factory(),
            'reference' => $this->faker->unique()->numerify('##########'),
            'amount' => $this->faker->randomFloat(2, 10, 100000),
            'date' => $this->faker->date(),
            'metadata' => null,
        ];
    }
}
