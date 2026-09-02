<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rule_id')->nullable()->constrained('booking_rules')->nullOnDelete();
            $table->dateTime('slot_starts_at');
            $table->string('action');
            $table->text('message')->nullable();
            $table->string('screenshot_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_attempts');
    }
};
