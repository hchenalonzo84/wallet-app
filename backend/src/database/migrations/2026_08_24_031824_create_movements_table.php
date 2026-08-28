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
        Schema::create('movements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('pocket_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('type', 30);

            $table->decimal('amount', 18, 2);

            $table->string('description', 500)
                ->nullable();

            $table->timestampTz('occurred_at');

            $table->uuid('transfer_group_id')
                ->nullable();

            $table->timestamps();

            $table->index([
                'user_id',
                'occurred_at',
            ]);

            $table->index([
                'pocket_id',
                'occurred_at',
            ]);

            $table->index('type');

            $table->index('transfer_group_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movements');
    }
};