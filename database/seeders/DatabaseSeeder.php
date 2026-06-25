<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $admin = User::create([
            'name' => 'CID Admin',
            'email' => 'admin@consideritdone.com',
            'password' => bcrypt('Password123'),
            'email_verified_at' => now(),
        ]);

        $client = User::create([
            'name' => 'John Client',
            'email' => 'client@example.com',
            'password' => bcrypt('Password123'),
            'email_verified_at' => now(),
        ]);

        // Mock Bookings / Tickets
        \App\Models\OperationalTicket::create([
            'user_id' => $client->id,
            'service_type' => 'tax_prep',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'price' => 150.00,
            'input_parameters' => [
                'tax_year' => '2025',
                'filing_status' => 'married_jointly',
                'has_dependents' => true,
                'documents_requested' => ['W-2', '1099-INT', 'Mortgage Interest Statement'],
            ],
            'scheduled_at' => now()->addDays(2),
        ]);

        \App\Models\OperationalTicket::create([
            'user_id' => $client->id,
            'service_type' => 'solar',
            'status' => 'in_progress',
            'payment_status' => 'partial',
            'price' => 8500.00,
            'assigned_to_id' => $admin->id,
            'input_parameters' => [
                'roof_type' => 'shingle',
                'average_monthly_bill' => 240.00,
                'system_size_kw' => 8.5,
                'inverter_preference' => 'Enphase IQ8',
            ],
            'scheduled_at' => now()->addDays(5),
        ]);

        \App\Models\OperationalTicket::create([
            'user_id' => $client->id,
            'service_type' => 'procurement',
            'status' => 'completed',
            'payment_status' => 'paid',
            'price' => 25000.00,
            'assigned_to_id' => $admin->id,
            'input_parameters' => [
                'item_category' => 'automobile',
                'target_item' => '2023 Tesla Model Y',
                'max_budget' => 38000.00,
                'specifications' => [
                    'color' => 'white',
                    'trim' => 'Long Range',
                    'max_mileage' => 20000,
                ],
            ],
            'scheduled_at' => now()->subDays(10),
        ]);

        \App\Models\OperationalTicket::create([
            'user_id' => $client->id,
            'service_type' => 'virtual_bookkeeping',
            'status' => 'in_progress',
            'payment_status' => 'paid',
            'price' => 350.00,
            'assigned_to_id' => $admin->id,
            'input_parameters' => [
                'company_name' => 'Nova Tech Solutions',
                'volume' => 'medium',
                'system' => 'QuickBooks Online',
                'reconciliation_frequency' => 'daily',
            ],
            'scheduled_at' => now()->addDays(1),
        ]);

        \App\Models\OperationalTicket::create([
            'user_id' => $client->id,
            'service_type' => 'accounts_and_logistics',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'price' => 1200.00,
            'input_parameters' => [
                'company_name' => 'Swift Courier Service',
                'optimization' => 'Setmore Front Desk Integration',
                'marketing_strategy' => 'Google Ads Campaigns & Local SEO',
                'cross_selling_options' => true,
            ],
            'scheduled_at' => now()->addDays(4),
        ]);

        \App\Models\OperationalTicket::create([
            'user_id' => $client->id,
            'service_type' => 'procurement',
            'status' => 'in_progress',
            'payment_status' => 'partial',
            'price' => 45000.00,
            'assigned_to_id' => $admin->id,
            'input_parameters' => [
                'item_category' => 'Real Estate',
                'target_item' => 'Studio Apartment in Singapore',
                'max_budget' => 180000.00,
            ],
            'scheduled_at' => now()->addDays(12),
        ]);

        \App\Models\OperationalTicket::create([
            'user_id' => $client->id,
            'service_type' => 'tax_preparation',
            'status' => 'completed',
            'payment_status' => 'paid',
            'price' => 299.00,
            'assigned_to_id' => $admin->id,
            'input_parameters' => [
                'tax_year' => '2024',
                'filing_status' => 'single',
                'refund_status' => 'optimized',
            ],
            'scheduled_at' => now()->subDays(15),
        ]);
    }
}
