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
        Schema::table('agencies', function (Blueprint $table) {
            // Correct address field type from text to string
            $table->string('address', 255)->nullable()->change();

            // Rename commission_rate to default_markup_percent and set default to 15.00
            $table->renameColumn('commission_rate', 'default_markup_percent');
            $table->decimal('default_markup_percent', 5, 2)->default(15.00)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            // Revert address field type
            $table->text('address')->nullable()->change();

            // Rename default_markup_percent back to commission_rate and set default to 10.00
            $table->renameColumn('default_markup_percent', 'commission_rate');
            $table->decimal('commission_rate', 5, 2)->default(10.00)->change();
        });
    }
};
