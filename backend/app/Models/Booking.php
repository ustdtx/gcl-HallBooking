<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'member_id',
        'hall_id',
        'booking_date',
        'shift',
        'status',
        'statusUpdater',
        'expires_at',
    // ...other fields...
];

protected $casts = [
    'expires_at' => 'datetime',
    // ...other casts...
];


    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function hall()
    {
        return $this->belongsTo(Hall::class);
    }
}
