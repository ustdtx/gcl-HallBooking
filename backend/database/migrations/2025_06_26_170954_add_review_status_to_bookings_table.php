<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'Review' to the ENUM list
        DB::statement("ALTER TABLE bookings MODIFY status ENUM('Unpaid', 'Pre-Booked', 'Confirmed', 'Cancelled', 'Unavailable', 'Review') DEFAULT 'Unpaid'");
    }

    public function down(): void
    {
        // Remove 'Review' from the ENUM list
        DB::statement("ALTER TABLE bookings MODIFY status ENUM('Unpaid', 'Pre-Booked', 'Confirmed', 'Cancelled', 'Unavailable') DEFAULT 'Unpaid'");
    }
};

