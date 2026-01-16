<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // For MySQL, you need to use a raw statement to modify ENUMs
        DB::statement("ALTER TABLE bookings MODIFY status ENUM('Unpaid', 'Pre-Booked', 'Confirmed', 'Cancelled', 'Unavailable') DEFAULT 'Unpaid'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE bookings MODIFY status ENUM('Unpaid', 'Pre-Booked', 'Confirmed', 'Cancelled') DEFAULT 'Unpaid'");
    }
};