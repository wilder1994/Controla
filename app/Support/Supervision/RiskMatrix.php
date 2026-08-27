<?php

declare(strict_types=1);

namespace App\Support\Supervision;

use App\Enums\SupervisorFieldOutcome;
use App\Enums\SupervisorRecommendationPriority;
use App\Enums\SupervisorRiskImpact;
use App\Enums\SupervisorRiskLevel;
use App\Enums\SupervisorRiskLikelihood;

final class RiskMatrix
{
    public static function score(SupervisorRiskLikelihood $likelihood, SupervisorRiskImpact $impact): int
    {
        return $likelihood->score() * $impact->score();
    }

    public static function level(SupervisorRiskLikelihood $likelihood, SupervisorRiskImpact $impact): SupervisorRiskLevel
    {
        $score = self::score($likelihood, $impact);

        return match (true) {
            $score >= 17 => SupervisorRiskLevel::Extreme,
            $score >= 10 => SupervisorRiskLevel::High,
            $score >= 5 => SupervisorRiskLevel::Medium,
            default => SupervisorRiskLevel::Low,
        };
    }

    public static function priority(SupervisorRiskLevel $level): SupervisorRecommendationPriority
    {
        return match ($level) {
            SupervisorRiskLevel::Extreme => SupervisorRecommendationPriority::Urgent,
            SupervisorRiskLevel::High => SupervisorRecommendationPriority::High,
            SupervisorRiskLevel::Medium => SupervisorRecommendationPriority::Normal,
            SupervisorRiskLevel::Low => SupervisorRecommendationPriority::Low,
        };
    }

    public static function outcome(SupervisorRiskLevel $level): SupervisorFieldOutcome
    {
        return match ($level) {
            SupervisorRiskLevel::Extreme, SupervisorRiskLevel::High => SupervisorFieldOutcome::Critical,
            default => SupervisorFieldOutcome::Attention,
        };
    }

    /**
     * @param  list<SupervisorRiskLevel>  $levels
     */
    public static function worstOutcome(array $levels): SupervisorFieldOutcome
    {
        foreach ($levels as $level) {
            if (self::outcome($level) === SupervisorFieldOutcome::Critical) {
                return SupervisorFieldOutcome::Critical;
            }
        }

        return SupervisorFieldOutcome::Attention;
    }
}
