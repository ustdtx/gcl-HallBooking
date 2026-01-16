<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use Carbon\Carbon;

class CleanExpiredBookings extends Command
{
    protected $signature = 'bookings:clean-expired';
    protected $description = 'Delete expired unpaid bookings and cancel stale pre-bookings';

    public function handle()
    {
        $deleted = Booking::where('status', 'Unpaid')
            ->where('expires_at', '<=', now())
            ->delete();

        $cancelled = Booking::where('status', 'Pre-Booked')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'Cancelled']);

        $this->info("Deleted $deleted unpaid bookings.");
        $this->info("Cancelled $cancelled expired pre-bookings.");
    }
}
