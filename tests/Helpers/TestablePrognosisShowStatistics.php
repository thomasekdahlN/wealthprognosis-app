<?php

namespace Tests\Helpers;

use App\Models\Core\Prognosis;

class TestablePrognosisShowStatistics extends Prognosis
{
    public function isShownInStatisticsPublic(string $assetType): bool
    {
        return $this->isShownInStatistics($assetType);
    }
}

