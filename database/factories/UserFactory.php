<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\User;
use Faker\Generator as Faker;
use Illuminate\Support\Facades\Hash;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(User::class, function (Faker $faker) {
    $password = Hash::make('secret');

    return [
        'legacy_usuario_id' => $faker->unique()->numberBetween(1, 999999),
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'password' => $password,
        'nome_usu' => $faker->name,
        'login_usu' => $faker->unique()->userName,
        'senha_usu' => $password,
        'email_usu' => $faker->unique()->safeEmail,
        'nivel_usu' => 'USU',
        'acesso_usu' => now(),
        'data_cad' => now(),
        'id_setor' => null,
        'id_cliente' => '0',
        'status_usu' => 'ATI',
        'estados_usu' => null,
        'comarca_usu' => null,
    ];
});
