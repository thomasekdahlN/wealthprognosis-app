<?php

/*
 * Copyright (C) 2024 Thomas Ekdahl
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

namespace App\Jobs;

use App\Models\AiInstruction;
use App\Models\SimulationConfiguration;
use App\Services\AiEvaluationService;
use App\Services\SimulationExportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessAiComparisonAnalysis implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600; // 10 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $simulationAId,
        public int $simulationBId,
        public int $userId,
        public string $cacheKey
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Set PHP execution time limit to 10 minutes (600 seconds)
        // This overrides the default 30 second limit
        set_time_limit(600);

        try {
            // Log that the job is starting (to verify new code is running)
            Log::channel('ai')->info('🚀 AI Comparison Job Started', [
                'simulation_a_id' => $this->simulationAId,
                'simulation_b_id' => $this->simulationBId,
                'user_id' => $this->userId,
                'timestamp' => now()->toDateTimeString(),
            ]);

            // Update status
            Cache::put($this->cacheKey.':status', 'Loading AI instruction configuration...', 600);

            // Get the simulation comparison AI instruction
            $instruction = AiInstruction::where('type', 'simulation_comparison')
                ->where('is_active', true)
                ->where('user_id', $this->userId)
                ->first();

            if (! $instruction) {
                Cache::put($this->cacheKey.':error', 'AI comparison instruction not found. Please run the AI instruction seeder.', 600);
                Cache::put($this->cacheKey.':status', null, 600);

                return;
            }

            // Load simulations
            Cache::put($this->cacheKey.':status', 'Loading simulation data...', 600);
            $simulationA = SimulationConfiguration::find($this->simulationAId);
            $simulationB = SimulationConfiguration::find($this->simulationBId);

            if (! $simulationA || ! $simulationB) {
                Cache::put($this->cacheKey.':error', 'One or both simulations not found.', 600);
                Cache::put($this->cacheKey.':status', null, 600);

                return;
            }

            // Export both simulations to CSV for AI analysis (compact version with essential fields)
            Cache::put($this->cacheKey.':status', 'Exporting simulation data...', 600);
            $simulationACsv = SimulationExportService::toCsvCompact($simulationA);
            $simulationBCsv = SimulationExportService::toCsvCompact($simulationB);

            // Log the CSV data being sent
            Log::info('AI Comparison - CSV Data Prepared', [
                'simulation_a_id' => $this->simulationAId,
                'simulation_a_name' => $simulationA->name,
                'simulation_a_csv_size' => strlen($simulationACsv),
                'simulation_a_csv_rows' => substr_count($simulationACsv, "\n"),
                'simulation_b_id' => $this->simulationBId,
                'simulation_b_name' => $simulationB->name,
                'simulation_b_csv_size' => strlen($simulationBCsv),
                'simulation_b_csv_rows' => substr_count($simulationBCsv, "\n"),
                'simulation_a_csv_preview' => substr($simulationACsv, 0, 500).'...',
                'simulation_b_csv_preview' => substr($simulationBCsv, 0, 500).'...',
            ]);

            // Build the user prompt with both CSV data
            Cache::put($this->cacheKey.':status', 'Preparing AI prompt...', 600);
            $userPrompt = $instruction->buildUserPrompt([
                'simulation_a_csv' => $simulationACsv,
                'simulation_b_csv' => $simulationBCsv,
            ]);

            Log::info('AI Comparison - User Prompt Built', [
                'user_prompt_length' => strlen($userPrompt),
                'system_prompt_length' => strlen($instruction->system_prompt),
                'estimated_total_tokens' => (int) ((strlen($userPrompt) + strlen($instruction->system_prompt)) / 4),
                'model' => $instruction->model,
                'max_tokens' => $instruction->max_tokens,
            ]);

            // Log the complete message structure being sent to AI (using dedicated ai channel)
            Log::channel('ai')->info('AI Comparison - Complete Message Structure', [
                'simulation_a_id' => $this->simulationAId,
                'simulation_a_name' => $simulationA->name,
                'simulation_b_id' => $this->simulationBId,
                'simulation_b_name' => $simulationB->name,
                'system_prompt' => $instruction->system_prompt,
                'user_prompt' => $userPrompt,
                'model' => $instruction->model,
                'max_tokens' => $instruction->max_tokens,
                'temperature' => $instruction->temperature,
                'simulation_a_csv' => $simulationACsv,
                'simulation_b_csv' => $simulationBCsv,
            ]);

            // Call the AI service
            Cache::put($this->cacheKey.':status', 'Calling AI service (this may take 1-3 minutes)...', 600);
            $aiService = new AiEvaluationService;
            $response = $aiService->callAi(
                $instruction->system_prompt,
                $userPrompt,
                $instruction->model,
                $instruction->max_tokens,
                (float) $instruction->temperature
            );

            // Check if the AI call was successful
            Cache::put($this->cacheKey.':status', 'Processing AI response...', 600);
            if (! $response['success']) {
                Cache::put($this->cacheKey.':error', $response['error'] ?? 'AI analysis failed', 600);
                Cache::put($this->cacheKey.':status', null, 600);

                return;
            }

            // Store the result in cache
            Cache::put($this->cacheKey.':result', $response['content'], 600);
            Cache::put($this->cacheKey.':status', 'Analysis complete!', 600);
            Cache::put($this->cacheKey.':completed', true, 600);

            Log::info('AI Comparison Analysis completed', [
                'simulation_a_id' => $this->simulationAId,
                'simulation_b_id' => $this->simulationBId,
                'user_id' => $this->userId,
            ]);
        } catch (\Exception $e) {
            Log::error('AI Comparison Analysis failed', [
                'error' => $e->getMessage(),
                'simulation_a_id' => $this->simulationAId,
                'simulation_b_id' => $this->simulationBId,
                'user_id' => $this->userId,
            ]);

            Cache::put($this->cacheKey.':error', 'Failed to generate AI analysis: '.$e->getMessage(), 600);
            Cache::put($this->cacheKey.':status', null, 600);
        }
    }
}
