<?php

namespace App\Actions;

use App\Models\User;
use App\UserRole;

class RegisterHost
{
    /**
     * @param  array{first_name: string, last_name: string, phone: string, email: string, password: string}  $details  The password is already hashed.
     */
    public function handle(array $details, bool $phoneVerified): User
    {
        $user = User::query()->create([
            ...$details,
            'name' => trim($details['first_name'].' '.$details['last_name']),
            'role' => UserRole::Host,
        ]);

        if ($phoneVerified) {
            $user->forceFill(['phone_verified_at' => now()])->save();
        }

        return $user;
    }
}
