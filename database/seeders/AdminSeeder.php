<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;


class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = Admin::factory()->create([
            'name' => 'Fırat Koçoğlu',
            'email' => 'fratkocogl@gmail.com',
            'password' => '12121994fx'
        ]);
        
        $admin->assignRole('admin');
    }
}
