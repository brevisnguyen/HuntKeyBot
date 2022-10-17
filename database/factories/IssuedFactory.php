<?php

namespace Database\Factories;

use App\Models\Issued;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class IssuedFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Issued::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'amount' => $this->faker->randomFloat(2, 990, 6900),
            'created_at' => $this->faker->dateTimeBetween('-1 week', '+1 week'),
            'updated_at' => $this->faker->dateTimeBetween('-1 week', '+1 week'),
        ];
    }
}
