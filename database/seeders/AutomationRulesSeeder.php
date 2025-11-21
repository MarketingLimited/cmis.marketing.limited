<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class AutomationRulesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds default automation rule templates for all organizations
     */
    public function run(): void
    {
        // Get all organizations
        $organizations = DB::table('cmis.organizations')->get();

        // Default automation rule templates
        $templates = [
            [
                'name' => 'Pause High CPA Campaigns',
                'description' => 'Automatically pause campaigns when cost per acquisition exceeds $50',
                'condition' => json_encode([
                    'metric' => 'cpa',
                    'operator' => '>',
                    'value' => 50
                ]),
                'action' => json_encode([
                    'type' => 'pause_underperforming'
                ]),
                'is_active' => false // Inactive by default, user must enable
            ],
            [
                'name' => 'Increase Budget for High ROAS',
                'description' => 'Automatically increase budget by 20% when ROAS exceeds 3.0x',
                'condition' => json_encode([
                    'metric' => 'roas',
                    'operator' => '>',
                    'value' => 3.0
                ]),
                'action' => json_encode([
                    'type' => 'increase_budget',
                    'value' => 20 // Increase by 20%
                ]),
                'is_active' => false
            ],
            [
                'name' => 'Decrease Budget for Low CTR',
                'description' => 'Automatically decrease budget by 30% when CTR is below 1%',
                'condition' => json_encode([
                    'metric' => 'ctr',
                    'operator' => '<',
                    'value' => 0.01 // 1%
                ]),
                'action' => json_encode([
                    'type' => 'decrease_budget',
                    'value' => 30 // Decrease by 30%
                ]),
                'is_active' => false
            ],
            [
                'name' => 'Alert on High Daily Spend',
                'description' => 'Send notification when daily spend exceeds $1,000',
                'condition' => json_encode([
                    'metric' => 'spend',
                    'operator' => '>',
                    'value' => 1000
                ]),
                'action' => json_encode([
                    'type' => 'notify'
                ]),
                'is_active' => false
            ],
            [
                'name' => 'Alert on Low Conversion Rate',
                'description' => 'Send notification when conversion rate drops below 2%',
                'condition' => json_encode([
                    'metric' => 'conversion_rate',
                    'operator' => '<',
                    'value' => 0.02 // 2%
                ]),
                'action' => json_encode([
                    'type' => 'notify'
                ]),
                'is_active' => false
            ],
            [
                'name' => 'Pause Low ROAS Campaigns',
                'description' => 'Automatically pause campaigns when ROAS falls below 1.5x',
                'condition' => json_encode([
                    'metric' => 'roas',
                    'operator' => '<',
                    'value' => 1.5
                ]),
                'action' => json_encode([
                    'type' => 'pause_underperforming'
                ]),
                'is_active' => false
            ]
        ];

        // Insert templates for each organization
        foreach ($organizations as $org) {
            foreach ($templates as $template) {
                DB::table('cmis_automation.automation_rules')->insert([
                    'id' => Uuid::uuid4()->toString(),
                    'org_id' => $org->id,
                    'name' => $template['name'],
                    'description' => $template['description'],
                    'condition' => $template['condition'],
                    'action' => $template['action'],
                    'is_active' => $template['is_active'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            $this->command->info("Created 6 automation rule templates for organization: {$org->name}");
        }

        $totalRules = count($organizations) * count($templates);
        $this->command->info("Total automation rules created: {$totalRules}");
    }
}
