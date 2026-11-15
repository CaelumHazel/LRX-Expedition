<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->string('origin_city')->nullable();
            $table->string('destination_city')->nullable();
            $table->text('receiver_address')->nullable();
            $table->string('current_position')->nullable();
            $table->decimal('declared_value', 12, 2)->default(0);
        });

        Schema::table('tracking_logs', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('source')->default('manual');
        });

        Schema::table('shipping_rates', function (Blueprint $table) {
            $table->foreignId('courier_id')
                ->nullable()
                ->constrained('couriers')
                ->nullOnDelete();

            $table->index(
                ['origin_city', 'destination_city', 'cargo_type', 'courier_id'],
                'shipping_rates_route_courier_index'
            );
        });

        Schema::create('tracking_number_sequences', function (Blueprint $table) {
            $table->date('sequence_date')->primary();
            $table->unsignedSmallInteger('last_sequence')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_number_sequences');

        Schema::table('shipping_rates', function (Blueprint $table) {
            $table->dropIndex('shipping_rates_route_courier_index');
            $table->dropConstrainedForeignId('courier_id');
        });

        Schema::table('tracking_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn('source');
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn([
                'origin_city',
                'destination_city',
                'receiver_address',
                'current_position',
                'declared_value',
            ]);
        });
    }
};
