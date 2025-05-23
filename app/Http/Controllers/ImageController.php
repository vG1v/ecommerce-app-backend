<?php

namespace App\Http\Controllers;

use App\Models\Image;
use App\Models\User;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    /**
     * Upload an image for a model.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:2048', // 2MB Max
            'model_type' => 'required|string|in:user,product', // What type of model
            'model_id' => 'required|integer',
            'type' => 'required|string|in:profile,main,gallery',
        ]);

        // Get the model instance based on model_type
        $model = $this->getModel($request->model_type, $request->model_id);

        if (!$model) {
            return response()->json([
                'status' => 'error',
                'message' => 'Model not found'
            ], 404);
        }

        // Check authorization
        if (!$this->authorizeImageUpload($request->model_type, $model)) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to upload images for this resource'
            ], 403);
        }

        try {
            // For profile or main images, remove existing images of that type
            if (in_array($request->type, ['profile', 'main'])) {
                $existingImage = $model->images()->where('type', $request->type)->first();
                if ($existingImage) {
                    $this->deleteImageFile($existingImage);
                    $existingImage->delete();
                }
            }

            // Process and store the image
            $image = $request->file('image');
            $originalFilename = $image->getClientOriginalName();
            $filename = $request->model_type . '-' . $model->id . '-' . $request->type . '-' . time() . '.' . $image->getClientOriginalExtension();

            // Determine directory path
            $directory = $request->model_type . 's/' . $model->id;
            if ($request->type == 'gallery') {
                $directory .= '/gallery';
            }

            $path = $image->storeAs($directory, $filename, 'public');

            // Create the image record
            $newImage = new Image([
                'path' => '/storage/' . $path,
                'filename' => $originalFilename,
                'type' => $request->type,
                'sort_order' => $request->type == 'gallery' ? $model->images()->where('type', 'gallery')->count() + 1 : 0
            ]);

            $model->images()->save($newImage);

            // Update main_image_path for products (for backward compatibility)
            if ($request->model_type == 'product' && $request->type == 'main') {
                $model->main_image_path = '/storage/' . $path;
                $model->save();
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Image uploaded successfully',
                'image' => $newImage
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to upload image',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an image.
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $image = Image::findOrFail($id);
        $model = $image->imageable;

        // Check authorization
        $modelType = $this->getModelType($model);
        if (!$this->authorizeImageUpload($modelType, $model)) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to delete this image'
            ], 403);
        }

        try {
            // Delete the file
            $this->deleteImageFile($image);

            // Delete the record
            $image->delete();

            // Reorder remaining gallery images if needed
            if ($image->type == 'gallery') {
                $galleryImages = $model->images()
                    ->where('type', 'gallery')
                    ->orderBy('sort_order')
                    ->get();

                foreach ($galleryImages as $index => $img) {
                    $img->sort_order = $index + 1;
                    $img->save();
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Image deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete image',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reorder gallery images.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'model_type' => 'required|string|in:user,product',
            'model_id' => 'required|integer',
            'images' => 'required|array',
            'images.*.id' => 'required|exists:images,id',
            'images.*.sort_order' => 'required|integer|min:1'
        ]);

        $model = $this->getModel($request->model_type, $request->model_id);

        if (!$model) {
            return response()->json([
                'status' => 'error',
                'message' => 'Model not found'
            ], 404);
        }

        // Check authorization
        if (!$this->authorizeImageUpload($request->model_type, $model)) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to reorder images for this resource'
            ], 403);
        }

        try {
            foreach ($request->images as $imageData) {
                $image = Image::find($imageData['id']);

                // Make sure the image belongs to this model and is a gallery image
                if (
                    $image && $image->imageable_id == $model->id &&
                    $image->imageable_type == get_class($model) &&
                    $image->type == 'gallery'
                ) {

                    $image->sort_order = $imageData['sort_order'];
                    $image->save();
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Images reordered successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to reorder images',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all images for a model.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $request->validate([
            'model_type' => 'required|string|in:user,product',
            'model_id' => 'required|integer',
            'type' => 'nullable|string|in:profile,main,gallery'
        ]);

        $model = $this->getModel($request->model_type, $request->model_id);

        if (!$model) {
            return response()->json([
                'status' => 'error',
                'message' => 'Model not found'
            ], 404);
        }

        $query = $model->images();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $images = $query->orderBy('sort_order')->get();

        return response()->json([
            'status' => 'success',
            'data' => $images
        ]);
    }

    // Helper to get model instance based on type and ID.
    private function getModel($type, $id)
    {
        switch ($type) {
            case 'user':
                return User::find($id);
            case 'product':
                return Product::find($id);
            default:
                return null;
        }
    }

    // Helper to get model type from model instance.
    private function getModelType($model)
    {
        if ($model instanceof User) {
            return 'user';
        } elseif ($model instanceof Product) {
            return 'product';
        }
        return null;
    }

    // Check if user is authorized to upload/manage images for this model.
    private function authorizeImageUpload($modelType, $model)
    {
        $user = Auth::user();

        switch ($modelType) {
            case 'user':
                // Users can only manage their own profile images
                return $user->id == $model->id;

            case 'product':
                // Only vendors who own the product can manage its images
                $vendor = Vendor::where('user_id', $user->id)->first();
                return $vendor && $vendor->id == $model->vendor_id;

            default:
                return false;
        }
    }

    // Delete image file from storage.
    private function deleteImageFile($image)
    {
        if ($image->path) {
            $path = str_replace('/storage/', '', $image->path);
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
    }
}
