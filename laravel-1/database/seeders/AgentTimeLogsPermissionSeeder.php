<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Permission;

class AgentTimeLogsPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::firstOrCreate(['name' => 'agent_time_logs.view']);
        Permission::firstOrCreate(['name' => 'agent_time_logs.create']);
            Permission::firstOrCreate(['name' => 'agent_time_logs.edit']);
        Permission::firstOrCreate(['name' => 'agent_time_logs.delete']);
        //
        
    }
}
