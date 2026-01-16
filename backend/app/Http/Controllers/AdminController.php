<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use App\Helpers\SimpleLogger;

class AdminController extends Controller
{
    // List all admins with pagination and blade view
    public function index(Request $request)
    {

        $perPageOptions = [25, 50, 100, 200, 'all'];
        $perPage = $request->input('per_page', 25);
        if (!in_array($perPage, $perPageOptions)) {
            $perPage = 25;
        }
        $admins = $perPage === 'all' ? Admin::orderBy('id', 'desc')->get() : Admin::orderBy('id', 'desc')->paginate($perPage);
        return view('admin.admins', compact('admins', 'perPage', 'perPageOptions'));
    }

    // Show update form
    public function edit($id)
    {
        $admin = \Auth::guard('admin')->user();
        if (!$admin || $admin->role !== 'admin') {
            abort(403, 'Forbidden');
        }
        $editAdmin = Admin::findOrFail($id);
        return view('admin.admin_edit', compact('editAdmin'));
    }

    // Update admin
    public function update(Request $request, $id)
    {
        $admin = \Auth::guard('admin')->user();
        if (!$admin || $admin->role !== 'admin') {
            abort(403, 'Forbidden');
        }
        $editAdmin = Admin::findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:admins,email,' . $editAdmin->id,
            'password' => 'nullable|string',
            'role' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
        ]);
        $editAdmin->name = $data['name'];
        $editAdmin->email = $data['email'];
        if (!empty($data['password'])) {
            $editAdmin->password = bcrypt($data['password']);
        }
        $editAdmin->role = $data['role'];
        $editAdmin->phone = $data['phone'] ?? null;
        $editAdmin->save();
        SimpleLogger::log('admin_update', $admin->name.' updated admin: '.$editAdmin->name.' ('.$editAdmin->email.')');
        return redirect()->route('admin.admins')->with('success', 'Admin updated successfully!');
    }

    // Delete admin
    public function destroy($id)
    {
        $admin = \Auth::guard('admin')->user();
        if (!$admin || $admin->role !== 'admin') {
            abort(403, 'Forbidden');
        }
        $deleteAdmin = Admin::findOrFail($id);
        if ($deleteAdmin->id == $admin->id) {
            return redirect()->back()->withErrors(['error' => 'You cannot delete yourself.']);
        }
        $deleteAdmin->delete();
        SimpleLogger::log('admin_delete', $admin->name.' deleted admin: '.$deleteAdmin->name.' ('.$deleteAdmin->email.')');
        return redirect()->back()->with('success', 'Admin deleted successfully!');
    }

    // Create a new admin
    public function store(Request $request)
    {
        $admin = \Auth::guard('admin')->user();
        if (!$admin || $admin->role !== 'admin') {
            return redirect()->back()->withErrors(['error' => 'Forbidden']);
        }
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:admins,email',
            'password' => 'required|string',
            'role' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
        ]);
        $created = Admin::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'role' => $data['role'],
            'phone' => $data['phone'] ?? null,
        ]);
        SimpleLogger::log('admin_add', $admin->name.' added admin: '.$data['name'].' ('.$data['email'].')');
        return redirect()->back()->with('success', 'Admin added successfully!');
    }
}
