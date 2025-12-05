<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('church_members')->onDelete('cascade');
            $table->foreignId('recorded_by')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->enum('contribution_type', [
                'tithe','offering','donation','building_fund','missionary','thanksgiving','other'
            ]);
            $table->date('contribution_date');
            $table->enum('payment_method', ['cash','mobile_money','bank_transfer','check']);
            $table->string('reference_number')->nullable()->unique();
            $table->text('notes')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->enum('recurring_frequency', ['weekly','monthly','quarterly','yearly'])->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contributions');
    }
};
