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
        Schema::create('upcoming_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('banner_id')->nullable()->constrained('assets')->nullOnDelete('cascade');
            $table->string('title');
            $table->string('description')->nullable(); // Optional description of the resource
            $table->string('venue')->nullable(); // e.g., 'address', 'zoom', 'youtube'
            $table->string('location')->default('Online SDSSN'); // e.g., 'address', 'zoom', 'youtube'
            $table->string('registration_link')->nullable(); // e.g., 'google meet', 'zoom', 'youtube'
            // category
            $table->time('start_time')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->time('end_time')->nullable();
            $table->string('category')->nullable()->default('general'); // Category of e.g., 'general', 'retreat', 'seminar'
            $table->boolean('status')->default(1);
            $table->json('facilitators')->nullable();
            $table->json('speakers')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone_number')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete(); // User who created the resource
            $table->timestamps();
            $table->softDeletes(); // Soft delete for the resource
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('upcoming_events');
    }
};
