<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if(!User::find(1)){
            User::create([
                'id' => 1,
                'name' => 'Marwan',
                'email' => 'marwan@nesr.com',
                'phone' => '0920000000',
                'password' => '$2y$10$X3ER.qrjr417NG0TPUg9I.SOmMFSXSn4PunA6OT/F0.HDxHrSs6MC',
                'is_active' => 1,
            ]);
        }

    }
}
