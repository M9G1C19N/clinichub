<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BookingPhotoController extends Controller
{
    public function index()
    {
        $photos = BookingPhoto::orderBy('sort_order')->orderBy('id')->get()
            ->map(fn($p) => [
                'id'         => $p->id,
                'url'        => $p->url,
                'caption'    => $p->caption,
                'is_active'  => $p->is_active,
                'sort_order' => $p->sort_order,
                'created_at' => $p->created_at->format('M d, Y'),
            ]);

        return inertia('Admin/BookingPhotos', compact('photos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'photos'          => ['required', 'array', 'min:1'],
            'photos.*'        => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:12288'],
            'captions'        => ['nullable', 'array'],
            'captions.*'      => ['nullable', 'string', 'max:150'],
        ]);

        $maxOrder  = BookingPhoto::max('sort_order') ?? 0;
        $stored    = []; // track uploaded paths so we can clean up on failure

        try {
            DB::beginTransaction();

            foreach ($request->file('photos') as $i => $file) {
                $path    = $file->store('booking-photos', 'public');
                $stored[] = $path;

                BookingPhoto::create([
                    'file_path'  => $path,
                    'caption'    => $request->input("captions.$i") ?? null,
                    'is_active'  => true,
                    'sort_order' => $maxOrder + $i + 1,
                ]);
            }

            DB::commit();

        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            foreach ($stored as $path) {
                Storage::disk('public')->delete($path);
            }
            Log::error('BookingPhoto upload DB error: ' . $e->getMessage());

            return back()->withErrors([
                'upload_error' => 'Database error while saving photos: ' . $e->getMessage(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            foreach ($stored as $path) {
                Storage::disk('public')->delete($path);
            }
            Log::error('BookingPhoto upload error: ' . $e->getMessage());

            return back()->withErrors([
                'upload_error' => 'Upload failed: ' . $e->getMessage(),
            ]);
        }

        return back()->with('success', 'Photos uploaded successfully.');
    }

    public function update(Request $request, BookingPhoto $photo)
    {
        $request->validate([
            'caption' => ['nullable', 'string', 'max:150'],
        ]);

        $photo->update(['caption' => $request->caption]);

        return back()->with('success', 'Caption updated.');
    }

    public function toggleActive(BookingPhoto $photo)
    {
        $photo->update(['is_active' => !$photo->is_active]);

        return back()->with('success', $photo->is_active ? 'Photo shown on booking page.' : 'Photo hidden from booking page.');
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'order'   => ['required', 'array'],
            'order.*' => ['integer'],
        ]);

        foreach ($request->order as $sort => $id) {
            BookingPhoto::where('id', $id)->update(['sort_order' => $sort]);
        }

        return back()->with('success', 'Order saved.');
    }

    public function destroy(BookingPhoto $photo)
    {
        Storage::disk('public')->delete($photo->file_path);
        $photo->delete();

        return back()->with('success', 'Photo deleted.');
    }
}
