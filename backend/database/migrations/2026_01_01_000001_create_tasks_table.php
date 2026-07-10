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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();

            // Tasks belong to the authenticated user who created them.
            // (Not in the original spec's table, but required once login/register was added.)
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('title');
            $table->date('due_date');
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->enum('status', ['pending', 'in_progress', 'done'])->default('pending');

            $table->timestamps();

            // A user cannot have two tasks with the same title on the same due date.
            $table->unique(['user_id', 'title', 'due_date'], 'unique_title_per_due_date_per_user');

            // Speeds up the report + list endpoints.
            $table->index(['user_id', 'due_date']);
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
