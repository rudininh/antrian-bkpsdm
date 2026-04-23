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
        Schema::table('queues', function (Blueprint $table) {
            $table->dropUnique('queues_ticket_number_unique');
            $table->unique(['queue_date', 'ticket_number'], 'queues_queue_date_ticket_number_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('queues', function (Blueprint $table) {
            $table->dropUnique('queues_queue_date_ticket_number_unique');
            $table->unique('ticket_number', 'queues_ticket_number_unique');
        });
    }
};
