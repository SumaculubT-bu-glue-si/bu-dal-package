<?php

namespace Bu\Server\Database\Seeders;

use Illuminate\Database\Seeder;
use Bu\Server\Models\Location;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = [
            [
                'name' => 'Tokyo HQ',
                'address' => '1-1-1 Chiyoda',
                'city' => 'Tokyo',
                'state' => 'Tokyo',
                'country' => 'Japan',
                'postal_code' => '100-0001',
                'status' => 'active',
                'visible' => true,
                'order' => 1,
            ],
            [
                'name' => 'Osaka Branch',
                'address' => '1-1-1 Kita',
                'city' => 'Osaka',
                'state' => 'Osaka',
                'country' => 'Japan',
                'postal_code' => '530-0001',
                'status' => 'active',
                'visible' => true,
                'order' => 2,
            ],
            [
                'name' => 'Nagoya Sales',
                'address' => '1-1-1 Naka',
                'city' => 'Nagoya',
                'state' => 'Aichi',
                'country' => 'Japan',
                'postal_code' => '460-0001',
                'status' => 'active',
                'visible' => true,
                'order' => 3,
            ],
            [
                'name' => 'Fukuoka Branch',
                'address' => '1-1-1 Hakata',
                'city' => 'Fukuoka',
                'state' => 'Fukuoka',
                'country' => 'Japan',
                'postal_code' => '812-0001',
                'status' => 'active',
                'visible' => true,
                'order' => 4,
            ],
            [
                'name' => 'Sapporo R&D',
                'address' => '1-1-1 Chuo',
                'city' => 'Sapporo',
                'state' => 'Hokkaido',
                'country' => 'Japan',
                'postal_code' => '060-0001',
                'status' => 'active',
                'visible' => true,
                'order' => 5,
            ],
            [
                'name' => 'サーバ室',
                'address' => 'Tokyo HQ B1F',
                'city' => 'Tokyo',
                'state' => 'Tokyo',
                'country' => 'Japan',
                'postal_code' => '100-0001',
                'status' => 'active',
                'visible' => true,
                'order' => 6,
                'parent_id' => null, // This will be updated after Tokyo HQ is created
            ],
        ];

        foreach ($locations as $locationData) {
            $location = Location::create($locationData);
            if ($location->name === 'サーバ室') {
                $parent = Location::where('name', 'Tokyo HQ')->first();
                if ($parent) {
                    $location->parent_id = $parent->id;
                    $location->save();
                }
            }
        }
    }
}
