<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hall extends Model
{
    protected $fillable = [
        'name',
        'description',
        'capacity',
        'charges',
        'images',
        'policy_pdf',
        'policy_content',
        'is_active',
    ];

    protected $casts = [
        'charges' => 'array',
        'images' => 'array',
        'policy_content' => 'array',
        'is_active' => 'boolean',
    ];
}
