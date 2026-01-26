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
       Schema::create('children_ministries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('age_from');
            $table->integer('age_to');
            $table->string('class_teacher');
            $table->integer('total_children')->default(0);
            $table->text('description')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::dropIfExists('children_ministries');
    }
};
