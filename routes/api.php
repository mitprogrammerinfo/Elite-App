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
        Route::middleware('auth:sanctum')->post('/logout', 'logout'); 
        Route::middleware('auth:sanctum')->post('/reset-password', 'resetPassword'); 
    });

    // Google Auth
    Route::controller(GoogleAuthController::class)->group(function () {
        Route::get('/google', 'redirectToGoogle');
        Route::get('/google/callback', 'handleGoogleCallback');
    });
});
//Route::middleware('auth:sanctum')->get('/exterior/features', [SurveyController::class, 'getExteriorFeatures']);

// Protected routes (Sanctum authenticated)
Route::middleware('auth:sanctum')->group(function () {
    // User routes
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

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
    });
});


