<?php

namespace App\Http\Requests\Admin;

use App\ServiceItem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePricingRequest extends FormRequest
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
        return collect(ServiceItem::cases())
            ->mapWithKeys(fn (ServiceItem $item): array => [
                'rates.'.$item->value => ['required', 'integer', 'min:0', 'max:1000000'],
            ])
            ->prepend(['required', 'array'], 'rates')
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return collect(ServiceItem::cases())
            ->mapWithKeys(fn (ServiceItem $item): array => ['rates.'.$item->value => $item->label()])
            ->all();
    }
}
