<?php

namespace Database\Seeders;

use App\Models\TestCrmCustomer;
use Illuminate\Database\Seeder;

class TestCrmCustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            ['name' => 'Acme Corporation', 'phone' => '15559876543', 'email' => 'contact@acme.com', 'company' => 'Acme Corp', 'status' => 'won', 'value' => 15000.00, 'notes' => 'Enterprise deal closed.'],
            ['name' => 'Beta Industries', 'phone' => '15559876544', 'email' => 'info@beta.com', 'company' => 'Beta Industries', 'status' => 'qualified', 'value' => 8500.50, 'notes' => 'Qualified lead, awaiting proposal.'],
            ['name' => 'Gamma Solutions', 'phone' => '15559876545', 'email' => 'hello@gamma.com', 'company' => 'Gamma Solutions', 'status' => 'new', 'value' => 3200.00, 'notes' => 'New inquiry from website.'],
            ['name' => 'Delta Services', 'phone' => '15559876546', 'email' => 'support@delta.com', 'company' => 'Delta Services', 'status' => 'lost', 'value' => 1200.00, 'notes' => 'Lost to competitor.'],
            ['name' => 'Epsilon Ltd', 'phone' => '15559876547', 'email' => 'contact@epsilon.com', 'company' => 'Epsilon Ltd', 'status' => 'won', 'value' => 22000.00, 'notes' => 'Large contract signed.'],
            ['name' => 'Zeta Tech', 'phone' => '15559876548', 'email' => 'sales@zeta.com', 'company' => 'Zeta Tech', 'status' => 'new', 'value' => 5000.00, 'notes' => 'Initial contact made.'],
            ['name' => 'Eta Consulting', 'phone' => '15559876549', 'email' => 'info@eta.com', 'company' => 'Eta Consulting', 'status' => 'qualified', 'value' => 7800.00, 'notes' => 'Qualified, demo scheduled.'],
            ['name' => 'Theta Group', 'phone' => '15559876550', 'email' => 'theta@group.com', 'company' => 'Theta Group', 'status' => 'won', 'value' => 18500.00, 'notes' => 'Deal closed successfully.'],
            ['name' => 'Iota Systems', 'phone' => '15559876551', 'email' => 'info@iota.com', 'company' => 'Iota Systems', 'status' => 'lost', 'value' => 900.00, 'notes' => 'Budget constraints.'],
            ['name' => 'Kappa Partners', 'phone' => '15559876552', 'email' => 'partners@kappa.com', 'company' => 'Kappa Partners', 'status' => 'new', 'value' => 4500.00, 'notes' => 'New lead from referral.'],
        ];

        foreach ($customers as $customer) {
            TestCrmCustomer::create($customer);
        }
    }
}
