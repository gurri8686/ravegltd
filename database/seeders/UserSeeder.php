<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $dt = Carbon::now();
        $dateNow = $dt->toDateTimeString();
        DB::table('users')->insert([
            'first_name' => Str::random(10),
            'last_name' => Str::random(10),
            'email' => 'super@yopmail.com',//Str::random(10).'@gmail.com',
            'image' => Str::random(10),
            'country' => Str::random(10),
            'state' => Str::random(10),
            'city' => Str::random(10),
            'zipcode' => Str::random(10),
            'address' => Str::random(10),
            'password' => Hash::make('12345678'),
            'is_active' => 1,
            'created_at' => $dateNow,
            'updated_at' => $dateNow
        ]);
    }
}
