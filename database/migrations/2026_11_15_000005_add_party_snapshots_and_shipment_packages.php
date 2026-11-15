<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->string('sender_name')->nullable();
            $table->string('sender_phone', 20)->nullable();
            $table->string('receiver_name')->nullable();
            $table->string('receiver_phone', 20)->nullable();
            $table->unsignedInteger('estimated_distance_km')->nullable();
        });

        Schema::create('shipment_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->index(['shipment_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_packages');

        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn([
                'sender_name',
                'sender_phone',
                'receiver_name',
                'receiver_phone',
                'estimated_distance_km',
            ]);
        });
    }
};
