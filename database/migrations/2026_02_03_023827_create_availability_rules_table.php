<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('availability_rules', function (Blueprint $table) {
            $table->id();

            // Matches your seeder
            $table->unsignedTinyInteger('day_of_week'); // 0=Sunday ... 6=Saturday
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->boolean('is_available')->default(true);

            $table->timestamps();

            $table->unique(['day_of_week']); // one row per weekday
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('availability_rules');
    }
};
