<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SimpleLog;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $query = SimpleLog::query();
        if ($request->filled('admin_name')) {
            $query->where('description', 'like', '%' . $request->admin_name . '%');
        }
        $logs = $query->orderByDesc('created_at')->paginate(30);
        return view('admin.logs', compact('logs'));
    }
}
