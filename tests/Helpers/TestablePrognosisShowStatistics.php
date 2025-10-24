<?php

namespace Tests\Helpers;

use App\Services\Prognosis\PrognosisService;

class TestablePrognosisShowStatistics extends PrognosisService
{
    public function isShownInStatisticsPublic(string $assetType): bool
    {
        return $this->isShownInStatistics($assetType);
    }
}
