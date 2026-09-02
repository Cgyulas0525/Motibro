<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_rules', function (Blueprint $table) {
            $table->id();
            $table->string('label')->nullable();
            $table->unsignedTinyInteger('weekday');
            $table->time('time');
            $table->boolean('enabled')->default(true);
            $table->boolean('waitlist_ok')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['weekday', 'time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_rules');
    }
};
