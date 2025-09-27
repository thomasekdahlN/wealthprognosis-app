<?php

namespace App\Livewire;

use App\Models\AssetConfiguration;
use App\Services\CurrentAssetConfiguration;
use Livewire\Component;

class AssetConfigurationPicker extends Component
{
    public ?int $selectedAssetConfigurationId = null;

    public bool $showDropdown = false;

    public string $search = '';

    public function mount(): void
    {
        $this->selectedAssetConfigurationId = app(CurrentAssetConfiguration::class)->id();

        // Auto-select first configuration if none is selected
        if (! $this->selectedAssetConfigurationId) {
            $firstConfiguration = AssetConfiguration::query()->orderBy('name')->first();
            if ($firstConfiguration) {
                $this->selectedAssetConfigurationId = $firstConfiguration->id;
                app(CurrentAssetConfiguration::class)->set($firstConfiguration);
            }
        }
    }

    public function selectAssetConfiguration(?int $assetConfigurationId): void
    {
        $this->selectedAssetConfigurationId = $assetConfigurationId;

        if ($assetConfigurationId) {
            $assetConfiguration = AssetConfiguration::find($assetConfigurationId);
            app(CurrentAssetConfiguration::class)->set($assetConfiguration);
        } else {
            app(CurrentAssetConfiguration::class)->set(null);
        }

        $this->showDropdown = false;
        $this->search = '';

        // Redirect using pretty URLs. If coming from assets index, go to config-scoped assets.
        $referer = request()->headers->get('referer');

        if (! $referer) {
            $this->redirect(route('filament.admin.pages.config-assets', ['record' => $assetConfigurationId]));

            return;
        }

        if (str_contains($referer, '/admin/assets')) {
            $this->redirect(route('filament.admin.pages.config-assets', ['record' => $assetConfigurationId]));

            return;
        }

        // Default: just refresh the page without leaking query parameters
        [$base] = explode('?', $referer, 2);
        $this->redirect($base);
    }

    public function toggleDropdown(): void
    {
        $this->showDropdown = ! $this->showDropdown;
        if ($this->showDropdown) {
            $this->search = '';
        }
    }

    public function clearSearch(): void
    {
        $this->search = '';
    }

    public function getAssetConfigurationsProperty()
    {
        $query = AssetConfiguration::query()->orderBy('name');

        if (trim($this->search) !== '') {
            $searchTerm = trim($this->search);
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('description', 'LIKE', "%{$searchTerm}%");
            });
        }

        return $query->get();
    }

    public function getSelectedAssetConfigurationProperty(): ?AssetConfiguration
    {
        return $this->selectedAssetConfigurationId ? AssetConfiguration::find($this->selectedAssetConfigurationId) : null;
    }

    public function render()
    {
        return view('livewire.asset-configuration-picker');
    }
}
