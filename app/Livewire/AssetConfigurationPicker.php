<?php

namespace App\Livewire;

use App\Models\AssetConfiguration;
use App\Services\AssetConfigurationSessionService;
use Livewire\Component;

class AssetConfigurationPicker extends Component
{
    public ?int $selectedAssetConfigurationId = null;
    public bool $showDropdown = false;
    public string $search = '';

    public function mount(): void
    {
        $this->selectedAssetConfigurationId = AssetConfigurationSessionService::getActiveAssetConfigurationId();

        // Auto-select first configuration if none is selected
        if (!$this->selectedAssetConfigurationId) {
            $firstConfiguration = AssetConfiguration::query()->orderBy('name')->first();
            if ($firstConfiguration) {
                $this->selectedAssetConfigurationId = $firstConfiguration->id;
                AssetConfigurationSessionService::setActiveAssetConfiguration($firstConfiguration);
            }
        }
    }

    public function selectAssetConfiguration(?int $assetConfigurationId): void
    {
        $this->selectedAssetConfigurationId = $assetConfigurationId;

        if ($assetConfigurationId) {
            $assetConfiguration = AssetConfiguration::find($assetConfigurationId);
            AssetConfigurationSessionService::setActiveAssetConfiguration($assetConfiguration);
        } else {
            AssetConfigurationSessionService::clearActiveAssetConfiguration();
        }

        $this->showDropdown = false;
        $this->search = '';

        // Refresh the page to update all components
        $this->redirect(request()->header('Referer') ?: '/admin');
    }

    public function toggleDropdown(): void
    {
        $this->showDropdown = !$this->showDropdown;
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
