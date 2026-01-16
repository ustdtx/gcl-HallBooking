<?php

// app/Models/Payment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'booking_id', 'type', 'amount', 'tran_id', 'status',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
