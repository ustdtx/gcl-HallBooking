<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Booking;
use Carbon\Carbon;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = ['Unpaid', 'Pre-Booked', 'Confirmed', 'Cancelled', 'Unavailable'];
        $shifts = ['FN', 'AN', 'FD'];

        $usedSlots = []; // [ '2025-07-14_FN' => true ]

        $bookingsToCreate = 10;
        $created = 0;

        while ($created < $bookingsToCreate) {
            $day = rand(1, 28);
            $shift = $shifts[array_rand($shifts)];
            $date = Carbon::create(2025, 7, $day)->format('Y-m-d');
            $slotKey = $date . '_' . $shift;

            if (isset($usedSlots[$slotKey])) {
                continue; // skip conflicting booking
            }

            $usedSlots[$slotKey] = true;

            Booking::create([
                'member_id'    => 1,
                'hall_id'      => 1,
                'booking_date' => $date,
                'shift'        => $shift,
                'status'       => $statuses[array_rand($statuses)],
                'expires_at'   => now()->addMinutes(15), // only relevant for Unpaid
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            $created++;
        }
    }
}
