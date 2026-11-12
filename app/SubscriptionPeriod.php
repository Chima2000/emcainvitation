<?php

namespace App;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

enum SubscriptionPeriod: string
{
    case Week = 'week';
    case Month = 'month';
    case TwoMonths = 'two_months';
    case ThreeMonths = 'three_months';
    case SixMonths = 'six_months';
    case Year = 'year';

    public function label(): string
    {
        return __('labels.subscription_periods.'.$this->value);
    }

    public function endsAfter(CarbonInterface $start): CarbonImmutable
    {
        $start = CarbonImmutable::instance($start);

        return match ($this) {
            self::Week => $start->addWeek(),
            self::Month => $start->addMonthNoOverflow(),
            self::TwoMonths => $start->addMonthsNoOverflow(2),
            self::ThreeMonths => $start->addMonthsNoOverflow(3),
            self::SixMonths => $start->addMonthsNoOverflow(6),
            self::Year => $start->addYearNoOverflow(),
        };
    }

    /**
     * Rough length in days, used to show the price per day.
     */
    public function approximateDays(): int
    {
        return match ($this) {
            self::Week => 7,
            self::Month => 30,
            self::TwoMonths => 60,
            self::ThreeMonths => 90,
            self::SixMonths => 182,
            self::Year => 365,
        };
    }
}
