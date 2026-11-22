<?php

namespace Database\Seeders;

use App\Models\Asset;
use Illuminate\Database\Seeder;

class AssetSeeder extends Seeder
{
    public function run(): void
    {
        $assets = [
            [
                'vendor' => 'microsoft',
                'product' => 'windows_10',
                'version' => '10.0.19045',
                'status' => 'Active',
            ],
            [
                'vendor' => 'apache',
                'product' => 'http_server',
                'version' => '2.4.59',
                'status' => 'Active',
            ],
            [
                'vendor' => 'php',
                'product' => 'php',
                'version' => '8.3.6',
                'status' => 'Active',
            ],
            [
                'vendor' => 'oracle',
                'product' => 'mysql',
                'version' => '8.0.36',
                'status' => 'Active',
            ],
            [
                'vendor' => 'nginx',
                'product' => 'nginx',
                'version' => '1.26.0',
                'status' => 'Retired',
            ],
        ];

        foreach ($assets as $asset) {
            Asset::create($asset);
        }
    }
}
