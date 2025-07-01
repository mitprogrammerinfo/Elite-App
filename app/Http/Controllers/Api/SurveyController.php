<?php

namespace App\Http\Controllers\Api;
use App\Http\Resources\ExteriorPhotoResource;
use App\Http\Resources\ExteriorSurveyResource;
use App\Http\Resources\InteriorSurveyCategoryResource;
use App\Http\Resources\SurveyResource;
use App\Http\Resources\DamageInspectionPhotoResource;
use App\Models\ExteriorFeature;
use App\Models\InteriorFeature;
use App\Models\SurveyIntCat;
use App\Models\SurveyIntCatFeature;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Survey;
use App\Models\InteriorCategory;
use Illuminate\Support\Facades\Auth;
use DB;
use Mail;
use Storage;


class SurveyController extends Controller
{

public function uploadExteriorPhotos(Request $request, Survey $survey=null)
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
       

         // Validate the type first
    $request->validate([
        'type' => 'required|in:pre_loss,damage,post_repair',
    ]);

    // For damage type, delegate full validation and processing
    if ($request->type === 'damage') {
        // Validate just like damageInspectionPhotos
        $request->validate([
            'rooms' => 'required|array|min:1',
        ]);

        return $this->damageInspectionPhotos($request, $survey);
    }
    
         $request->validate([
        'photos' => 'required|array|size:6',
        'photos.*' => 'image|mimes:jpeg,png,jpg'
    ]);

        if (!$survey) {
            $survey = Survey::create([
                'user_id' => $userId,
                'type' => $request->type,
                'status' => 'exterior_photos',
            ]);
        } elseif ($survey->type === 'damage') {
            return response()->json(['error' => 'Survey is of type damage and cannot use standard exterior photos'], 400);
        }

        $photoLabels = [
            'address',
            'front_elevation',
            'right_elevation',
            'left_elevation',
            'rear',
            'roof'
        ];

      //  DB::beginTransaction();
        try {
            $uploadedPhotos = [];
            foreach ($request->file('photos') as $index => $photo) {
                if (!isset($photoLabels[$index])) {
                    throw new \Exception('Invalid number of photos provided');
                }
                $label = $photoLabels[$index];
                $filename = $label . '_' . time() . '.' . $photo->getClientOriginalExtension();
                $path = $photo->storeAs('images/exterior_photos', $filename, 'public');

                $photoModel = $survey->exteriorPhotos()->create([
                    'image_path' => $path,
                    'label' => $label
                ]);

                $uploadedPhotos[] = [
                    'label' => $label,
                    'image_path' => asset('storage/' . $path)
                ];
            }

            $survey->update(['status' => 'interior_selections']);
           // DB::commit();

            return response()->json([
                'message' => 'Photos uploaded successfully',
                'survey_id' => $survey->id,
                'photos' => ExteriorPhotoResource::collection($survey->exteriorPhotos)
            ], 200);
        } catch (\Exception $e) {
           // DB::rollBack();
            return response()->json([
                'error' => 'Failed to upload exterior photos: ' . $e->getMessage()
            ], 500);
        }
    }

 public function damageInspectionPhotos(Request $request, Survey $survey = null)
{
    $userId = Auth::id();
    if (!$userId) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    // Basic structure validation
    $request->validate([
        'type' => 'required|in:damage',
        'rooms' => 'required|array|min:1',
    ]);

    if (!$survey) {
        $survey = Survey::create([
            'user_id' => $userId,
            'type' => $request->type,
            'status' => 'exterior_photos',
        ]);
    } elseif ($survey->type !== 'damage') {
        return response()->json(['error' => 'Survey is not of type damage'], 400);
    }

    DB::beginTransaction();

    try {
        foreach ($request->rooms as $roomKey => $roomData) {
            // Accept room key as-is (e.g., 'room1', 'room2')
            $roomIndex = $roomKey;

            // Validate that photos exist and have minimum 2 images
            if (!isset($roomData['photos']) || !is_array($roomData['photos']) || count($roomData['photos']) < 2) {
                throw new \Exception("Each room must have at least 2 photos. Room: {$roomKey}");
            }

            foreach ($roomData['photos'] as $photo) {
                if (
                    !$photo->isValid() ||
                    !in_array($photo->getMimeType(), ['image/jpeg', 'image/png', 'image/jpg'])
                ) {
                    throw new \Exception("Invalid image in {$roomKey}");
                }

                $filename = 'damage_' . $survey->id . '_' . time() . '_' . uniqid() . '.' . $photo->getClientOriginalExtension();
                $path = $photo->storeAs('survey/exterior/damage', $filename, 'public');

                $survey->damageInspectionPhotos()->create([
                    'image_path' => $path,
                    'room_index' => $roomIndex
                ]);
            }
        }

        $survey->update(['status' => 'completed']);
        DB::commit();

        // Fetch and group the photos by room_index
        $groupedPhotos = $survey->damageInspectionPhotos()
            ->get()
            ->groupBy('room_index')
            ->map(function ($photos) {
                return DamageInspectionPhotoResource::collection($photos);
            });

        return response()->json([
            'message' => 'Damage inspection photos uploaded successfully',
            'survey_id' => $survey->id,
            'photos' => $groupedPhotos,
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'error' => 'Failed to upload damage inspection photos: ' . $e->getMessage()
        ], 500);
    }
}

