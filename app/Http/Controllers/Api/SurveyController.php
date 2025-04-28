<?php

namespace App\Http\Controllers\Api;
use App\Models\ExteriorFeature;
use App\Models\ExteriorSurvey;
use App\Models\InteriorFeature;
use App\Models\SurveyIntCat;
use App\Models\SurveyIntCatFeature;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Survey;
use App\Models\InteriorCategory;
use App\Models\SurveyInterior;
use Illuminate\Support\Facades\Auth;
use DB;
use Log;


class SurveyController extends Controller
{
    public function store(Request $request)
{

    $userId = Auth::id();

    if (!$userId) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $survey = Survey::create([
        'user_id' => $userId,
        'status' => 'exterior_photos', 
    ]);

    return response()->json([
        'message' => 'Survey created successfully',
        'survey_id' => $survey->id,
    ]);
}



public function uploadExteriorPhotos(Request $request, Survey $survey)
{
    $userId = Auth::id();

    if (!$userId) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }
    $request->validate([
        'photos' => 'required|array|size:6',
        'photos.*' => 'image|mimes:jpeg,png,jpg'
    ]);
    $survey = Survey::create([
        'user_id' => $userId,
        'status' => 'exterior_photos',
    ]);

    

    $photoLabels = [
        'address',
        'front_elevation',
        'right_elevation',
        'left_elevation',
        'rear',
        'roof'
    ];

    $uploadedPhotos = [];

    foreach ($request->file('photos') as $index => $photo) {
        $label = $photoLabels[$index];
        $filename = $label . '_' . time() . '.' . $photo->getClientOriginalExtension();
        $path = $photo->storeAs('images/exterior_photos', $filename, 'public');

        $photoModel = $survey->exteriorPhotos()->create([
            'image_path' => $path,
            'label' => $label
        ]);

        $uploadedPhotos[] = [
            'label' => $label,
            'image_path' => $path
        ];
    }

    // Move survey to next stage
    $survey->update(['status' => 'interior_selections']);

    return response()->json([
        'message' => 'Photos uploaded successfully',
        'survey_id' => $survey->id,
        'photos' => $uploadedPhotos
    ]);
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
        'categories.*.features' => 'required|array|min:1',
        'categories.*.features.*' => 'required|exists:interior_features,id',
    ]);

    DB::beginTransaction();
    try {
        $uploadedData = [
            'survey_id' => $survey->id,
            'categories' => [],
        ];

        foreach ($request->categories as $categoryData) {
            $surveyCategory = SurveyIntCat::create([
                'survey_id' => $survey->id, // Directly use survey_id
                'category_id' => $categoryData['category_id']
            ]);

            $images = [];
            foreach ($categoryData['images'] as $image) {
                $path = $image->store('survey/interior/categories', 'public');
                $surveyCategory->images()->create(['image_path' => $path]);
                $images[] = $path;
            }

            $features = [];
            foreach ($categoryData['features'] as $featureId) {
                SurveyIntCatFeature::create([
                    'survey_int_cat_id' => $surveyCategory->id,
                    'feature_id' => $featureId
                ]);
                $features[] = $featureId;
            }

            $uploadedData['categories'][] = [
                'category_id' => $categoryData['category_id'],
                'images' => $images,
                'features' => $features,
            ];
        }

        DB::commit();
        $survey->update(['status' => 'exterior_features']);

        return response()->json([
            'message' => 'Interior survey saved successfully',
            'data' => $uploadedData
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
        'features' => 'required|array',
        'features.*' => 'exists:exterior_features,id',
        'images' => 'required|array|min:1',
        'images.*' => 'image|mimes:jpeg,png,jpg|max:2048',
    ]);

    DB::beginTransaction();

    try {
        // Associate features directly with survey
        $survey->exteriorFeatures()->sync($request->features);

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
            'survey_id' => $survey->id,
            'selected_features' => $request->features,
            'uploaded_images' => $uploadedImages,
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



    public function completeSurvey(Request $request, Survey $survey)
    {
        $request->validate([
            'feature_ids' => 'required|array|size:13',
            'feature_ids.*' => 'exists:exterior_features,id'
        ]);

        foreach ($request->feature_ids as $featureId) {
            $survey->exteriorSelections()->create(['feature_id' => $featureId]);
        }

        $survey->update(['status' => 'completed']);

        return response()->json(['message' => 'Survey completed successfully']);
    }
}

