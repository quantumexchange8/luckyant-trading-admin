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
        Schema::create('mt5_delete_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('trading_account_id');
            $table->unsignedBigInteger('meta_login');
            $table->string('type')->default('auto');
            $table->timestamp('account_created_at')->nullable();
            $table->decimal('account_balance', 13, 2)->nullable();
            $table->string('remarks')->nullable();
            $table->unsignedBigInteger('handle_by')->nullable();
            $table->timestamps();

            $table->foreign('handle_by')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mt5_delete_log');
    }
};
