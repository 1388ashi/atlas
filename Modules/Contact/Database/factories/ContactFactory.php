<?php

/** @var Factory $factory */

use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;
use Modules\Article\Entities\Article;
use Modules\Contact\Entities\Contact;

$factory->define(Contact::class, function (Faker $faker) {
    return [
        'name' => $faker->name(),
        'email' => $faker->email,
        'subject' => $faker->realText('30'),
        'body' => $faker->realText('300')
    ];
});
