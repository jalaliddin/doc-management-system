<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Ishlab chiqarish bo\'limi', 'index_code' => 'ИШ/', 'head_name' => 'Rahimov Behruz Salimovich', 'head_phone' => '2-34'],
            ['name' => 'Moliya bo\'limi', 'index_code' => 'МОЛ/', 'head_name' => 'Toshmatov Jasur Hamidovich', 'head_phone' => '2-41'],
            ['name' => 'Kadrlar bo\'limi', 'index_code' => 'КАД/', 'head_name' => 'Yusupova Nilufar Karimovna', 'head_phone' => '2-18'],
            ['name' => 'Huquq bo\'limi', 'index_code' => 'ҲУҚ/', 'head_name' => 'Mirzayev Otabek Nematovich', 'head_phone' => '2-27'],
            ['name' => 'Texnik xavfsizlik bo\'limi', 'index_code' => 'ТХ/', 'head_name' => 'Ergashev Sherzod Baxtiyorovich', 'head_phone' => '2-52'],
            ['name' => 'Iqtisod bo\'limi', 'index_code' => 'ИҚТ/', 'head_name' => 'Xolmatov Sardor Umarovich', 'head_phone' => '2-35'],
            ['name' => 'Buxgalteriya', 'index_code' => 'БУХ/', 'head_name' => 'Nazarova Zulfiya Alimovna', 'head_phone' => '2-44'],
            ['name' => 'Axborot texnologiyalari xizmati', 'index_code' => 'АТ/', 'head_name' => 'Saidov Jalol Sultonovich', 'head_phone' => '2-60'],
        ];

        foreach ($departments as $dept) {
            Department::create($dept);
        }
    }
}
