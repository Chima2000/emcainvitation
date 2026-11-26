<?php

use App\Actions\RememberGuestsAsContacts;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(RememberGuestsAsContacts::class)->handle();
    }

    public function down(): void
    {
        //
    }
};
