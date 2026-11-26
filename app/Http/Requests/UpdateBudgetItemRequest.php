<?php

namespace App\Http\Requests;

use App\Models\BudgetItem;
use Illuminate\Contracts\Validation\Validator;

class UpdateBudgetItemRequest extends StoreBudgetItemRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $item = $this->route('budgetItem');

        if ($item instanceof BudgetItem) {
            $this->errorBag = 'budget-'.$item->getKey();
        }
    }

    protected function failedValidation(Validator $validator): void
    {
        $item = $this->route('budgetItem');

        if ($item instanceof BudgetItem) {
            session()->flash('budget_edit', $item->getKey());
        }

        parent::failedValidation($validator);
    }
}
