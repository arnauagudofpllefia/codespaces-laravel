<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Usuario::create([
            'nombre' => 'Admin',
            'email' => 'admin@ejemplo.com',
            'contrasena' => Hash::make('admin1234'),
            'rol' => 'admin',
        ]);
    }
}
