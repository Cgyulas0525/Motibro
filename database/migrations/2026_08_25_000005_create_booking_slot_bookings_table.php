<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_slot_bookings', function (Blueprint $table) {
            $table->id();
            $table->dateTime('slot_starts_at')->unique();
            $table->foreignId('rule_id')->nullable()->constrained('booking_rules')->nullOnDelete();
            $table->foreignId('booking_run_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->dateTime('booked_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_slot_bookings');
    }
};
