<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Hall;
use Illuminate\Support\Facades\Storage;
use App\Helpers\SimpleLogger;

class HallsController extends Controller
{
    // Show the admin event booking form (Blade)
    public function adminBookingForm()
    {
        $halls = \App\Models\Hall::all(['id', 'name']);
        return view('admin.booking_form', compact('halls'));
    }

    // Handle the booking form POST (Blade)
    public function blockBooking(\Illuminate\Http\Request $request)
    {
        // Only allow admins
        $admin = \Auth::guard('admin')->user();
        if (!$admin || (isset($admin->role) && $admin->role !== 'admin')) {
            return back()->withInput()->withErrors(['error' => 'Failed to create booking. Unauthorized action.']);
        }

        $data = $request->validate([
            'hall_id' => 'required|exists:halls,id',
            'year' => 'required|integer',
            'month' => 'required|integer',
            'day' => 'required|integer',
            'shift' => 'required|in:FN,AN,FD',
        ]);

        $bookingDate = sprintf('%04d-%02d-%02d', $data['year'], $data['month'], $data['day']);

        // Check for existing booking
        $conflictBooking = \App\Models\Booking::where([
            'hall_id' => $data['hall_id'],
            'booking_date' => $bookingDate,
            'shift' => $data['shift'],
        ])->where('status', '!=', 'Cancelled')->first();

        if ($conflictBooking) {
            // Set status to Review and statusUpdater to Admin
            $conflictBooking->status = 'Review';
            $conflictBooking->statusUpdater = 'Admin';
            $conflictBooking->save();
            // Log the event
            $desc = 'Event could not be booked because of conflict with booking_id: ' . $conflictBooking->id;
            SimpleLogger::log('bookingConflict', $desc);
            return back()->withInput()->withErrors(['error' => $desc]);
        }

        \App\Models\Booking::create([
            'member_id' => null,
            'hall_id' => $data['hall_id'],
            'booking_date' => $bookingDate,
            'shift' => $data['shift'],
            'status' => 'Unavailable',
            'statusUpdater' => 'Admin',
        ]);

        return back()->with('message', 'Booking created successfully.');
    }
    public function index()
    {
        $halls = Hall::all()->map(function ($hall) {
            return $this->transformHall($hall);
        });
        return response()->json($halls);
    }

    public function store(Request $request)
    {
        // Only restrict for POST (create), not for GET/index
        $admin = \Auth::guard('admin')->user();
        if (!$admin || (isset($admin->role) && $admin->role !== 'admin')) {
            return response()->json(['error' => 'Unauthorized. Only admins can create halls.'], 403);
        }
    // Parse JSON fields if sent as string (from FormData)
    foreach (['images', 'charges', 'policy_content'] as $field) {
        if ($request->has($field) && is_string($request->$field)) {
            $decoded = json_decode($request->$field, true);
            if ($decoded !== null) {
                $request->merge([$field => $decoded]);
            }
        }
    }

    $requestData = $request->all();

    // Set default empty arrays if not present
    if (!isset($requestData['images'])) {
        $requestData['images'] = [];
    }
    if (!isset($requestData['charges'])) {
        $requestData['charges'] = [];
    }
    if (!isset($requestData['policy_content'])) {
        $requestData['policy_content'] = [];
    }

    $data = validator($requestData, [
        'name' => 'required|string',
        'description' => 'nullable|string',
        'capacity' => 'nullable|integer',
        'charges' => 'nullable|array',
        'images' => 'nullable|array',
        'images.*' => 'file|image',
        'policy_pdf' => 'nullable|mimes:pdf',
        'policy_content' => 'nullable|array',
    ])->validate();


        // Upload images
        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $filename = time() . '_' . $image->getClientOriginalName();
                $path = $image->storeAs('halls', $filename, 'public');
                $imagePaths[] = 'media/' . $path;
            }
        }

        // Upload policy PDF
        $policyPdfPath = null;
        if ($request->hasFile('policy_pdf')) {
            $pdf = $request->file('policy_pdf');
            $pdfFilename = time() . '_' . $pdf->getClientOriginalName();
            $path = $pdf->storeAs('hall_policies', $pdfFilename, 'public');
            $policyPdfPath = 'media/' . $path;
        }

        // Create hall
        $hall = Hall::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'capacity' => $data['capacity'] ?? null,
            'charges' => $data['charges'],
            'images' => $imagePaths,
            'policy_pdf' => $policyPdfPath,
            'policy_content' => $data['policy_content'] ?? null,
        ]);

        return response()->json($this->transformHall($hall), 201);
    }

    public function show($id)
    {
        $hall = Hall::find($id);
        if (!$hall) return response()->json(['error' => 'Not found'], 404);
        return response()->json($this->transformHall($hall));
    }

