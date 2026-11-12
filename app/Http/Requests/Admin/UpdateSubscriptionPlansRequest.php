<?php

namespace App\Http\Requests\Admin;

use App\SubscriptionPeriod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSubscriptionPlansRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return collect(SubscriptionPeriod::cases())
            ->flatMap(fn (SubscriptionPeriod $period): array => [
                'plans.'.$period->value.'.price' => ['required', 'integer', 'min:0', 'max:100000000'],
                'plans.'.$period->value.'.is_active' => ['sometimes', 'boolean'],
            ])
            ->merge([
                'plans' => ['required', 'array'],
                'required' => ['sometimes', 'boolean'],
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return collect(SubscriptionPeriod::cases())
            ->mapWithKeys(fn (SubscriptionPeriod $period): array => ['plans.'.$period->value.'.price' => $period->label()])
            ->all();
    }
}
