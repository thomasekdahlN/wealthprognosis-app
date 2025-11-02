<?php

/*
 * Copyright (C) 2024 Thomas Ekdahl
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

namespace App\Filament\Widgets\Compare;

use App\Models\AiInstruction;
use App\Models\SimulationConfiguration;
use App\Services\AiEvaluationService;
use App\Services\SimulationExportService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Log;

class CompareAiAnalysisWidget extends Widget
{
    protected static string $view = 'filament.widgets.compare.compare-ai-analysis-widget';

    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 'full';

    public ?SimulationConfiguration $simulationA = null;

    public ?SimulationConfiguration $simulationB = null;

    public ?string $aiAnalysis = null;

    public bool $isLoading = false;

    public ?string $errorMessage = null;

    public static function canView(): bool
    {
        return request()->routeIs('filament.admin.pages.compare-dashboard');
    }

    public function mount(?SimulationConfiguration $simulationA = null, ?SimulationConfiguration $simulationB = null): void
    {
        $this->simulationA = $simulationA;
        $this->simulationB = $simulationB;
    }

    public function loadAiAnalysis(): void
    {
        $this->isLoading = true;
        $this->errorMessage = null;
        $this->aiAnalysis = null;

        try {
            // Get the simulation comparison AI instruction
            $instruction = AiInstruction::where('type', 'simulation_comparison')
                ->where('is_active', true)
                ->where('user_id', auth()->id())
                ->first();

            if (! $instruction) {
                $this->errorMessage = 'AI comparison instruction not found. Please run the AI instruction seeder.';
                $this->isLoading = false;

                return;
            }

            // Export both simulations to JSON
            $simulationAJson = SimulationExportService::toJsonString($this->simulationA);
            $simulationBJson = SimulationExportService::toJsonString($this->simulationB);

            // Build the user prompt with both JSON data
            $userPrompt = $instruction->buildUserPrompt([
                'simulation_a_json' => $simulationAJson,
                'simulation_b_json' => $simulationBJson,
            ]);

            // Call the AI service
            $aiService = new AiEvaluationService;
            $response = $aiService->callOpenAI(
                $instruction->system_prompt,
                $userPrompt,
                $instruction->model,
                $instruction->max_tokens,
                (float) $instruction->temperature
            );

            $this->aiAnalysis = $response;

            Log::info('AI Comparison Analysis completed', [
                'simulation_a_id' => $this->simulationA->id,
                'simulation_b_id' => $this->simulationB->id,
                'user_id' => auth()->id(),
            ]);
        } catch (\Exception $e) {
            Log::error('AI Comparison Analysis failed', [
                'error' => $e->getMessage(),
                'simulation_a_id' => $this->simulationA?->id,
                'simulation_b_id' => $this->simulationB?->id,
                'user_id' => auth()->id(),
            ]);

            $this->errorMessage = 'Failed to generate AI analysis: '.$e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    protected function getViewData(): array
    {
        return [
            'simulationA' => $this->simulationA,
            'simulationB' => $this->simulationB,
            'aiAnalysis' => $this->aiAnalysis,
            'isLoading' => $this->isLoading,
            'errorMessage' => $this->errorMessage,
        ];
    }
}
