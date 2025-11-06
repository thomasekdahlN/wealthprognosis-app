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

use App\Jobs\ProcessAiComparisonAnalysis;
use App\Models\SimulationConfiguration;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;

class CompareAiAnalysisWidget extends Widget
{
    protected string $view = 'filament.widgets.compare.compare-ai-analysis-widget';

    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 'full';

    public ?SimulationConfiguration $simulationA = null;

    public ?SimulationConfiguration $simulationB = null;

    public ?string $aiAnalysis = null;

    public bool $isLoading = false;

    public ?string $errorMessage = null;

    public ?string $loadingStatus = null;

    public ?string $jobCacheKey = null;

    // Polling interval in milliseconds (2 seconds)
    public int $pollingInterval = 2000;

    public static function canView(): bool
    {
        return request()->routeIs('filament.admin.pages.compare-dashboard');
    }

    public function mount(?SimulationConfiguration $simulationA = null, ?SimulationConfiguration $simulationB = null): void
    {
        $this->simulationA = $simulationA;
        $this->simulationB = $simulationB;
        $this->jobCacheKey = 'ai_comparison_'.auth()->id().'_'.$simulationA?->id.'_'.$simulationB?->id;

        // Check if there's a cached result
        $this->checkJobStatus();
    }

    public function loadAiAnalysis(): void
    {
        // Clear any previous results
        Cache::forget($this->jobCacheKey.':result');
        Cache::forget($this->jobCacheKey.':error');
        Cache::forget($this->jobCacheKey.':status');
        Cache::forget($this->jobCacheKey.':completed');

        $this->isLoading = true;
        $this->errorMessage = null;
        $this->aiAnalysis = null;
        $this->loadingStatus = 'Initializing AI analysis...';

        // Dispatch the job
        ProcessAiComparisonAnalysis::dispatch(
            $this->simulationA->id,
            $this->simulationB->id,
            auth()->id(),
            $this->jobCacheKey
        );

        // Start polling for status updates
        $this->dispatch('start-polling');
    }

    public function checkJobStatus(): void
    {
        // Check if job is completed
        if (Cache::has($this->jobCacheKey.':completed')) {
            $this->aiAnalysis = Cache::get($this->jobCacheKey.':result');
            $this->isLoading = false;
            $this->loadingStatus = null;
            $this->dispatch('stop-polling');

            return;
        }

        // Check for errors
        if (Cache::has($this->jobCacheKey.':error')) {
            $this->errorMessage = Cache::get($this->jobCacheKey.':error');
            $this->isLoading = false;
            $this->loadingStatus = null;
            $this->dispatch('stop-polling');

            return;
        }

        // Check for status updates
        if (Cache::has($this->jobCacheKey.':status')) {
            $this->loadingStatus = Cache::get($this->jobCacheKey.':status');
            $this->isLoading = true;
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
            'loadingStatus' => $this->loadingStatus,
        ];
    }
}
