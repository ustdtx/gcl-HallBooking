<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Hall;
use Illuminate\Support\Facades\Storage;

class HallsController extends Controller
{
    public function index()
    {
        $halls = Hall::all()->map(function ($hall) {
            return $this->transformHall($hall);
        });
        return response()->json($halls);
    }

    public function store(Request $request)
    {
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
                $imagePaths[] = 'storage/' . $path;
            }
        }

        // Upload policy PDF
        $policyPdfPath = null;
        if ($request->hasFile('policy_pdf')) {
            $pdf = $request->file('policy_pdf');
            $pdfFilename = time() . '_' . $pdf->getClientOriginalName();
            $path = $pdf->storeAs('hall_policies', $pdfFilename, 'public');
            $policyPdfPath = 'storage/' . $path;
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

        $hall->policy_pdf = 'storage/' . $path;
        $hall->save();

        return response()->json([
            'message' => 'Uploaded',
            'url' => url('storage/' . $path),
            'filename' => $filename
        ]);
    }

    return response()->json(['error' => 'No file uploaded'], 400);
}

public function updatePolicyPdf(Request $request, $id)
{
    $hall = Hall::find($id);
    if (!$hall) return response()->json(['error' => 'Not found'], 404);

    $request->validate([
        'policy_pdf' => 'required|mimes:pdf',
    ]);

    // Delete old PDF if exists
    if ($hall->policy_pdf) {
        $oldPath = str_replace('storage/', '', $hall->policy_pdf);
        Storage::disk('public')->delete($oldPath);
    }

    $pdf = $request->file('policy_pdf');
    $filename = time() . '_' . $pdf->getClientOriginalName();
    $path = $pdf->storeAs('hall_policies', $filename, 'public');

    $hall->policy_pdf = 'storage/' . $path;
    $hall->save();

    return response()->json($this->transformHall($hall));
}

public function deletePolicyPdf($id)
{
    $hall = Hall::find($id);
    if (!$hall) return response()->json(['error' => 'Not found'], 404);

    if (!$hall->policy_pdf) {
        return response()->json(['error' => 'No policy PDF found'], 404);
    }

    $relativePath = str_replace('storage/', '', $hall->policy_pdf);
    Storage::disk('public')->delete($relativePath);

    $hall->policy_pdf = null;
    $hall->save();

    return response()->json(['message' => 'Policy PDF deleted successfully']);
}

public function addImages(Request $request, $id)
{
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
        $newImagePaths[] = 'storage/' . $path;
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
    $hall = Hall::find($id);
    if (!$hall) return response()->json(['error' => 'Not found'], 404);

    $request->validate([
        'image_url' => 'required|string',
    ]);

    $imageUrl = $request->input('image_url');
    $parsedUrlPath = parse_url($imageUrl, PHP_URL_PATH); // /storage/halls/filename.jpg
    $relativePath = ltrim($parsedUrlPath, '/');           // storage/halls/filename.jpg

    $images = $hall->images ?? [];

    if (!in_array($relativePath, $images)) {
        return response()->json(['error' => 'Image not found in record'], 404);
    }

    // Delete file
    Storage::disk('public')->delete(str_replace('storage/', '', $relativePath));

    // Remove from DB array
    $updatedImages = array_filter($images, fn($img) => $img !== $relativePath);
    $hall->images = array_values($updatedImages);
    $hall->save();

    return response()->json($this->transformHall($hall));
}

public function addPolicyContent(Request $request, $id)
{
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



public function destroy($id)
{
    $hall = Hall::find($id);
    if (!$hall) return response()->json(['error' => 'Not found'], 404);

    // Delete images from storage
    if (is_array($hall->images)) {
        foreach ($hall->images as $image) {
            $relativePath = str_replace('storage/', '', $image);
            Storage::disk('public')->delete($relativePath);
        }
    }

    // Delete policy PDF from storage
    if ($hall->policy_pdf) {
        $relativePdfPath = str_replace('storage/', '', $hall->policy_pdf);
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
