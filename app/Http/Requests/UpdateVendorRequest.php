<?php

namespace App\Http\Requests;

use App\Models\Vendor;
use App\VendorStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class UpdateVendorRequest extends StoreVendorRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->isStatusChange()) {
            return;
        }

        parent::prepareForValidation();

        $vendor = $this->route('vendor');

        if ($vendor instanceof Vendor) {
            $this->errorBag = 'vendor-'.$vendor->getKey();
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if ($this->isStatusChange()) {
            return [
                'status' => ['required', Rule::enum(VendorStatus::class)],
            ];
        }

        return [
            'name' => ['required', 'string', 'max:120'],
            'category' => ['required', Rule::in(self::categories())],
            'phone' => ['nullable', 'string', 'max:30'],
            'price' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'status' => ['required', Rule::enum(VendorStatus::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $vendor = $this->route('vendor');

        if ($vendor instanceof Vendor && ! $this->isStatusChange()) {
            session()->flash('vendor_edit', $vendor->getKey());
        }

        parent::failedValidation($validator);
    }

    private function isStatusChange(): bool
    {
        return ! $this->exists('name') && $this->exists('status');
    }
}
