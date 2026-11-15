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
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_number')->unique();

            $table->foreignId('sender_id')
                ->constrained('customers')
                ->restrictOnDelete();

            $table->foreignId('receiver_id')
                ->constrained('customers')
                ->restrictOnDelete();

            $table->foreignId('courier_id')
                ->nullable()
                ->constrained('couriers')
                ->nullOnDelete();

            $table->foreignId('warehouse_id')
                ->nullable()
                ->constrained('warehouses')
                ->nullOnDelete();

            $table->string('cargo_type');

            $table->decimal('total_weight', 10, 2);

            $table->decimal('shipping_cost', 12, 2)->default(0);

            $table->date('estimated_delivery_date')->nullable();

            $table->string('status')->default('pending');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('tracking_number');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
