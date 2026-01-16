<?php
	namespace App\Helpers;

use App\Models\SimpleLog;

class SimpleLogger
{
    public static function log($name, $description)
    {
        SimpleLog::create([
            'name' => $name,
            'description' => $description,
            'created_at' => now(),
        ]);
    }
}