public function updateBasic(Request $request, $id)
{
    // Only restrict for update
    $admin = \Auth::guard('admin')->user();
    if (!$admin || (isset($admin->role) && $admin->role !== 'admin')) {
        return response()->json(['error' => 'Unauthorized. Only admins can update halls.'], 403);
    }

    $hall = Hall::find($id);
    if (!$hall) return response()->json(['error' => 'Not found'], 404);

    $data = $request->validate([
        'name' => 'sometimes|string',
        'description' => 'nullable|string',
        'capacity' => 'nullable|integer',
        'is_active' => 'boolean',
    ]);

    $hall->update($data);
    return response()->json($this->transformHall($hall));
}

public function addCharge(Request $request, $id)
{
    // Only restrict for add
    $admin = \Auth::guard('admin')->user();
    if (!$admin || (isset($admin->role) && $admin->role !== 'admin')) {
        return response()->json(['error' => 'Unauthorized. Only admins can add charges.'], 403);
    }
    $hall = Hall::find($id);
    if (!$hall) return response()->json(['error' => 'Not found'], 404);

    $data = $request->validate([
        'name' => 'required|string',
        'value' => 'required|numeric',
    ]);

    $charges = $hall->charges ?? [];
    $charges[$data['name']] = $data['value'];

    $hall->charges = $charges;
    $hall->save();

    return response()->json($this->transformHall($hall));
}

public function updateCharge(Request $request, $id)
{
    // Only restrict for update
    $admin = \Auth::guard('admin')->user();
    if (!$admin || (isset($admin->role) && $admin->role !== 'admin')) {
        return response()->json(['error' => 'Unauthorized. Only admins can update charges.'], 403);
    }
    $hall = Hall::find($id);
    if (!$hall) return response()->json(['error' => 'Not found'], 404);

    $data = $request->validate([
        'name' => 'required|string',
        'value' => 'required|numeric',
    ]);

    $charges = $hall->charges ?? [];

    if (!array_key_exists($data['name'], $charges)) {
        return response()->json(['error' => 'Charge not found'], 404);
    }

    $charges[$data['name']] = $data['value'];
    $hall->charges = $charges;
    $hall->save();

    return response()->json($this->transformHall($hall));
}

public function deleteCharge(Request $request, $id)
{
    // Only restrict for delete
    $admin = \Auth::guard('admin')->user();
    if (!$admin || (isset($admin->role) && $admin->role !== 'admin')) {
        return response()->json(['error' => 'Unauthorized. Only admins can delete charges.'], 403);
    }
    $hall = Hall::find($id);
    if (!$hall) return response()->json(['error' => 'Not found'], 404);

    $data = $request->validate([
        'name' => 'required|string',
    ]);

    $charges = $hall->charges ?? [];

    if (!array_key_exists($data['name'], $charges)) {
        return response()->json(['error' => 'Charge not found'], 404);
    }

    unset($charges[$data['name']]);
    $hall->charges = $charges;
    $hall->save();

    return response()->json($this->transformHall($hall));
}

public function addPolicyPdf(Request $request, $id)
{
    // Only restrict for add
    $admin = \Auth::guard('admin')->user();
    if (!$admin || (isset($admin->role) && $admin->role !== 'admin')) {
        return response()->json(['error' => 'Unauthorized. Only admins can upload policy PDF.'], 403);
    }
    \Log::info('Incoming policy_pdf request:', [
        'has_file' => $request->hasFile('policy_pdf'),
        'files' => $request->allFiles(),
        'all' => $request->all()
    ]);

    $hall = Hall::find($id);
    if (!$hall) return response()->json(['error' => 'Not found'], 404);


        if ($request->hasFile('policy_pdf')) {
            $pdf = $request->file('policy_pdf');
            $filename = time() . '_' . $pdf->getClientOriginalName();
            $path = $pdf->storeAs('hall_policies', $filename, 'public');

            $hall->policy_pdf = 'media/' . $path;
            $hall->save();

            return response()->json([
                'message' => 'Uploaded',
                'url' => url('media/' . $path),
                'filename' => $filename
            ]);
        }

    return response()->json(['error' => 'No file uploaded'], 400);
}

