<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('service_rates', function (Blueprint $table) {
            $table->id();
            $table->string('item')->unique();
            $table->unsignedInteger('amount');
            $table->timestamps();
        });

        DB::table('service_rates')->insert(collect([
            'card' => 1000,
            'whatsapp' => 300,
            'sms' => 90,
            'reminder' => 150,
            'thank_you' => 100,
        ])->map(fn (int $amount, string $item): array => [
            'item' => $item,
            'amount' => $amount,
            'created_at' => now(),
            'updated_at' => now(),
        ])->values()->all());
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_rates');
    }
};
