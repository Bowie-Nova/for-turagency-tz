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
        Schema::create('tours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('operator');
            $table->string('title');
            $table->string('hotel_name');
            $table->integer('hotel_category')->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('days');
            $table->date('departure_date');
            $table->integer('available_seats')->nullable();
            $table->decimal('hotel_rating', 3, 1)->nullable();
            $table->json('inclusions')->nullable();
            $table->string('url')->nullable();
            $table->decimal('popularity_score', 5, 2)->default(0);
            $table->timestamps();
            $table->index(['lead_id', 'operator']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tours');
    }
};