public function updatePolicyPdf(Request $request, $id)
{
    // Only restrict for update
    $admin = \Auth::guard('admin')->user();
    if (!$admin || (isset($admin->role) && $admin->role !== 'admin')) {
        return response()->json(['error' => 'Unauthorized. Only admins can update policy PDF.'], 403);
    }
    $hall = Hall::find($id);
    if (!$hall) return response()->json(['error' => 'Not found'], 404);

    $request->validate([
        'policy_pdf' => 'required|mimes:pdf',
    ]);

    // Delete old PDF if exists
    if ($hall->policy_pdf) {
        $oldPath = str_replace('media/', '', $hall->policy_pdf);
        Storage::disk('public')->delete($oldPath);
    }

    $pdf = $request->file('policy_pdf');
    $filename = time() . '_' . $pdf->getClientOriginalName();
    $path = $pdf->storeAs('hall_policies', $filename, 'public');

    $hall->policy_pdf = 'media/' . $path;
    $hall->save();

    return response()->json($this->transformHall($hall));
}

public function deletePolicyPdf($id)
{
    // Only restrict for delete
    $admin = \Auth::guard('admin')->user();
    if (!$admin || (isset($admin->role) && $admin->role !== 'admin')) {
        return response()->json(['error' => 'Unauthorized. Only admins can delete policy PDF.'], 403);
    }
    $hall = Hall::find($id);
    if (!$hall) return response()->json(['error' => 'Not found'], 404);

    if (!$hall->policy_pdf) {
        return response()->json(['error' => 'No policy PDF found'], 404);
    }

    $relativePath = str_replace('media/', '', $hall->policy_pdf);
    Storage::disk('public')->delete($relativePath);

    $hall->policy_pdf = null;
    $hall->save();

    return response()->json(['message' => 'Policy PDF deleted successfully']);
}

public function addImages(Request $request, $id)
{
    // Only restrict for add
    $admin = \Auth::guard('admin')->user();
    if (!$admin || (isset($admin->role) && $admin->role !== 'admin')) {
        return response()->json(['error' => 'Unauthorized. Only admins can upload images.'], 403);
    }
    $hall = Hall::find($id);
    if (!$hall) return response()->json(['error' => 'Not found'], 404);

    $request->validate([
        'images' => 'required|array',
        'images.*' => 'file|image',
        'dummy' => 'nullable|string',
    ]);

    $dummy = $request->input('dummy'); // just to show you're receiving it


    $newImagePaths = [];
    foreach ($request->file('images') as $image) {
        $filename = time() . '_' . $image->getClientOriginalName();
        $path = $image->storeAs('halls', $filename, 'public');
        $newImagePaths[] = 'media/' . $path;
    }

    $hall->images = array_merge($hall->images ?? [], $newImagePaths);
    $hall->save();

    return response()->json([
        'hall' => $this->transformHall($hall),
        'dummy' => $dummy,
    ]);
}

public function deleteImage(Request $request, $id)
{
    // Only restrict for delete
    $admin = \Auth::guard('admin')->user();
    if (!$admin || (isset($admin->role) && $admin->role !== 'admin')) {
        return response()->json(['error' => 'Unauthorized. Only admins can delete images.'], 403);
    }
    $hall = Hall::find($id);
    if (!$hall) return response()->json(['error' => 'Not found'], 404);

    $request->validate([
        'image_url' => 'required|string',
    ]);

    $imageUrl = $request->input('image_url');

    $parsedUrlPath = parse_url($imageUrl, PHP_URL_PATH); // /media/halls/filename.jpg
    $relativePath = ltrim($parsedUrlPath, '/');           // media/halls/filename.jpg

    $images = $hall->images ?? [];

    if (!in_array($relativePath, $images)) {
        return response()->json(['error' => 'Image not found in record'], 404);
    }

    // Delete file
    Storage::disk('public')->delete(str_replace('media/', '', $relativePath));

    // Remove from DB array
    $updatedImages = array_filter($images, fn($img) => $img !== $relativePath);
    $hall->images = array_values($updatedImages);
    $hall->save();

    return response()->json($this->transformHall($hall));
}