public function getInteriorCategories()
{
    $categories = InteriorCategory::all();
    return response()->json($categories);
}

public function getInteriorCategoriesFeatures()
{
    $features = InteriorFeature::all();
    return response()->json($features);
}

public function saveInteriorSurvey(Request $request, Survey $survey)
{
    $request->validate([
        'categories' => 'required|array',
        'categories.*.category_id' => 'required|exists:interior_categories,id',
        'categories.*.images' => 'required|array|min:1',
        'categories.*.images.*' => 'image|mimes:jpeg,png,jpg|max:2048',
        'categories.*.features' => 'nullable|array',
        'categories.*.features.*' => 'nullable|exists:interior_features,id',
    ]);

    DB::beginTransaction();
    try {
        $uploadedData = [
            'survey_id' => $survey->id,
            'categories' => [],
        ];

        foreach ($request->categories as $categoryData) {
            $surveyCategory = SurveyIntCat::create([
                'survey_id' => $survey->id,
                'category_id' => $categoryData['category_id']
            ]);

            // Store images and capture their IDs
            $images = [];
            foreach ($categoryData['images'] as $image) {
                $path = $image->store('survey/interior/categories', 'public');
                $imageModel = $surveyCategory->images()->create(['image_path' => $path]);
                $images[] = [
                    'id' => $imageModel->id,
                    'url' => asset('storage/' . $path)
                ];
            }

            // Store features only if they exist
            $features = [];
            if (!empty($categoryData['features'])) {
                foreach ($categoryData['features'] as $featureId) {
                    SurveyIntCatFeature::create([
                        'survey_int_cat_id' => $surveyCategory->id,
                        'feature_id' => $featureId
                    ]);
                    $features[] = $featureId;
                }
            }

            $uploadedData['categories'][] = [
                'category_id' => $categoryData['category_id'],
                'images' => $images,
                'features' => $features,
            ];
        }

        $survey->update(['status' => 'exterior_features']);
        DB::commit();

        return response()->json([
            'message' => 'Interior survey saved successfully',
            'data' => InteriorSurveyCategoryResource::collection($survey->interiorCategories)
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'error' => 'Failed to save interior survey: ' . $e->getMessage()
        ], 500);
    }
}

public function getExteriorFeatures()
    {
        $features = ExteriorFeature::all();
        return response()->json($features);
    }
public function saveExteriorSurvey(Request $request, Survey $survey)
{
    $request->validate([
        'features' => 'nullable|array',
        'features.*' => 'exists:exterior_features,id',
        'images' => 'required|array|min:1',
        'images.*' => 'image|mimes:jpeg,png,jpg|max:2048',
    ]);

    DB::beginTransaction();

    try {
        // Sync features if provided, else detach all
        if (!empty($request->features)) {
            $survey->exteriorFeatures()->sync($request->features);
        } else {
            $survey->exteriorFeatures()->detach();
        }

        $uploadedImages = [];
        foreach ($request->images as $image) {
            $filename = 'exterior_'.$survey->id.'_'.time().'_'.uniqid().'.'.$image->getClientOriginalExtension();
            $path = $image->storeAs('exterior_images', $filename, 'public');

            $uploadedImages[] = $path;
            $survey->exteriorImages()->create([
                'image_path' => $path,
            ]);
        }

        DB::commit();

        $survey->update(['status' => 'completed']);

        return response()->json([
            'message' => 'Exterior survey saved successfully',
            'data' => new ExteriorSurveyResource($survey),
            'timestamp' => now()->toDateTimeString()
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'error' => 'Failed to save exterior survey',
            'details' => $e->getMessage(),
            'trace' => config('app.debug') ? $e->getTrace() : null
        ], 500);
    }
}

