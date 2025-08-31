<?php

namespace App\Livewire;

use App\Models\AssetConfiguration;
use App\Services\AssetConfigurationSessionService;
use Livewire\Component;

class AssetConfigurationPicker extends Component
{
    public ?int $selectedAssetOwnerId = null;
    public bool $showDropdown = false;
    public string $search = '';

    public function mount(): void
    {
        $this->selectedAssetOwnerId = AssetConfigurationSessionService::getActiveAssetOwnerId();

        // Auto-select first owner if none is selected
        if (!$this->selectedAssetOwnerId) {
            $firstOwner = AssetConfiguration::query()->orderBy('name')->first();
            if ($firstOwner) {
                $this->selectedAssetOwnerId = $firstOwner->id;
                AssetConfigurationSessionService::setActiveAssetOwner($firstOwner);
            }
        }
    }

    public function selectAssetConfiguration(?int $assetConfigurationId): void
    {
        $this->selectedAssetOwnerId = $assetConfigurationId;

        if ($assetConfigurationId) {
            $assetConfiguration = AssetConfiguration::find($assetConfigurationId);
            AssetConfigurationSessionService::setActiveAssetOwner($assetConfiguration);
        } else {
            AssetConfigurationSessionService::clearActiveAssetOwner();
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
        return $this->selectedAssetOwnerId ? AssetConfiguration::find($this->selectedAssetOwnerId) : null;
    }

    public function render()
    {
        return view('livewire.asset-configuration-picker');
    }
}
