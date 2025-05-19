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
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('store_name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('banner_path')->nullable();
            $table->string('contact_email');
            $table->string('contact_phone')->nullable();
            
            // Address information
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable();
            
            // Business information
            $table->string('business_registration_number')->nullable();
            $table->decimal('commission_rate', 5, 2)->default(10.00); // Default 10% commission
            
            // Status information
            $table->enum('status', ['pending', 'active', 'suspended'])->default('pending');
            $table->boolean('featured')->default(false);
            $table->decimal('average_rating', 3, 2)->nullable();
            $table->unsignedInteger('total_products')->default(0);
            $table->unsignedInteger('total_sales')->default(0);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
