<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MemberSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('members')->insert([
            [
                'name' => 'John Doe',
                'club_account' => 'GCL1234',
                'email' => 'john@example.com',
                'phone' => '01710000000',
                'address' => 'Banani, Dhaka',
                'date_joined' => Carbon::create('2021', '01', '15'),
                'otp' => null,
                'otp_created' => null,
                'otp_expiry' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Jane Smith',
                'club_account' => 'GCL5678',
                'email' => 'jane@example.com',
                'phone' => '01810000000',
                'address' => null,
                'date_joined' => null,
                'otp' => null,
                'otp_created' => null,
                'otp_expiry' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
