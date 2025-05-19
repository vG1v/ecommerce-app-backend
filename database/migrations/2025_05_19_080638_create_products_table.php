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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('short_description')->nullable();
            $table->text('description');
            
            // Pricing
            $table->decimal('price', 10, 2);
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->boolean('on_sale')->default(false);
            
            // Inventory
            $table->string('sku')->unique();
            $table->integer('stock_quantity')->default(0);
            $table->integer('low_stock_threshold')->default(5);
            $table->enum('stock_status', ['in_stock', 'out_of_stock', 'backorder'])->default('in_stock');
            $table->boolean('manage_stock')->default(true);
            
            // Product attributes
            $table->string('main_image_path')->nullable();
            $table->decimal('weight', 8, 2)->nullable(); // in kg
            $table->decimal('length', 8, 2)->nullable(); // in cm
            $table->decimal('width', 8, 2)->nullable();  // in cm
            $table->decimal('height', 8, 2)->nullable(); // in cm
            
            // Display options
            $table->boolean('featured')->default(false);
            $table->boolean('is_digital')->default(false);
            $table->boolean('is_downloadable')->default(false);
            $table->integer('download_limit')->nullable();
            $table->string('download_file_path')->nullable();
            
            // Status
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->decimal('average_rating', 3, 2)->nullable();
            $table->integer('review_count')->default(0);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
