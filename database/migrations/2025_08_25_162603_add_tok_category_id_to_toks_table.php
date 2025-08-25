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
        Schema::table('toks', function (Blueprint $table) {
            $table->foreignId('tok_category_id')
                ->nullable()
                ->constrained('tok_categories')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('toks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tok_category_id');
        });
    }
};
