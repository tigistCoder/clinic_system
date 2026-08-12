<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Medicine;
use App\Models\Purchase;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with roles, medicines, and FEFO stock batches.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'System Admin',
                'username' => 'admin',
                'email' => 'admin@clinic.com',
                'password' => 'password123',
                'role' => 'admin',
                'status' => 'active',
            ],
            [
                'name' => 'Reception User',
                'username' => 'reception',
                'email' => 'reception@clinic.com',
                'password' => 'password123',
                'role' => 'reception',
                'status' => 'active',
            ],
            [
                'name' => 'Nurse Sara',
                'username' => 'nurse',
                'email' => 'nurse@clinic.com',
                'password' => 'password123',
                'role' => 'nurse',
                'status' => 'active',
            ],
            [
                'name' => 'Dr. Abebe',
                'username' => 'doctor',
                'email' => 'doctor@clinic.com',
                'password' => 'password123',
                'role' => 'doctor',
                'status' => 'active',
            ],
            [
                'name' => 'Lab Tech Kebede',
                'username' => 'lab',
                'email' => 'lab@clinic.com',
                'password' => 'password123',
                'role' => 'lab',
                'status' => 'active',
            ],
            [
                'name' => 'Pharmacist Almaz',
                'username' => 'pharmacy',
                'email' => 'pharmacy@clinic.com',
                'password' => 'password123',
                'role' => 'pharmacy',
                'status' => 'active',
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['username' => $userData['username']],
                $userData
            );
        }

        $medicines = [
            [
                'name'           => 'Paracetamol',
                'category'       => 'Analgesics',
                'generic_name'   => 'Acetaminophen 500mg',
                'price_per_unit' => 15.00,
                'reorder_level'  => 10,
                'batches' => [
                    [
                        'batch_number'       => 'PAR-2026-B1',
                        'quantity_purchased' => 100,
                        'purchase_date'      => now()->subMonth()->toDateString(),
                        'expiry_date'        => now()->addMonths(8)->toDateString(),
                    ],
                    // Sample expired batch to verify automatic stock exclusion logic
                    [
                        'batch_number'       => 'PAR-2025-EXP',
                        'quantity_purchased' => 50,
                        'purchase_date'      => now()->subYear()->toDateString(),
                        'expiry_date'        => now()->subDays(10)->toDateString(),
                    ],
                ]
            ],
            [
                'name'           => 'Amoxicillin',
                'category'       => 'Antibiotics',
                'generic_name'   => 'Amoxicillin 500mg Capsule',
                'price_per_unit' => 45.00,
                'reorder_level'  => 10,
                'batches' => [
                    [
                        'batch_number'       => 'AMX-2026-FEFO1',
                        'quantity_purchased' => 30,
                        'purchase_date'      => now()->subDays(20)->toDateString(),
                        'expiry_date'        => now()->addMonths(2)->toDateString(),
                    ],
                    [
                        'batch_number'       => 'AMX-2026-FEFO2',
                        'quantity_purchased' => 50,
                        'purchase_date'      => now()->subDays(10)->toDateString(),
                        'expiry_date'        => now()->addMonths(12)->toDateString(),
                    ],
                ]
            ],
            [
                'name'           => 'Ibuprofen',
                'category'       => 'Analgesics',
                'generic_name'   => 'Ibuprofen 400mg Tablet',
                'price_per_unit' => 25.00,
                'reorder_level'  => 10,
                'batches' => [
                    [
                        'batch_number'       => 'IBU-2026-B1',
                        'quantity_purchased' => 80,
                        'purchase_date'      => now()->subDays(15)->toDateString(),
                        'expiry_date'        => now()->addYear()->toDateString(),
                    ],
                ]
            ],
            [
                'name'           => 'Omeprazole',
                'category'       => 'Gastrointestinal',
                'generic_name'   => 'Omeprazole 20mg Capsule',
                'price_per_unit' => 30.00,
                'reorder_level'  => 10,
                'batches' => [
                    [
                        'batch_number'       => 'OMP-2026-B1',
                        'quantity_purchased' => 60,
                        'purchase_date'      => now()->subDays(15)->toDateString(),
                        'expiry_date'        => now()->addYear()->toDateString(),
                    ],
                ]
            ],
            [
                'name'           => 'Ciprofloxacin',
                'category'       => 'Antibiotics',
                'generic_name'   => 'Ciprofloxacin 500mg Tablet',
                'price_per_unit' => 50.00,
                'reorder_level'  => 10,
                'batches' => [
                    [
                        'batch_number'       => 'CIP-2026-B1',
                        'quantity_purchased' => 40,
                        'purchase_date'      => now()->subDays(15)->toDateString(),
                        'expiry_date'        => now()->addYear()->toDateString(),
                    ],
                ]
            ],
        ];

        foreach ($medicines as $medData) {
            $batches = $medData['batches'];
            unset($medData['batches']);

            $medicine = Medicine::updateOrCreate(
                ['name' => $medData['name']],
                $medData
            );

            foreach ($batches as $batchData) {
                Purchase::updateOrCreate(
                    [
                        'medicine_id'  => $medicine->id,
                        'batch_number' => $batchData['batch_number']
                    ],
                    [
                        'quantity_purchased' => $batchData['quantity_purchased'],
                        'quantity_sold'      => 0,
                        'remaining_quantity' => $batchData['quantity_purchased'],
                        'purchase_date'      => $batchData['purchase_date'],
                        'expiry_date'        => $batchData['expiry_date'],
                    ]
                );
            }
        }
    }
}