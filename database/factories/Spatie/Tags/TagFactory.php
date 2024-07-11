<?php

namespace Database\Factories\Spatie\Tags;

use App\Models\Model;
use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Tags\Tag;

class TagFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Tag::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name'=> $this->faker->title(),
            'slug'=> $this->faker->slug(),
            'type'=> $this->faker->countryCode,
            'order_column'=> 9999,
        ];
    }
}
