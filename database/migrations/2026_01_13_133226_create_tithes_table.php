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
        Schema::create('tithes', function (Blueprint $table) {
            $table->id(); // PostgreSQL compatible
            $table->string('transaction_id')->unique();
            $table->string('account_name');
            $table->decimal('amount', 10, 2);
            $table->string('payment_method');
            $table->date('date');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users');
        }
    );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::dropIfExists('tithes');
    }
};
