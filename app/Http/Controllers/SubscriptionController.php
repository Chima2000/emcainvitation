<?php

namespace App\Http\Controllers;

use App\Actions\ContinueHostRegistration;
use App\Actions\PurchaseSubscription;
use App\Http\Requests\StoreSubscriptionPaymentRequest;
use App\Http\Requests\StoreSubscriptionRequest;
use App\Models\SubscriptionPlan;
use App\PaymentMethod;
use App\SubscriptionPeriod;
use App\Support\ChosenInvitationTemplate;
use App\TopUpStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $plans = SubscriptionPlan::ordered(onlyAvailable: true);

        return view('subscription.index', [
            'user' => $user,
            'plans' => $plans,
            'selectedPeriod' => old('period', $request->query('period', $plans->first()?->period->value)),
            'methods' => PaymentMethod::cases(),
            'history' => $user->subscriptions()->latest()->limit(8)->get(),
            'pendingPayments' => $user->topUpRequests()
                ->whereNotNull('subscription_period')
                ->where('status', TopUpStatus::Pending)
                ->latest()
                ->get(),
        ]);
    }

    public function store(StoreSubscriptionRequest $request, PurchaseSubscription $purchaseSubscription, ContinueHostRegistration $continueHostRegistration, ChosenInvitationTemplate $chosenInvitationTemplate): RedirectResponse
    {
        $subscription = $purchaseSubscription->handle($request->user(), $request->enum('period', SubscriptionPeriod::class));
        $date = $subscription->ends_at->timezone(config('app.timezone'))->translatedFormat('j F Y');

        if ($request->session()->get(ContinueHostRegistration::PendingSignIn)) {
            return $continueHostRegistration->signIn($request, __('Thank you! Your subscription is active until :date. Sign in with the phone number and password you just chose.', [
                'date' => $date,
            ]));
        }

        return redirect()
            ->to($chosenInvitationTemplate->eventUrl($request) ?? route('dashboard'))
            ->with('status', __('Thank you! Your subscription is active until :date.', [
                'date' => $date,
            ]));
    }

    public function payment(StoreSubscriptionPaymentRequest $request, ContinueHostRegistration $continueHostRegistration): RedirectResponse
    {
        $plan = $request->plan();

        $request->user()->topUpRequests()->create([
            ...$request->safe()->only(['method', 'reference', 'payer_phone']),
            'amount' => $plan->price,
            'subscription_period' => $plan->period,
            'note' => __('Subscription for :period', ['period' => mb_strtolower($plan->period->label())]),
        ]);

        if ($request->session()->get(ContinueHostRegistration::PendingSignIn)) {
            return $continueHostRegistration->signIn($request, __('Thanks! We received your payment details. Sign in with the phone number and password you just chose.'));
        }

        return redirect()
            ->route('subscription.index')
            ->with('status', __('Thanks! We received your payment details. Your account opens as soon as we confirm the payment.'));
    }
}