public function getCompletedSurveys(Request $request) 
{
    $userId = Auth::id();
    if (!$userId) return response()->json(['error' => 'Unauthorized'], 401);

    $filter = $request->query('filter', 'all');
    $query = Survey::where('user_id', $userId)->where('status', 'completed');

    if ($filter === 'today') {
        $query->whereDate('created_at', now());
    } elseif ($filter === 'week') {
        $query->whereBetween('created_at', [
            Carbon::now()->subDays(6)->startOfDay(), 
            Carbon::now()->endOfDay()
        ]);
    }  elseif ($filter === 'month') {
        $query->whereBetween('created_at', [
            Carbon::now()->subDays(29)->startOfDay(),
            Carbon::now()->endOfDay()
        ]);
    }

    $surveys = $query->orderByDesc('created_at')->get();

    return response()->json([
        'message' => 'Completed surveys fetched successfully',
        'surveys' => SurveyResource::collection($surveys)
    ]);
}
public function getIncompleteSurveys(Request $request)
{
    $userId = Auth::id();
    if (!$userId) return response()->json(['error' => 'Unauthorized'], 401);

    $query = Survey::where('user_id', $userId)->where('status', '!=', 'completed')->orderByDesc('created_at');

    if ($request->has('status')) {
        $query->where('status', $request->status);
    }

    $surveys = $query->get();

    return response()->json([
        'message' => 'Incomplete surveys fetched successfully',
        'surveys' => SurveyResource::collection($surveys)
    ]);
}

public function getSurveyById($surveyId)
{
    $userId = Auth::id();
    if (!$userId) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $survey = Survey::where('id', $surveyId)
        ->where('user_id', $userId)
        ->first();

    if (!$survey) {
        return response()->json(['error' => 'Survey not found'], 404);
    }

    return response()->json([
        'message' => 'Survey fetched successfully',
        'survey' => new SurveyResource($survey),
    ]);
}

public function getSurveyStatus(Request $request, $surveyId)
{
    $userId = Auth::id();

    if (!$userId) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    // Fetch the survey by ID for the authenticated user
    $survey = Survey::where('user_id', $userId)
                    ->where('id', $surveyId)
                    ->first();

    if (!$survey) {
        return response()->json(['error' => 'Survey not found or unauthorized'], 404);
    }

    // Return the survey status
    return response()->json([
        'survey_id' => $survey->id,
        'status' => $survey->status,
    ]);
}

 public function fetchDamageInspectionSurveys(Request $request)
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            // Fetch damage surveys for the authenticated user
            $surveys = Survey::where('user_id', $userId)
                ->where('type', 'damage')
                ->with(['damageInspectionPhotos' => function ($query) {
                    $query->select('id', 'survey_id', 'image_path', 'room_index', 'created_at', 'updated_at');
                }])
                ->get();

            // Transform surveys to include grouped photos
            $response = $surveys->map(function ($survey) {
                $groupedPhotos = $survey->damageInspectionPhotos
                    ->groupBy('room_index')
                    ->map(function ($photos) {
                        return DamageInspectionPhotoResource::collection($photos);
                    });

                return [
                    'id' => $survey->id,
                    'user_id' => $survey->user_id,
                    'type' => $survey->type,
                    'status' => $survey->status,
                    'created_at' => $survey->created_at,
                    'updated_at' => $survey->updated_at,
                    'photos' => $groupedPhotos,
                ];
            });

            return response()->json([
                'message' => 'Damage inspection surveys retrieved successfully',
                'surveys' => $response,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch damage inspection surveys: ' . $e->getMessage(),
            ], 500);
        }
    }

