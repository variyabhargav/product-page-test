<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CreateUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'name' => 'Token User',
            'email' => 'test@gmail.com',
            'email_verified_at' => now(),
            'password' => bcrypt('demo@123'), // password
            'remember_token' => \Str::random(10),
        ]);
    }
}
