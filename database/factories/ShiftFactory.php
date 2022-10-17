<?php

namespace Database\Factories;

use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShiftFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Shift::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'rate' => $this->faker->randomDigit(),
            'is_start' => true,
            'is_end' => true,
            'created_at' => $this->faker->dateTimeBetween('-1 week', '+1 week'),
            'updated_at' => $this->faker->dateTimeBetween('-1 week', '+1 week'),
        ];
    }
}
