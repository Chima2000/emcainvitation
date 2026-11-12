<?php

namespace App;

enum WalletEntryType: string
{
    case TopUp = 'top_up';
    case PackagePurchase = 'package_purchase';
    case MessageCharge = 'message_charge';
    case Adjustment = 'adjustment';
    case Subscription = 'subscription';

    public function label(): string
    {
        return __('labels.wallet_entries.'.$this->value);
    }

    public function isRevenue(): bool
    {
        return in_array($this, self::revenueTypes(), true);
    }

    /**
     * @return list<self>
     */
    public static function revenueTypes(): array
    {
        return [self::PackagePurchase, self::MessageCharge, self::Subscription];
    }
}
