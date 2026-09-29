<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_blackouts', function (Blueprint $table) {
            $table->id();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['starts_on', 'ends_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_blackouts');
    }
};