public function fetchDamageInspectionSurveysById(Request $request, $surveyId = null)
{
    $userId = Auth::id();
    if (!$userId) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    try {
        $query = Survey::where('user_id', $userId)->where('type', 'damage')
            ->with(['damageInspectionPhotos' => function ($query) {
                $query->select('id', 'survey_id', 'image_path', 'room_index', 'created_at', 'updated_at');
            }]);

        if ($surveyId) {
            $query->where('id', $surveyId);
        }

        $surveys = $query->get();

        if ($surveyId && $surveys->isEmpty()) {
            return response()->json(['error' => 'Survey not found'], 404);
        }

        $response = $surveys->map(function ($survey) {
            $groupedPhotos = $survey->damageInspectionPhotos
                ->groupBy('room_index')
                ->map(function ($photos) {
                    return DamageInspectionPhotoResource::collection($photos);
                });

            return [
                'id' => $survey->id,
                'user_id' => $survey->user_id,
                'type' => $survey->type,
                'status' => $survey->status,
                'created_at' => $survey->created_at,
                'updated_at' => $survey->updated_at,
                'photos' => $groupedPhotos,
            ];
        });

        return response()->json([
            'message' => 'Damage inspection surveys retrieved successfully',
            'surveys' => $response,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to fetch damage inspection surveys: ' . $e->getMessage(),
        ], 500);
    }
}
public function updateSurvey(Request $request, $surveyId)
{
    $userId = Auth::id();
    if (!$userId) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    // Find the survey
    $survey = Survey::findOrFail($surveyId);

    $request->validate([
        'update_type' => 'required|in:exterior_photos,interior_selections,exterior_features'
    ]);

    $updateType = $request->input('update_type', 'exterior_photos');

    switch ($updateType) {
        case 'exterior_photos':
         return $this->updateExteriorPhotos($request, $survey);  
        case 'interior_selections':
            return $this->updateInteriorSurvey($request, $survey);
            
        case 'exterior_features':
            return $this->updateExteriorSurvey($request, $survey);
            
        default:
            return response()->json(['error' => 'Invalid update type'], 400);
    }
}

protected function updateExteriorPhotos(Request $request, Survey $survey)
{
    $request->validate([
        'photos' => 'required|array|min:1|max:6',
        'photos.*.file' => 'required|file|image|mimes:jpeg,png,jpg',
        'photos.*.label' => 'required|string|in:address,front_elevation,right_elevation,left_elevation,rear,roof'
    ]);

    DB::beginTransaction();

    try {
        foreach ($request->photos as $photoData) {
            $photo = $photoData['file']; 
            $label = $photoData['label'];

            // Delete existing photo if any
            $existing = $survey->exteriorPhotos()->where('label', $label)->first();
            if ($existing) {
                \Storage::disk('public')->delete($existing->image_path);
                $existing->delete();
            }

            // Store new photo
            $filename = $label . '_' . time() . '.' . $photo->getClientOriginalExtension();
            $path = $photo->storeAs('images/exterior_photos', $filename, 'public');

            $survey->exteriorPhotos()->create([
                'label' => $label,
                'image_path' => $path,
            ]);
        }

        DB::commit();
        return response()->json([
            'message' => 'Photos updated successfully',
            'photos' => ExteriorPhotoResource::collection($survey->exteriorPhotos)
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'error' => 'Upload failed',
            'details' => $e->getMessage()
        ], 500);
    }
}

protected function updateInteriorSurvey(Request $request, Survey $survey) 
{
    $request->validate([
        'categories' => 'required|array',
        'categories.*.category_id' => 'required|exists:interior_categories,id',
        'categories.*.new_images' => 'sometimes|array|nullable',
        'categories.*.new_images.*.image' => 'image|mimes:jpeg,png,jpg|max:2048',
        'categories.*.new_images.*.position' => 'required|integer|min:0|max:3', // 0-3 for 4 fixed positions
        'categories.*.features' => 'sometimes|array|nullable',
        'categories.*.features.*' => 'exists:interior_features,id',
    ]);

    DB::beginTransaction();

    try {
        $existingCategories = $survey->interiorCategories()->pluck('category_id')->toArray();
        $incomingCategories = collect($request->categories)->pluck('category_id')->toArray();

        // 1. Delete removed categories
        $toDelete = array_diff($existingCategories, $incomingCategories);
        SurveyIntCat::where('survey_id', $survey->id)
            ->whereIn('category_id', $toDelete)
            ->each(function ($cat) {
                $cat->features()->delete();
                foreach ($cat->images as $img) {
                    \Storage::disk('public')->delete($img->image_path);
                    $img->delete();
                }
                $cat->delete();
            });

        // 2. Process each category
        foreach ($request->categories as $categoryData) {
            // Find or create the category
            $surveyCat = SurveyIntCat::firstOrCreate([
                'survey_id' => $survey->id,
                'category_id' => $categoryData['category_id']
            ]);

            // Update features if provided
            if (isset($categoryData['features'])) {
                $surveyCat->features()->delete();
                foreach ($categoryData['features'] as $featureId) {
                    SurveyIntCatFeature::create([
                        'survey_int_cat_id' => $surveyCat->id,
                        'feature_id' => $featureId
                    ]);
                }
            }

            // Handle image replacements if provided
            if (!empty($categoryData['new_images'])) {
                $images = $surveyCat->images()->orderBy('id')->get();
                
                foreach ($categoryData['new_images'] as $newImage) {
                    $position = $newImage['position'];
                    $imageFile = $newImage['image'];
                    
                    // Ensure we have exactly 4 images (create empty slots if needed)
                    while ($images->count() <= $position) {
                        $images->push($surveyCat->images()->create(['image_path' => '']));
                    }
                    
                    // Delete old image if exists
                    $oldImage = $images->get($position);
                    if ($oldImage && $oldImage->image_path) {
                        \Storage::disk('public')->delete($oldImage->image_path);
                    }
                    
                    // Store new image
                    $path = $imageFile->store('survey/interior/categories', 'public');
                    $oldImage->update(['image_path' => $path]);
                }
            }
        }

        DB::commit();

        return response()->json([
            'message' => 'Interior survey updated successfully',
            'data' => InteriorSurveyCategoryResource::collection($survey->interiorCategories)
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'error' => 'Failed to update interior survey',
            'details' => $e->getMessage()
        ], 500);
    }
}


