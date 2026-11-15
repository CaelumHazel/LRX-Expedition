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
        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            
            $table->string('origin_city');
            $table->string('destination_city');

            $table->string('cargo_type');

            $table->decimal('price_per_kg', 12, 2);

            $table->integer('estimated_days');

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index([
                'origin_city',
                'destination_city',
                'cargo_type',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_rates');
    }
};
