<?php

namespace Database\Seeders;

use App\Models\Visit;
use App\Models\Invoice;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $visit = Visit::create([
            'patient_name' => 'Uwese Alise',
            'visit_id' => 'VIS-001234',
        ]);

        $invoice = $visit->invoices()->create([
            'transaction_ref' => 'ef-ABC123XYZ',
            'total_amount' => 16000,
            'status' => 'pending',
        ]);

        $invoice->items()->createMany([
            ['description' => 'Malaria RDT', 'qty' => 1, 'price' => 5000, 'total' => 5000, 'type' => 'lab'],
            ['description' => 'Consultation', 'qty' => 1, 'price' => 10000, 'total' => 10000, 'type' => 'consultation'],
            ['description' => 'Paracetamol', 'qty' => 2, 'price' => 500, 'total' => 1000, 'type' => 'medication'],
        ]);

        $visit2 = Visit::create([
            'patient_name' => 'Kalisa Jean',
            'visit_id' => 'VIS-778899',
        ]);

        $invoice2 = $visit2->invoices()->create([
            'transaction_ref' => 'ef-KJ999XYZ',
            'total_amount' => 20000,
            'status' => 'pending',
        ]);

        $invoice2->items()->createMany([
            ['description' => 'Full Blood Count', 'qty' => 1, 'price' => 7000, 'total' => 7000, 'type' => 'lab'],
            ['description' => 'Consultation', 'qty' => 1, 'price' => 10000, 'total' => 10000, 'type' => 'consultation'],
            ['description' => 'Amoxicillin', 'qty' => 3, 'price' => 1000, 'total' => 3000, 'type' => 'medication'],
        ]);
    }
}
