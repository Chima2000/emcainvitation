<?php

namespace App\Actions;

use App\Models\User;
use App\UserRole;
use Illuminate\Validation\ValidationException;

class PromoteLeadToHost
{
    /**
     * Turn a trial account into a host after the registration steps. The password is already hashed.
     *
     * @param  array{first_name: string, last_name: string, phone: string, email: string, password: string}  $details
     */
    public function handle(User $user, array $details): User
    {
        if (! $user->isLead()) {
            throw ValidationException::withMessages([
                'phone' => __('This account is already a host.'),
            ]);
        }

        $user->forceFill([
            'first_name' => $details['first_name'],
            'last_name' => $details['last_name'],
            'name' => trim($details['first_name'].' '.$details['last_name']),
            'phone' => $details['phone'],
            'email' => $details['email'],
            'password' => $details['password'],
            'phone_verified_at' => now(),
            'role' => UserRole::Host,
        ])->save();

        return $user;
    }
}
