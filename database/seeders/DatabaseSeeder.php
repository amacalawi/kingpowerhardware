<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            'name' => 'Super Admin',
            'email' => 'superadmin',
            'username' => 'superadmin',
            'password' => '$2y$10$VOcL1XkcOPcQM6PWRuYh1e3WSfQ4lxOAeDAMnuIykXWTgg8u6ZbCW',
            'secret_question_id' => 1,
            'secret_password' => '$2y$10$VOcL1XkcOPcQM6PWRuYh1e3WSfQ4lxOAeDAMnuIykXWTgg8u6ZbCW',
            'type' => 'superadmin'
        ]);

        $item_categories = [
            [
                'code' => "chemicals",
                'name' => "Chemicals",
                'description' => "Chemicals",
                'created_by' => 1
            ], 
            [
                'code' => "adhesives",
                'name' => "Adhesives",
                'description' => "Adhesives",
                'created_by' => 1
            ], 
            [
                'code' => "construction-items",
                'name' => "Construction Items",
                'description' => "Construction Items",
                'created_by' => 1
            ],
            [
                'code' => "plupming-items",
                'name' => "Plupming Items",
                'description' => "Plupming Items",
                'created_by' => 1
            ],
            [
                'code' => "tools-and-equipments",
                'name' => "Tools and Equipments",
                'description' => "Tools and Equipments",
                'created_by' => 1
            ],
            [
                'code' => "electrical-items",
                'name' => "Electrical items",
                'description' => "Electrical items",
                'created_by' => 1
            ]
        ];
        foreach ($item_categories as $item_category) {
            DB::table('items_category')->insert($item_category);
        }

        $branches = [
            [
                'code' => "pampangga",
                'name' => "Pampanga",
                'description' => "Pampanga (Main Branch)",
                'activation_code' => "d6821f8b1cd3dfd525bd21209739d64a",
                'is_srp' => 0,
                'created_by' => 1
            ], 
            [
                'code' => "negros-oriental",
                'name' => "Negros Oriental",
                'description' => "Negros Oriental (Bais)",
                'activation_code' => "89995a398cd6871276bc35289e03361e",
                'is_srp' => 1,
                'created_by' => 1
            ], 
            [
                'code' => "bicol",
                'name' => "Bicol",
                'description' => "Bicol (Baao)",
                'activation_code' => "d4916070cf373682733da283986d36b0",
                'is_srp' => 0,
                'created_by' => 1
            ]
        ];
        foreach ($branches as $branch) {
            DB::table('branches')->insert($branch);
        }
    }
}
