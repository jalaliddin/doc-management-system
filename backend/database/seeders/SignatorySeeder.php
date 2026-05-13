<?php

namespace Database\Seeders;

use App\Models\Signatory;
use Illuminate\Database\Seeder;

class SignatorySeeder extends Seeder
{
    public function run(): void
    {
        $signatories = [
            ['position' => 'Bosh direktor', 'full_name' => 'Qodirov Anvar Xoliqovich', 'is_active' => true],
            ['position' => 'Bosh direktorning birinchi o\'rinbosari', 'full_name' => 'Xasanov Ulugbek Mamatovich', 'is_active' => true],
            ['position' => 'Bosh direktorning ishlab chiqarish bo\'yicha o\'rinbosari', 'full_name' => 'Nazarov Kamol Baxtiyorovich', 'is_active' => true],
            ['position' => 'Bosh direktorning moliya bo\'yicha o\'rinbosari', 'full_name' => 'Toshpulatov Sardor Raximovich', 'is_active' => true],
            ['position' => 'Bosh muhandis', 'full_name' => 'Yoqubov Firdavs Sultonovich', 'is_active' => true],
        ];

        foreach ($signatories as $sig) {
            Signatory::create($sig);
        }
    }
}
