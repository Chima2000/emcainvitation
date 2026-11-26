<?php

namespace App\Http\Requests;

use App\Models\Pledge;
use App\PledgeStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class UpdatePledgeRequest extends StorePledgeRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->isStatusChange()) {
            return;
        }

        parent::prepareForValidation();

        $pledge = $this->route('pledge');

        if ($pledge instanceof Pledge) {
            $this->errorBag = 'pledge-'.$pledge->getKey();
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if ($this->isStatusChange()) {
            return [
                'status' => ['required', Rule::enum(PledgeStatus::class)],
            ];
        }

        return parent::rules();
    }

    protected function failedValidation(Validator $validator): void
    {
        $pledge = $this->route('pledge');

        if ($pledge instanceof Pledge && ! $this->isStatusChange()) {
            session()->flash('pledge_edit', $pledge->getKey());
        }

        parent::failedValidation($validator);
    }

    private function isStatusChange(): bool
    {
        return ! $this->exists('donor_name') && $this->exists('status');
    }
}
