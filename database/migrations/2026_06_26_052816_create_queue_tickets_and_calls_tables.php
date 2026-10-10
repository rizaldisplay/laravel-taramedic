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
        Schema::create('queue_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->foreignId('antrean_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('counter_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ticket_number', 20);
            $table->unsignedInteger('sequence');
            $table->date('queue_date')->index();
            $table->string('status', 20)->default('waiting')->index();
            $table->string('source', 20);
            $table->string('priority', 20)->default('normal')->index();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('handled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at')->useCurrent();
            $table->timestamp('called_at')->nullable();
            $table->timestamp('serving_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedTinyInteger('recall_count')->default(0);
            $table->unsignedTinyInteger('skip_count')->default(0);
            $table->timestamps();

            $table->unique(['service_id', 'queue_date', 'sequence']);
            $table->index(['service_id', 'queue_date', 'status']);
            $table->index(['antrean_id', 'queue_date', 'status']);
            $table->index(['queue_date', 'status']);
            $table->index(['counter_id', 'status']);
            $table->index(
                ['counter_id', 'queue_date', 'status'],
                'queue_tickets_counter_date_status_index',
            );
            $table->index(
                ['queue_date', 'status', 'priority', 'skip_count', 'sequence'],
                'queue_tickets_call_order_index',
            );
        });

        Schema::create('queue_calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('queue_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('counter_id')->constrained()->restrictOnDelete();
            $table->foreignId('antrean_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('call_type', 20);
            $table->timestamp('called_at');
            $table->timestamps();

            $table->index(['antrean_id', 'called_at']);
            $table->index(['queue_ticket_id', 'called_at']);
            $table->index(['counter_id', 'called_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queue_calls');
        Schema::dropIfExists('queue_tickets');
    }
};
