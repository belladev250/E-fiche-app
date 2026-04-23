<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->string('patient_name');
            $table->string('visit_id')->unique();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained();
            $table->string('transaction_ref')->unique();
            $table->decimal('total_amount', 12, 2);
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->string('description');
            $table->integer('qty');
            $table->decimal('price', 12, 2);
            $table->decimal('total', 12, 2);
            $table->string('type');
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained();
            $table->decimal('amount', 12, 2);
            $table->string('method');
            $table->string('status')->default('pending');
            $table->string('transaction_id')->nullable();
            $table->unsignedBigInteger('cashier_id')->nullable();
            $table->timestamps();
        });

        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique();
            $table->json('payload');
            $table->string('status');
            $table->timestamps();
        });
    }
};
