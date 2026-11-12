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
        Schema::create('committees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->foreignId('leader_guest_id')->nullable()->constrained('guests')->nullOnDelete();
            $table->timestamps();

            $table->unique(['event_id', 'name']);
        });

        Schema::create('committee_guest', function (Blueprint $table) {
            $table->foreignId('committee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->constrained()->cascadeOnDelete();

            $table->primary(['committee_id', 'guest_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('committee_guest');
        Schema::dropIfExists('committees');
    }
};
