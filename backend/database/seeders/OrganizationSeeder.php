<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\OrganizationLeader;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $organizations = [
            [
                'name' => 'O\'zbekiston Respublikasi Energetika vazirligi',
                'type' => 'yuqori',
                'leaders' => [
                    ['position' => 'Vazir', 'full_name' => 'Jurayev Jorabek Xolmatovich'],
                ],
            ],
            [
                'name' => 'O\'zbekiston Respublikasi Hukumati',
                'type' => 'yuqori',
                'leaders' => [
                    ['position' => 'Bosh vazir', 'full_name' => 'Abdulla Aripov'],
                ],
            ],
            [
                'name' => 'Urganch shahar hokimligi',
                'type' => 'yuqori',
                'leaders' => [
                    ['position' => 'Hokim', 'full_name' => 'Mamatmusayev Nodir Yaxshiliqovich'],
                ],
            ],
            [
                'name' => 'Xorazm viloyati hokimligi',
                'type' => 'yuqori',
                'leaders' => [
                    ['position' => 'Hokim', 'full_name' => 'Xolmatov Bahodir Nishonovich'],
                ],
            ],
            [
                'name' => 'Xonqa tuman gaz ta\'minot korxonasi',
                'type' => 'quyi',
                'leaders' => [
                    ['position' => 'Direktor', 'full_name' => 'Yusupov Alisher Baxromovich'],
                ],
            ],
            [
                'name' => 'Urganch shahar gaz ta\'minot korxonasi',
                'type' => 'quyi',
                'leaders' => [
                    ['position' => 'Direktor', 'full_name' => 'Qodirov Sanjar Hamidovich'],
                ],
            ],
            [
                'name' => 'Bog\'ot tuman gaz ta\'minot korxonasi',
                'type' => 'quyi',
                'leaders' => [
                    ['position' => 'Direktor', 'full_name' => 'Holiqov Mansur Toshpulatovich'],
                ],
            ],
            [
                'name' => '"UzGazInvest" JSC',
                'type' => 'boshqa',
                'leaders' => [
                    ['position' => 'Bosh direktor', 'full_name' => 'Ibragimov Rustam Normatovich'],
                ],
            ],
            [
                'name' => '"Xorazmgaz" AJ',
                'type' => 'boshqa',
                'leaders' => [
                    ['position' => 'Bosh direktor', 'full_name' => 'Tursunov Bobur Abdurahmonovich'],
                ],
            ],
        ];

        foreach ($organizations as $orgData) {
            $leaders = $orgData['leaders'];
            unset($orgData['leaders']);

            $org = Organization::create($orgData);

            foreach ($leaders as $leader) {
                OrganizationLeader::create([
                    'organization_id' => $org->id,
                    'position' => $leader['position'],
                    'full_name' => $leader['full_name'],
                ]);
            }
        }
    }
}
