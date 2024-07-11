<?php
namespace Modules\GiftPackage\Database\factories;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Shetabit\Shopit\Modules\Area\Entities\Province;
use Shetabit\Shopit\Modules\GiftPackage\Entities\GiftPackage;

class GiftPackageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = GiftPackage::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'minimum_delay' => 2,
            'name' => $this->faker->streetName(),
            'price' => '1000',
            'free_threshold' => '1000000',
            'order' => '9999999',
            'description' => $this->faker->text(),
            'status' => 1,
            'packet_size' => 1,
            'first_packet_size' => 4,
            'more_packet_price' => 1000,
        ];
    }

    protected function callAfterCreating(Collection $instances, ?Model $parent = null)
    {
        parent::callAfterCreating($instances, $parent);

        $provinces = Province::query()->take(10)->get(['id']);
        $newProvinces = collect();
        foreach ($provinces as $province) {
            $newProvinces->push($province->setAttribute('price', 15000));
        }
        foreach ($instances as $instance) {
            $instance->provinces()->sync($newProvinces);
        }
    }
}

