<?php

namespace App\Models;

use App\GuestGender;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'phone', 'email', 'gender', 'notes'])]
class Contact extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gender' => GuestGender::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function remember(User $user, string $name, ?string $phone, ?string $email, ?string $gender): void
    {
        $contact = $phone !== null && $phone !== ''
            ? $user->contacts()->where('phone', $phone)->first()
            : $user->contacts()->where('name', $name)->whereNull('phone')->first();

        $attributes = [
            'name' => $name,
            'phone' => $phone !== '' ? $phone : null,
            'email' => $email !== '' ? $email : null,
            'gender' => $gender !== '' ? $gender : null,
        ];

        if ($contact instanceof self) {
            $contact->fill($attributes)->save();

            return;
        }

        $user->contacts()->create($attributes);
    }
}
