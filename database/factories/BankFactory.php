<?php

namespace Database\Factories;

use App\Models\Bank;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Bank> */
class BankFactory extends Factory
{
    protected $model = Bank::class;

    public function definition(): array
    {
        $apiKey = $this->faker->uuid();

        return [
            'name' => $this->faker->unique()->word(),
            'api_key_hash' => hash('sha256', $apiKey),
            'webhook_secret' => $this->faker->sha256(),
        ];
    }
}
