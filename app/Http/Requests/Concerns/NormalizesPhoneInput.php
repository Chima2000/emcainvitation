<?php

namespace App\Http\Requests\Concerns;

trait NormalizesPhoneInput
{
    protected function prepareForValidation(): void
    {
        if (! $this->exists('phone')) {
            return;
        }

        $this->merge([
            'phone' => preg_replace('/\s+/', '', (string) $this->input('phone')) ?? '',
        ]);
    }
}
