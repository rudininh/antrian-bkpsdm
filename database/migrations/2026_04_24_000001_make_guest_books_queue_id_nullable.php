<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('guest_books', function (Blueprint $table) {
            $table->dropForeign(['queue_id']);
            $table->dropUnique('guest_books_queue_id_unique');
        });

        DB::statement('ALTER TABLE guest_books MODIFY queue_id BIGINT UNSIGNED NULL');

        Schema::table('guest_books', function (Blueprint $table) {
            $table->foreign('queue_id')->references('id')->on('queues')->nullOnDelete();
            $table->unique('queue_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guest_books', function (Blueprint $table) {
            $table->dropForeign(['queue_id']);
            $table->dropUnique('guest_books_queue_id_unique');
        });

        DB::table('guest_books')->whereNull('queue_id')->delete();

        DB::statement('ALTER TABLE guest_books MODIFY queue_id BIGINT UNSIGNED NOT NULL');

        Schema::table('guest_books', function (Blueprint $table) {
            $table->foreign('queue_id')->references('id')->on('queues')->cascadeOnDelete();
            $table->unique('queue_id');
        });
    }
};
