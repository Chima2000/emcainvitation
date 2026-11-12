<?php

namespace App;

enum PaymentMethod: string
{
    case Mpesa = 'mpesa';
    case AirtelMoney = 'airtel_money';
    case MixxByYas = 'mixx_by_yas';
    case HaloPesa = 'halopesa';
    case Bank = 'bank';

    public function label(): string
    {
        return __('labels.payment_methods.'.$this->value);
    }

    public function logo(): string
    {
        return asset(match ($this) {
            self::Mpesa => 'images/payments/mpesa.svg',
            self::AirtelMoney => 'images/payments/airtel-money.png',
            self::MixxByYas => 'images/payments/mixx-by-yas.png',
            self::HaloPesa => 'images/payments/halopesa.png',
            self::Bank => 'images/payments/crdb-bank.png',
        });
    }

    public function payTo(): ?string
    {
        $account = config('emca.payment_accounts.'.$this->value);

        return is_string($account) && $account !== '' ? $account : null;
    }
}
