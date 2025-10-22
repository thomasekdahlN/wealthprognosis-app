<?php

namespace Tests\Helpers;

use App\Models\Core\Prognosis;

class TestablePrognosis extends Prognosis
{
    public function isSavingPublic(string $assetType): bool
    {
        return $this->isSavingType($assetType);
    }
}

