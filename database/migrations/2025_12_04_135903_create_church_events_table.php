<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('church_events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('event_type', [
                'sunday_service',
                'midweek_service',
                'prayer_meeting',
                'youth_service',
                'children_service',
                'conference',
                'outreach',
                'special_event'
            ]);
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->string('location');
            $table->json('participants')->nullable();
            $table->json('speakers')->nullable();
            $table->decimal('budget', 15, 2)->nullable();
            $table->integer('max_attendees')->nullable();
            $table->boolean('requires_registration')->default(false);
            $table->enum('status', ['scheduled', 'ongoing', 'completed', 'cancelled'])->default('scheduled');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('church_events');
    }
};
