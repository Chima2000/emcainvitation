<x-field name="first_name" label="{{ __('First name') }}">
    <x-input name="first_name" value="{{ old('first_name', $user?->first_name ?? '') }}" autocomplete="given-name" required />
</x-field>

<x-field name="last_name" label="{{ __('Last name') }}">
    <x-input name="last_name" value="{{ old('last_name', $user?->last_name ?? '') }}" autocomplete="family-name" required />
</x-field>

<x-field name="phone" label="{{ __('Phone number') }}">
    <x-input name="phone" type="tel" value="{{ old('phone', $user?->phone ?? '+255') }}" placeholder="+255692586046" inputmode="tel" autocomplete="tel" required />
    <p class="form-text mb-0">{{ __('They sign in with the same number starting with 0. For +255692586046, the sign-in number is 0692586046.') }}</p>
</x-field>

<x-field name="email" label="{{ __('Email') }}">
    <x-input name="email" type="email" value="{{ old('email', $user?->email ?? '') }}" autocomplete="email" required />
</x-field>

<x-field name="password" label="{{ $user ? __('New password') : __('Password') }}">
    <x-input name="password" type="password" autocomplete="new-password" :required="! $user" />
    @if ($user)
        <p class="form-text mb-0">{{ __('Leave the password blank to keep the current one.') }}</p>
    @endif
</x-field>

<x-field name="password_confirmation" label="{{ __('Repeat password') }}">
    <x-input name="password_confirmation" type="password" autocomplete="new-password" :required="! $user" />
</x-field>
