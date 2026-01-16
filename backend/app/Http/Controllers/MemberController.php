<?php
namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\Member;
use App\Helpers\SimpleLogger;
use Illuminate\Support\Facades\Auth;

class MemberController extends Controller
{
    // List members with filters and pagination
    public function index(Request $request)
    {
        $admin = \Auth::guard('admin')->user();
        // Allow all authenticated users to view the page, but only admins can add/edit/delete
        $filters = [
            'name' => $request->input('name', ''),
            'email' => $request->input('email', ''),
            'club_account' => $request->input('club_account', ''),
            'per_page' => $request->input('per_page', 25),
        ];
        $perPageOptions = [25, 50, 100, 200, 'all'];
        $query = Member::query();
        if ($filters['name']) $query->where('name', 'like', '%'.$filters['name'].'%');
        if ($filters['email']) $query->where('email', 'like', '%'.$filters['email'].'%');
        if ($filters['club_account']) $query->where('club_account', 'like', '%'.$filters['club_account'].'%');
        $members = $filters['per_page'] === 'all'
            ? $query->orderBy('id', 'desc')->get()
            : $query->orderBy('id', 'desc')->paginate($filters['per_page']);
        return view('admin.members', compact('members', 'filters', 'perPageOptions'));
    }

    // Store new member
    public function store(Request $request)
    {
        $admin = \Auth::guard('admin')->user();
        if (!$admin || $admin->role !== 'admin') {
            return back()->withInput()->withErrors(['error' => 'Failed to add member. Unauthorized action.']);
        }
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'club_account' => 'required|string|max:255',
            'email' => 'required|email|unique:members,email',
            'phone' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'date_joined' => 'nullable|date',
        ]);
        if (empty($data['date_joined'])) {
            $data['date_joined'] = now()->toDateString();
        }
        $member = Member::create($data);
        SimpleLogger::log('member_add', $admin->name.' added member: '.$data['name'].' ('.$data['email'].')');
        return redirect()->back()->with('success', 'Member added successfully!');
    }

    // Show edit form
    public function edit($id)
    {
        $admin = \Auth::guard('admin')->user();
        if (!$admin || $admin->role !== 'admin') {
            return back()->withInput()->withErrors(['error' => 'Failed. Unauthorized action.']);
        }
        $editMember = Member::findOrFail($id);
        return view('admin.member_edit', compact('editMember'));
    }

    // Update member
    public function update(Request $request, $id)
    {
        $admin = \Auth::guard('admin')->user();
        if (!$admin || $admin->role !== 'admin') {
            return back()->withInput()->withErrors(['error' => 'Failed to update member. Unauthorized action.']);
        }
        $editMember = Member::findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'club_account' => 'required|string|max:255',
            'email' => 'required|email|unique:members,email,'.$editMember->id,
            'phone' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'date_joined' => 'nullable|date',
        ]);
        if (empty($data['date_joined'])) {
            $data['date_joined'] = $editMember->date_joined ?? now()->toDateString();
        }
        $editMember->update($data);
        SimpleLogger::log('member_update', $admin->name.' updated member: '.$editMember->name.' ('.$editMember->email.')');
        return redirect()->route('admin.members')->with('success', 'Member updated successfully!');
    }

    // Delete member
    public function destroy($id)
    {
        $admin = \Auth::guard('admin')->user();
        if (!$admin || $admin->role !== 'admin') {
            return back()->withInput()->withErrors(['error' => 'Failed to delete member. Unauthorized action.']);
        }
        $deleteMember = Member::findOrFail($id);
        $deleteMember->delete();
        SimpleLogger::log('member_delete', $admin->name.' deleted member: '.$deleteMember->name.' ('.$deleteMember->email.')');
        return redirect()->back()->with('success', 'Member deleted successfully!');
    }

    // Step 1: Handle CSV upload and show mapping UI
    public function importCsv(Request $request)
    {
        $admin = \Auth::guard('admin')->user();
        if (!$admin || $admin->role !== 'admin') {
            return back()->withErrors(['error' => 'Unauthorized action.']);
        }
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);
        $file = $request->file('csv_file');
        $path = $file->storeAs('csv_imports', uniqid('members_').'.csv', 'local');
        $fullPath = storage_path('app/'.$path);
        $handle = fopen($fullPath, 'r');
        $csv_headers = fgetcsv($handle);
        $csv_preview = [];
        for ($i = 0; $i < 5 && ($row = fgetcsv($handle)); $i++) {
            $csv_preview[] = $row;
        }
        fclose($handle);
        // Suggest mapping by header name
        $fields = [
            'name' => 'Name',
            'club_account' => 'Club Account',
            'email' => 'Email',
            'phone' => 'Phone',
            'address' => 'Address',
            'date_joined' => 'Date Joined',
        ];
        $suggested = [];
        foreach ($fields as $field => $label) {
            foreach ($csv_headers as $i => $header) {
                if (strtolower(trim($header)) === strtolower(str_replace('_', ' ', $field))) {
                    $suggested[$field] = $i;
                    break;
                }
            }
        }
        return view('admin.members_import_map', [
            'csv_headers' => $csv_headers,
            'csv_preview' => $csv_preview,
            'fields' => $fields,
            'suggested' => $suggested,
            'csv_path' => $path,
        ]);
    }

    // Step 2: Process mapping and import members
    public function importCsvMap(Request $request)
    {
        $admin = \Auth::guard('admin')->user();
        if (!$admin || $admin->role !== 'admin') {
            return back()->withErrors(['error' => 'Unauthorized action.']);
        }
        $request->validate([
            'csv_path' => 'required',
            'mapping' => 'required|array',
        ]);
        $fields = [
            'name', 'club_account', 'email', 'phone', 'address', 'date_joined'
        ];
        $fullPath = storage_path('app/'.$request->csv_path);
        if (!file_exists($fullPath)) {
            return back()->withErrors(['error' => 'CSV file not found.']);
        }
        $handle = fopen($fullPath, 'r');
        $csv_headers = fgetcsv($handle);
        $imported = 0;
        $skipped = 0;
        $today = now()->toDateString();
        while (($row = fgetcsv($handle)) !== false) {
            $data = [];
            foreach ($fields as $field) {
                $col = $request->mapping[$field] ?? null;
                $data[$field] = ($col !== null && isset($row[$col])) ? trim($row[$col]) : null;
            }
            // If any of club_account, email, phone is missing, skip and log
            if (empty($data['club_account']) || empty($data['email']) || empty($data['phone'])) {
                $skipped++;
                \App\Helpers\SimpleLogger::log('member_import_drop', $admin->name.' dropped row (missing required fields): '.json_encode($data));
                continue;
            }
            // If date_joined missing, use today
            if (empty($data['date_joined'])) {
                $data['date_joined'] = $today;
            }
            // Validate email uniqueness
            if (\App\Models\Member::where('email', $data['email'])->exists()) {
                $skipped++;
                \App\Helpers\SimpleLogger::log('member_import_drop', $admin->name.' dropped row (duplicate email): '.json_encode($data));
                continue;
            }
            // Insert and log
            \App\Models\Member::create($data);
            $imported++;
            \App\Helpers\SimpleLogger::log('member_import_add', $admin->name.' imported member: '.json_encode($data));
        }
        fclose($handle);
        // Optionally delete the CSV file after import
        @unlink($fullPath);
        return redirect()->route('admin.members')->with('success', "Imported $imported members. Skipped $skipped entries.");
    }
}