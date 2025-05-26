<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SurveyController;
use App\Http\Controllers\Api\GoogleAuthController;

// Public routes
Route::prefix('auth')->group(function () {
    // Standard Auth
    Route::controller(AuthController::class)->group(function () {
        Route::post('/register', 'register');
        Route::post('/login', 'login');
        Route::post('/verify-email', 'verifyEmail');
        Route::post('/forgot-password', 'forgotPassword');
        Route::post('/verify-otp', 'verifyOtp');
        Route::post('/resend-otp', 'resendOtp');
        Route::middleware('auth:sanctum')->post('/logout', 'logout'); 
        Route::middleware('auth:sanctum')->post('/update-password', 'updatePassword');
        Route::middleware('auth:sanctum')->post('/reset-password', 'resetPassword'); 
    });

    // Google Auth
    Route::controller(GoogleAuthController::class)->group(function () {
        Route::get('/google/callback', 'handleGoogleCallback');
        // Route::post('/google-login', 'googleLogin');
    });
});
Route::post('/auth/google-login', [GoogleAuthController::class, 'googleLogin']);

Route::middleware('auth:sanctum')->post('/profile', [AuthController::class, 'updateProfile']);
// Protected routes (Sanctum authenticated)
Route::middleware('auth:sanctum')->group(function () {
    // User routes
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/surveys/incomplete', [SurveyController::class, 'getIncompleteSurveys']);
    // Survey Management
    Route::prefix('surveys')->controller(SurveyController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{survey}', 'show');
        
        // Photos
        Route::post('/exterior-photos', 'uploadExteriorPhotos');
        
        // Interior
        Route::get('/interior/categories', 'getInteriorCategories');
        Route::get('/interior/category-features', 'getInteriorCategoriesFeatures');
        Route::post('/{survey}/interior', 'saveInteriorSurvey');
        
        //Exterior Features
        Route::get('/exterior/features', 'getExteriorFeatures');
        Route::post('/{survey}/exterior', 'saveExteriorSurvey');
        // Completion
        Route::post('/{survey}/complete', 'completeSurvey');
        // survey status
        Route::get('/status/{surveyId}', 'getSurveyStatus');

        //update and delete survey
        Route::post('/update-survey/{surveyId}', 'updateSurvey');
        Route::delete('/delete-survey/{surveyId}', 'deleteSurvey');

       Route::get('/single-survey/{surveyId}', 'getSurveyById');
       
    });
});

Route::get('/public-images', [SurveyController::class, 'getAllPublicImages']);

Route::middleware('auth:sanctum')->get('/user/profile', [AuthController::class, 'getProfile']);
Route::middleware('auth:sanctum')->get('/completed-surveys', [SurveyController::class, 'getCompletedSurveys']);