public function addPolicyContent(Request $request, $id)
{
    // Only restrict for add
    $admin = \Auth::guard('admin')->user();
    if (!$admin || (isset($admin->role) && $admin->role !== 'admin')) {
        return response()->json(['error' => 'Unauthorized. Only admins can add policy content.'], 403);
    }

    $hall = Hall::find($id);
    if (!$hall) return response()->json(['error' => 'Not found'], 404);

    $data = $request->validate([
        'header' => 'required|string',
        'description' => 'required|string',
    ]);

    $policy = $hall->policy_content ?? [];
    $policy[$data['header']] = $data['description'];

    $hall->policy_content = $policy;
    $hall->save();

    return response()->json($this->transformHall($hall));
}

public function updatePolicyContent(Request $request, $id)
{
    // Only restrict for update
    $admin = \Auth::guard('admin')->user();
    if (!$admin || (isset($admin->role) && $admin->role !== 'admin')) {
        return response()->json(['error' => 'Unauthorized. Only admins can update policy content.'], 403);
    }
    $hall = Hall::find($id);
    if (!$hall) return response()->json(['error' => 'Not found'], 404);

    $data = $request->validate([
        'header' => 'required|string',
        'description' => 'required|string',
    ]);

    $policy = $hall->policy_content ?? [];

    if (!array_key_exists($data['header'], $policy)) {
        return response()->json(['error' => 'Policy header not found'], 404);
    }

    $policy[$data['header']] = $data['description'];
    $hall->policy_content = $policy;
    $hall->save();

    return response()->json($this->transformHall($hall));
}


    public function deletePolicyContent(Request $request, $id)
    {
        // Only restrict for delete
        $admin = \Auth::guard('admin')->user();
        if (!$admin || (isset($admin->role) && $admin->role !== 'admin')) {
            return response()->json(['error' => 'Unauthorized. Only admins can delete policy content.'], 403);
        }

        $hall = Hall::find($id);
        if (!$hall) return response()->json(['error' => 'Not found'], 404);

        $data = $request->validate([
            'header' => 'required|string',
        ]);

        $policy = $hall->policy_content ?? [];

        if (!array_key_exists($data['header'], $policy)) {
            return response()->json(['error' => 'Policy header not found'], 404);
        }

        unset($policy[$data['header']]);
        $hall->policy_content = $policy;
        $hall->save();

        return response()->json($this->transformHall($hall));
    }

    // Search policy content keys by partial match
    public function searchPolicyContent(Request $request, $id)
    {
        $hall = Hall::find($id);
        if (!$hall) return response()->json(['error' => 'Not found'], 404);

        $data = $request->validate([
            'query' => 'required|string',
        ]);

        $policy = $hall->policy_content ?? [];
        $query = strtolower($data['query']);
        $results = [];
        foreach ($policy as $header => $desc) {
            if (strpos(strtolower($header), $query) !== false) {
                $results[$header] = $desc;
            }
        }
        return response()->json(['results' => $results]);
    }



public function destroy($id)
{
    // Only restrict for delete
    $admin = \Auth::guard('admin')->user();
    if (!$admin || (isset($admin->role) && $admin->role !== 'admin')) {
        return response()->json(['error' => 'Unauthorized. Only admins can delete halls.'], 403);
    }

    $hall = Hall::find($id);
    if (!$hall) return response()->json(['error' => 'Not found'], 404);


    // Delete images from storage
    if (is_array($hall->images)) {
        foreach ($hall->images as $image) {
            $relativePath = str_replace('media/', '', $image);
            Storage::disk('public')->delete($relativePath);
        }
    }

    // Delete policy PDF from storage
    if ($hall->policy_pdf) {
        $relativePdfPath = str_replace('media/', '', $hall->policy_pdf);
        Storage::disk('public')->delete($relativePdfPath);
    }

    // Delete the hall from database
    $hall->delete();

    return response()->json(['message' => 'Hall and associated assets deleted successfully']);
}

private function transformHall($hall)
{
    return [
        'id' => $hall->id,
        'name' => $hall->name,
        'description' => $hall->description,
        'capacity' => $hall->capacity,
        'charges' => $hall->charges,
        'images' => is_array($hall->images)
            ? array_map(fn($img) => $img ? url($img) : null, $hall->images)
            : [],
        'policy_pdf' => $hall->policy_pdf ? url($hall->policy_pdf) : null,
        'policy_content' => $hall->policy_content,
        'is_active' => $hall->is_active,
        'created_at' => $hall->created_at,
        'updated_at' => $hall->updated_at,
    ];
}

}
