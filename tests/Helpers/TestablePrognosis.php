<?php

namespace Tests\Helpers;

use App\Services\Prognosis\PrognosisService;

class TestablePrognosis extends PrognosisService
{
    public function isSavingPublic(string $assetType): bool
    {
        return $this->isSavingType($assetType);
    }
}