protected function updateExteriorSurvey(Request $request, Survey $survey) 
{
    $request->validate([
        'features' => 'nullable|array',
        'features.*' => 'exists:exterior_features,id',

        'existing_image_ids' => 'nullable|array',
        'existing_image_ids.*' => 'exists:survey_ext_images,id',

        'images' => 'nullable|array',
        'images.*' => 'image|mimes:jpeg,png,jpg|max:2048',

        'replaced_images' => 'nullable|array',
    ]);

    DB::beginTransaction();

    try {
        // --- Sync Features (if provided) ---
        if ($request->has('features')) {
            $survey->exteriorFeatures()->sync($request->features);
        }

        // --- Handle Image Updates Only If Relevant Data Is Provided ---
        if ($request->has('existing_image_ids') || $request->hasFile('replaced_images')) {
            $existingImageIds = $request->get('existing_image_ids', []);
            $replacedImageIds = array_keys($request->file('replaced_images') ?? []);

            $keepImageIds = array_unique(array_merge($existingImageIds, $replacedImageIds));

            // Delete images not kept
            $survey->exteriorImages()->whereNotIn('id', $keepImageIds)->each(function ($img) {
                \Storage::disk('public')->delete($img->image_path);
                $img->delete();
            });
        }

        // --- Replace Existing Images ---
        if ($request->hasFile('replaced_images')) {
            foreach ($request->file('replaced_images') as $imageId => $file) {
                $imageModel = $survey->exteriorImages()->find($imageId);

                if ($imageModel) {
                    // Delete old image
                    \Storage::disk('public')->delete($imageModel->image_path);

                    // Store new image
                    $filename = 'exterior_' . $survey->id . '_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('exterior_images', $filename, 'public');

                    // Update image record
                    $imageModel->update([
                        'image_path' => $path,
                    ]);
                }
            }
        }

        // --- Upload New Images ---
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $filename = 'exterior_' . $survey->id . '_' . time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $path = $image->storeAs('exterior_images', $filename, 'public');

                $survey->exteriorImages()->create([
                    'image_path' => $path,
                ]);
            }
        }

        // Optional: Update survey status
        $survey->update(['status' => 'completed']);

        DB::commit();

        return response()->json([
            'message' => 'Exterior survey updated successfully',
            'data' => new \App\Http\Resources\ExteriorSurveyResource($survey->fresh()),
            'current_status' => $survey->fresh()->status,
        ]);
    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'error' => 'Failed to update exterior survey',
            'details' => $e->getMessage(),
            'trace' => config('app.debug') ? $e->getTrace() : null,
        ], 500);
    }
}

public function deleteSurvey($id)
{
    $survey = Survey::findOrFail($id);
    $survey->delete();

    return response()->json(['message' => 'Survey deleted successfully.']);
}

public function getAllPublicImages()
{
    $imageFolders = [
        'exterior_images',
        'images/exterior_photos',
        'survey/interior/categories',
    ];

    $imageUrls = [];

    foreach ($imageFolders as $folder) {
        // Get all files from the current folder
        $files = Storage::disk('public')->files($folder);

        // Convert each file path to a URL
        $imageUrls[$folder] = array_map(function ($file) {
            return asset('storage/' . $file);
        }, $files);
    }

    return response()->json([
        'status' => 'success',
        'images' => $imageUrls,
    ]);
}

}

