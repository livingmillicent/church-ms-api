<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('church_members', function (Blueprint $table) {
            $table->id();

            // Foreign key to users
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Member details
            $table->enum('member_type', ['regular', 'visitor', 'new_convert', 'inactive']);
            $table->date('baptism_date')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('emergency_contact')->nullable();
            
            // JSON fields
            $table->json('family_members')->nullable();
            $table->json('spiritual_gifts')->nullable();
            $table->json('ministries')->nullable();
            
            $table->string('home_cell_group')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('church_members');
    }
};
