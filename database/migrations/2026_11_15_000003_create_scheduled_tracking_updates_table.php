<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_tracking_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status');
            $table->string('location');
            $table->text('description')->nullable();
            $table->timestamp('scheduled_at');
            $table->timestamp('processed_at')->nullable();
            $table->string('result')->default('pending');
            $table->timestamps();
            $table->index(['processed_at', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_tracking_updates');
    }
};
