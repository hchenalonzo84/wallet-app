<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movements', function (Blueprint $table) {
            $table->foreignId('recurring_payment_id')
                ->nullable()
                ->after('transfer_group_id')
                ->constrained('recurring_payments')
                ->nullOnDelete();

            $table->date('scheduled_for')
                ->nullable()
                ->after('recurring_payment_id');

            $table->unique([
                'recurring_payment_id',
                'scheduled_for',
            ]);

            $table->index('scheduled_for');
        });
    }

    public function down(): void
    {
        Schema::table('movements', function (Blueprint $table) {
            $table->dropUnique([
                'recurring_payment_id',
                'scheduled_for',
            ]);

            $table->dropIndex([
                'scheduled_for',
            ]);

            $table->dropForeign([
                'recurring_payment_id',
            ]);

            $table->dropColumn([
                'recurring_payment_id',
                'scheduled_for',
            ]);
        });
    }
};