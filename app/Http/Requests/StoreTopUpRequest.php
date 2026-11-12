<?php

namespace App\Http\Requests;

use App\PaymentMethod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTopUpRequest extends FormRequest
{
    protected $errorBag = 'topUp';

    public function authorize(): bool
    {
        return $this->user() !== null && ! $this->user()->isSuperAdmin();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reference' => strtoupper(preg_replace('/\s+/', '', (string) $this->input('reference')) ?? ''),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => [
                'required',
                'integer',
                'min:'.config('emca.wallet.minimum_top_up'),
                'max:'.config('emca.wallet.maximum_top_up'),
            ],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'reference' => [
                'required',
                'string',
                'min:4',
                'max:64',
                'regex:/^[A-Z0-9\-\/]+$/',
                Rule::unique('top_up_requests', 'reference')->where('method', $this->input('method')),
            ],
            'payer_phone' => ['nullable', 'string', 'max:32'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reference.unique' => __('That payment reference was already sent. Each payment can be claimed once.'),
            'reference.regex' => __('Use the transaction code exactly as it appears in the payment message.'),
        ];
    }
}
