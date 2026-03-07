<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Entreprise;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $entreprises = Entreprise::all();

        foreach ($entreprises as $entreprise) {

            // Admin
            User::create([
                'name' => $entreprise->nom . ' Admin',
                'email' => strtolower($entreprise->code) . '@admin.com',
                'password' => Hash::make('password'),
                'entreprise_id' => $entreprise->id
            ]);

            // Comptable
            User::create([
                'name' => $entreprise->nom . ' Comptable',
                'email' => strtolower($entreprise->code) . '@compte.com',
                'password' => Hash::make('password'),
                'entreprise_id' => $entreprise->id
            ]);
        }
    }
}
