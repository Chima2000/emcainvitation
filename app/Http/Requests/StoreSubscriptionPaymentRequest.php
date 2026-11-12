<?php

namespace App\Http\Requests;

use App\Models\SubscriptionPlan;
use App\PaymentMethod;
use App\SubscriptionPeriod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class StoreSubscriptionPaymentRequest extends StoreSubscriptionRequest
{
    protected $errorBag = 'subscriptionPayment';

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
            ...parent::rules(),
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

    public function plan(): SubscriptionPlan
    {
        return SubscriptionPlan::query()
            ->available()
            ->where('period', $this->enum('period', SubscriptionPeriod::class))
            ->firstOrFail();
    }
}
