<?php

namespace App;

enum BudgetKind: string
{
    case Income = 'income';
    case Expense = 'expense';

    public function label(): string
    {
        return __('labels.budget.'.$this->value);
    }
}
