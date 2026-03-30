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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('departure_city');
            $table->string('destination_country');
            $table->integer('hotel_category')->nullable();
            $table->date('departure_from');
            $table->date('departure_to');
            $table->integer('nights_from');
            $table->integer('nights_to');
            $table->integer('adults');
            $table->integer('children')->default(0);
            $table->json('preferences')->nullable();
            $table->string('status')->default('new');
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
