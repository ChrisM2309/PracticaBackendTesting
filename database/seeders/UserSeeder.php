<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bibliotecario = User::firstOrCreate(
            ['email' => 'bibliotecario@correo.com'],
            [
                'name' => fake()->name(),
                'password' => bcrypt('bibliotecario123'),
                'email_verified_at' => now(),
            ]
        );
        $bibliotecario->assignRole('bibliotecario');

        $docente = User::firstOrCreate(
            ['email' => 'docente@correo.com'],
            [
                'name' => fake()->name(),
                'password' => bcrypt('docente123'),
                'email_verified_at' => now(),
            ]
        );
        $docente->assignRole('docente');

        $estudiante = User::firstOrCreate(
            ['email' => 'estudiante@correo.com'],
            [
                'name' => fake()->name(),
                'password' => bcrypt('estudiante123'),
                'email_verified_at' => now(),
            ]
        );
        $estudiante->assignRole('estudiante');
    }
}
