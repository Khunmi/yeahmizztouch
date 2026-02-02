<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->integer('duration_minutes');
            $table->integer('price_cents'); // Full price in cents
            $table->integer('deposit_cents'); // 40% of price, calculated on save
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->integer('minimum_age')->default(15);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
