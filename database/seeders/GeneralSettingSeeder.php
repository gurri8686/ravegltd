<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GeneralSetting;

class GeneralSettingSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'setting' => 'registration_allowed',
                'user_id' => 1,
                'status' => 1,
                'is_archive' => 0,
            ],
            [
                'setting' => 'notifications_enabled',
                'user_id' => 1,
                'status' => 1,
                'is_archive' => 0,
            ],
        ];

        foreach ($data as $item) {
            GeneralSetting::firstOrCreate(
                ['setting' => $item['setting']], // unique field condition
                $item // data to insert if not found
            );
        }
    }
}