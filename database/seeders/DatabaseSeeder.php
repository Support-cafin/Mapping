<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            EntrepriseSeeder::class,
            UserSeeder::class,
            OldAccountSeeder::class,
            NewAccountSeeder::class,
            MappingSeeder::class,
            JournalSeeder::class,
            GrandLivreSeeder::class,
        ]);
    }
}
