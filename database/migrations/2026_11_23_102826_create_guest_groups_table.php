<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->unique(['event_id', 'name']);
        });

        Schema::create('guest_group_guest', function (Blueprint $table) {
            $table->foreignId('guest_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->constrained()->cascadeOnDelete();
            $table->primary(['guest_group_id', 'guest_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_group_guest');
        Schema::dropIfExists('guest_groups');
    }
};
