<?php

use App\Enums\Finance\EntryPosition;
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
        Schema::create('transaction_entry_configs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('transaction_type_id')->index();
            $table->string('config_key');
            $table->string('ui_label');
            $table->enum('position', EntryPosition::values());
            $table->string('account_type_filter')->nullable();
            $table->uuid('account_id')->nullable()->index();
            $table->boolean('is_required')->default(true);
            $table->timestamps();

            $table->unique(['transaction_type_id', 'config_key']);
        });

        Schema::table('transaction_entry_configs', function (Blueprint $table) {
            $table->foreign('transaction_type_id')
                ->references('id')
                ->on('transaction_types')
                ->cascadeOnDelete();

            $table->foreign('account_id')
                ->references('id')
                ->on('chart_of_accounts')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_entry_configs');
    }
};
