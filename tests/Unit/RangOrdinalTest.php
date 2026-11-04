<?php

namespace Tests\Unit;

use App\Models\Entreprise;
use PHPUnit\Framework\TestCase;

class RangOrdinalTest extends TestCase
{
    public function test_le_premier_rang_s_ecrit_1er(): void
    {
        $this->assertSame('1er', Entreprise::rangOrdinal(1));
    }

    public function test_les_rangs_suivants_s_ecrivent_avec_e(): void
    {
        $this->assertSame('2e', Entreprise::rangOrdinal(2));
        $this->assertSame('11e', Entreprise::rangOrdinal(11));
        $this->assertSame('142e', Entreprise::rangOrdinal(142));
    }
}
