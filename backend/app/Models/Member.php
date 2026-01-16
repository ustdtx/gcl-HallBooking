<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class Member extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'club_account',
        'email',
        'phone',
        'address',
        'date_joined',
        'otp',
        'otp_created',
        'otp_expiry',
        'profile_picture',
    ];
    protected $hidden = [
        'otp',
    ];
    
}
