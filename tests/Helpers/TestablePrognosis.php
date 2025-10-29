<?php

namespace Tests\Helpers;

use App\Services\AssetTypeService;

class TestablePrognosis
{
    public function __construct(
        private AssetTypeService $assetTypeService
    ) {}

    public function isSavingPublic(string $assetType): bool
    {
        return $this->assetTypeService->isSavingType($assetType);
    }
}
