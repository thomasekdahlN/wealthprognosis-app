<?php

namespace Database\Seeders;

use App\Models\AiInstruction;
use App\Models\User;
use Illuminate\Database\Seeder;

class AiInstructionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();

        if (! $user) {
            $this->command->warn('No users found. Skipping AI instruction seeding.');

            return;
        }

        $instructions = [
            [
                'name' => 'Asset Portfolio Analysis',
                'description' => 'Comprehensive analysis of asset portfolio with recommendations',
                'type' => 'portfolio_analysis',
                'system_prompt' => 'You are an expert financial advisor with deep knowledge of asset allocation, risk management, and wealth building strategies. You analyze asset portfolios and provide actionable insights and recommendations. Focus on diversification, risk assessment, tax efficiency, and long-term wealth building strategies.',
                'user_prompt_template' => 'Please analyze the following asset portfolio data and provide a comprehensive evaluation:

{json_data}

Please provide:
1. Overall portfolio assessment
2. Asset allocation analysis
3. Risk evaluation
4. Tax efficiency review
5. Specific recommendations for improvement
6. Potential concerns or red flags

Format your response in clear sections with actionable insights.',
                'model' => 'gpt-4',
                'max_tokens' => 2000,
                'temperature' => 0.7,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Risk Assessment',
                'description' => 'Focus on risk analysis and mitigation strategies',
                'type' => 'risk_assessment',
                'system_prompt' => 'You are a risk management specialist focused on identifying and mitigating financial risks in investment portfolios. You excel at analyzing asset correlations, concentration risks, and market vulnerabilities.',
                'user_prompt_template' => 'Analyze the risk profile of this asset portfolio:

{json_data}

Focus on:
1. Concentration risk analysis
2. Asset correlation assessment
3. Market risk exposure
4. Liquidity risk evaluation
5. Currency and geographic risks
6. Risk mitigation recommendations

Provide specific, actionable risk management strategies.',
                'model' => 'gpt-4',
                'max_tokens' => 1500,
                'temperature' => 0.6,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Tax Optimization Review',
                'description' => 'Analysis focused on tax efficiency and optimization',
                'type' => 'tax_optimization',
                'system_prompt' => 'You are a tax-focused financial advisor specializing in tax-efficient investing and wealth preservation strategies. You understand various tax-advantaged accounts, tax-loss harvesting, and asset location strategies.',
                'user_prompt_template' => 'Review this portfolio for tax optimization opportunities:

{json_data}

Analyze:
1. Current tax efficiency
2. Asset location optimization
3. Tax-loss harvesting opportunities
4. Tax-advantaged account utilization
5. Income tax implications
6. Estate planning considerations

Provide specific tax optimization recommendations.',
                'model' => 'gpt-4',
                'max_tokens' => 1800,
                'temperature' => 0.5,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Simulation Comparison Analysis',
                'description' => 'Compare two financial simulations and provide recommendations',
                'type' => 'simulation_comparison',
                'system_prompt' => 'You are an expert financial advisor specializing in scenario analysis and financial planning. You excel at comparing different financial strategies, identifying trade-offs, and providing clear, actionable recommendations. You understand concepts like FIRE (Financial Independence, Retire Early), LTV (Loan-to-Value), DSCR (Debt Service Coverage Ratio), cash flow analysis, and long-term wealth building. Your analysis is thorough, balanced, and considers both quantitative metrics and qualitative factors like risk tolerance and life goals.',
                'user_prompt_template' => 'I need you to compare two financial simulation scenarios and provide a comprehensive analysis with recommendations.

**Simulation A (Baseline):**
{simulation_a_json}

**Simulation B (Alternative Scenario):**
{simulation_b_json}

Please provide a detailed comparison analysis in **markdown format** covering:

## Executive Summary
- Brief overview of both scenarios
- Key differences between the scenarios
- Your primary recommendation (which scenario is better and why)

## Financial Outcomes Comparison
- Final net worth comparison
- FIRE achievement timeline (if applicable)
- Debt-free timeline
- Cash flow analysis over time
- Total taxes paid

## Risk Analysis
- Leverage and LTV comparison
- Cash flow stability
- Debt service coverage
- Risk-adjusted returns

## Trade-offs and Considerations
- Short-term vs long-term impacts
- Liquidity considerations
- Flexibility and optionality
- Life goals alignment

## Detailed Recommendation
- Which scenario do you recommend and why?
- What are the key success factors?
- What risks should be monitored?
- Any suggested modifications or hybrid approaches?

## Action Items
- Specific next steps to implement the recommended scenario
- Key milestones to track
- Warning signs to watch for

Please format your response with clear headings, bullet points, and emphasis on key insights. Use Norwegian number formatting where appropriate (space as thousand separator).',
                'model' => 'gpt-4',
                'max_tokens' => 3000,
                'temperature' => 0.7,
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($instructions as $instruction) {
            AiInstruction::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'name' => $instruction['name'],
                ],
                [
                    ...$instruction,
                    'team_id' => $user->current_team_id,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                    'created_checksum' => hash('sha256', $instruction['name'].'_created'),
                    'updated_checksum' => hash('sha256', $instruction['name'].'_updated'),
                ]
            );
        }

        $this->command->info('Ensured '.count($instructions).' AI instructions');
    }
}
