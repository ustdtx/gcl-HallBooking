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
    Schema::create('bookings', function (Blueprint $table) {
        $table->id();
        $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
        $table->foreignId('hall_id')->constrained('halls')->onDelete('cascade');
        $table->date('booking_date');
        $table->enum('shift', ['FN', 'AN', 'FD']);
        $table->enum('status', ['Unpaid', 'Pre-Booked', 'Confirmed', 'Cancelled'])->default('Unpaid');
        $table->enum('statusUpdater',['Auto','Admin'])->default('Auto');
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
