<?php

namespace App\Http\Requests;

use App\SubscriptionPeriod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && ! $this->user()->isSuperAdmin();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'period' => [
                'required',
                Rule::enum(SubscriptionPeriod::class),
                Rule::exists('subscription_plans', 'period')->where(fn (Builder $query) => $query->where('is_active', true)->where('price', '>', 0)),
            ],
        ];
    }
}
