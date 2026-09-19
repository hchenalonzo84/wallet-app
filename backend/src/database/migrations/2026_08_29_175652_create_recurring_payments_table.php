<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('pocket_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('name', 100);

            $table->string('description', 500)
                ->nullable();

            $table->decimal('amount', 18, 2);

            $table->string('frequency', 20)
                ->default('monthly');

            $table->smallInteger('billing_day');

            $table->date('starts_on');

            $table->date('next_due_on')
                ->nullable();

            $table->date('ends_on')
                ->nullable();

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();

            $table->index([
                'user_id',
                'is_active',
            ]);

            $table->index([
                'is_active',
                'next_due_on',
            ]);

            $table->index([
                'pocket_id',
                'is_active',
            ]);
        });

        /*
         * Protección adicional a nivel de PostgreSQL.
         *
         * Aunque Laravel también validará estos valores,
         * evitamos que datos inválidos puedan entrar
         * directamente por SQL u otro proceso.
         */
        DB::statement(
            '
            ALTER TABLE recurring_payments
            ADD CONSTRAINT recurring_payments_amount_positive
            CHECK (amount > 0)
            '
        );

        DB::statement(
            '
            ALTER TABLE recurring_payments
            ADD CONSTRAINT recurring_payments_billing_day_valid
            CHECK (billing_day BETWEEN 1 AND 31)
            '
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_payments');
    }
};