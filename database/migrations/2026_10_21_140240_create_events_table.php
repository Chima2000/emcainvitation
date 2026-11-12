<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invitation_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('type');
            $table->string('status')->default('draft');
            $table->string('host_name');
            $table->string('partner_name')->nullable();
            $table->string('headline')->nullable();
            $table->text('message')->nullable();
            $table->string('venue');
            $table->dateTime('event_date');
            $table->string('dress_code')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();

            $table->index(['status', 'is_public', 'event_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
