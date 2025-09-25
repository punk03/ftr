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
        Schema::create('medals', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('group_id');
            $table->integer('quantity');
            $table->decimal('price_per_medal', 8, 2);
            $table->decimal('total_amount', 10, 2);
            $table->enum('status', ['ordered', 'produced', 'delivered'])->default('ordered');
            $table->text('participants_list')->nullable();
            $table->timestamps();
            
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medals');
    }
};
