<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('peach_subscription_id')->nullable();
            $table->string('plan_id');
            $table->string('status');
            $table->string('payment_method_token')->nullable();
            
            // Billing information
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3);
            $table->string('billing_cycle', 20); // daily, weekly, monthly, yearly
            
            // Dates
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('next_billing_date')->nullable();
            
            // Payment status
            $table->timestamp('last_payment_date')->nullable();
            $table->string('last_payment_status')->nullable();
            $table->integer('payment_attempts')->default(0);
            
            // Metadata
            $table->json('metadata')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['user_id', 'status']);
            $table->index('next_billing_date');
            $table->index('peach_subscription_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('subscriptions');
    }
};
