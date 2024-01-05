<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            ['key' => 'app_name', 'value' => 'Laravel-POS'],
            ['key' => 'currency_symbol', 'value' => '$'],
            ['key' => 'address', 'value' => ''],
            ['key' => 'email', 'value' => ''],
            ['key' => 'phone', 'value' => '011111111'],
        ];

        foreach ($data as $value) {
            Setting::updateOrCreate([
                'key' => $value['key']
            ], [
                'value' => $value['value']
            ]);
        }
    }
}
