<?php

namespace YourCompany\GraphQLDAL\Database\Factories;

use YourCompany\GraphQLDAL\Models\Asset;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition()
    {
        return [
            'asset_id' => $this->faker->unique()->regexify('[A-Z]{2,4}-[0-9]{3,6}'),
            'type' => $this->faker->randomElement(['Laptop', 'Desktop', 'Monitor', 'Printer', 'Server']),
            'hostname' => $this->faker->domainWord() . '-' . $this->faker->randomNumber(3),
            'manufacturer' => $this->faker->randomElement(['Dell', 'HP', 'Lenovo', 'Apple', 'ASUS']),
            'model' => $this->faker->randomElement(['Latitude 5520', 'EliteDesk 800', 'ThinkPad X1', 'MacBook Pro', 'VivoBook']),
            'part_number' => $this->faker->regexify('[A-Z0-9]{8,12}'),
            'serial_number' => $this->faker->regexify('[A-Z0-9]{10,15}'),
            'form_factor' => $this->faker->randomElement(['Laptop', 'Desktop', 'Tower', 'All-in-One']),
            'os' => $this->faker->randomElement(['Windows 11', 'Windows 10', 'macOS', 'Ubuntu']),
            'os_bit' => $this->faker->randomElement(['64-bit', '32-bit']),
            'office_suite' => $this->faker->randomElement(['Microsoft Office 365', 'LibreOffice', 'Google Workspace']),
            'software_license_key' => $this->faker->regexify('[A-Z0-9]{5}-[A-Z0-9]{5}-[A-Z0-9]{5}-[A-Z0-9]{5}'),
            'wired_mac_address' => $this->faker->macAddress(),
            'wired_ip_address' => $this->faker->ipv4(),
            'wireless_mac_address' => $this->faker->macAddress(),
            'wireless_ip_address' => $this->faker->ipv4(),
            'purchase_date' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'purchase_price' => $this->faker->randomFloat(2, 500, 5000),
            'purchase_price_tax_included' => $this->faker->randomFloat(2, 550, 5500),
            'depreciation_years' => $this->faker->numberBetween(3, 7),
            'depreciation_dept' => $this->faker->randomElement(['IT', 'Finance', 'HR', 'Operations']),
            'cpu' => $this->faker->randomElement(['Intel i5-11400', 'Intel i7-11800H', 'AMD Ryzen 5 5600X', 'Apple M1']),
            'memory' => $this->faker->randomElement(['8GB', '16GB', '32GB', '64GB']),
            'location' => $this->faker->randomElement(['Office A', 'Office B', 'Warehouse', 'Remote']),
            'status' => $this->faker->randomElement(['利用中', '保管中', '貸出中', '故障中', '廃止']),
            'previous_user' => $this->faker->name(),
            'user_id' => $this->faker->numberBetween(1, 100),
            'usage_start_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'usage_end_date' => $this->faker->optional()->dateTimeBetween('now', '+1 year'),
            'carry_in_out_agreement' => $this->faker->boolean(),
            'last_updated' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'updated_by' => $this->faker->name(),
            'notes' => $this->faker->optional()->sentence(),
            'project' => $this->faker->randomElement(['Project Alpha', 'Project Beta', 'Project Gamma']),
            'notes1' => $this->faker->optional()->sentence(),
            'notes2' => $this->faker->optional()->sentence(),
            'notes3' => $this->faker->optional()->sentence(),
            'notes4' => $this->faker->optional()->sentence(),
            'notes5' => $this->faker->optional()->sentence(),
        ];
    }
}
