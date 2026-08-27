<?php

declare(strict_types=1);

namespace Tests\Unit\Supervision;

use App\Enums\SupervisorRecommendationPriority;
use App\Enums\SupervisorRiskImpact;
use App\Enums\SupervisorRiskLevel;
use App\Enums\SupervisorRiskLikelihood;
use App\Support\Supervision\RiskMatrix;
use PHPUnit\Framework\TestCase;

final class RiskMatrixTest extends TestCase
{
    public function test_low_medium_high_extreme_thresholds(): void
    {
        $this->assertSame(
            SupervisorRiskLevel::Low,
            RiskMatrix::level(SupervisorRiskLikelihood::VeryLow, SupervisorRiskImpact::Minor),
        );
        $this->assertSame(
            SupervisorRiskLevel::Medium,
            RiskMatrix::level(SupervisorRiskLikelihood::Medium, SupervisorRiskImpact::Minor),
        );
        $this->assertSame(
            SupervisorRiskLevel::High,
            RiskMatrix::level(SupervisorRiskLikelihood::High, SupervisorRiskImpact::Major),
        );
        $this->assertSame(
            SupervisorRiskLevel::Extreme,
            RiskMatrix::level(SupervisorRiskLikelihood::VeryHigh, SupervisorRiskImpact::Major),
        );
    }

    public function test_priority_maps_from_level(): void
    {
        $this->assertSame(
            SupervisorRecommendationPriority::Urgent,
            RiskMatrix::priority(SupervisorRiskLevel::Extreme),
        );
        $this->assertSame(
            SupervisorRecommendationPriority::Low,
            RiskMatrix::priority(SupervisorRiskLevel::Low),
        );
    }
}
