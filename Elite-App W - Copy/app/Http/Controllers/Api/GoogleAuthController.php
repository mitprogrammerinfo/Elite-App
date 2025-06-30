<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http; // For verifying token with Google (optional but recommended)

class GoogleAuthController extends Controller
{
    /**
     * Get Google OAuth URL
     * @return \Illuminate\Http\JsonResponse
     */


     
     
     public function googleLogin(Request $request)
     {
         $validator = Validator::make($request->all(), [
             'id' => 'required|string',
             'email' => 'required|email',
             'name' => 'required|string',
             'photoUrl' => 'nullable|url|regex:/googleusercontent\.com/',
         ]);
     
         if ($validator->fails()) {
             return response()->json([
                 'message' => 'Validation error',
                 'errors' => $validator->errors()
             ], 422);
         }
     
         $user = User::updateOrCreate(
             ['email' => $request->email],
             [
                 'first_name' => $request->name,
                 'google_id' => $request->id,
                 'avatar' => $request->photoUrl,
                 'password' => bcrypt(Str::random(24)), 
                 'email_verified_at' => now(),
             ]
         );
     
         $token = $user->createToken('auth_token')->plainTextToken;
     
         return response()->json([
             'message' => 'User successfully logged in',
             'token' => $token,
             'user' => new UserResource($user),
         ]);
     }
     
     

     // No use of below All in the Elite App
    public function redirectToGoogle()
    {        
        return Socialite::driver('google')->redirect();  
    }

    /**
     * Handle Google callback and return API token
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            if (!$googleUser->getEmail()) {
                throw new \Exception('Google account email is required');
            }

            $user = $this->findOrCreateUser($googleUser);
            $token = $this->createAuthToken($user);

            return response()->json([
                'token' => $token,
                'user' => $this->formatUserResponse($user)
            ]);

        } catch (\Exception $e) {
            Log::error('Google auth failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'authentication_failed',
                'message' => $e->getMessage()
            ], 401);
        }
    }

    /**
     * Find or create user from Google data
     */
    protected function findOrCreateUser($googleUser): User
    {
        return User::updateOrCreate(
            ['email' => $googleUser->getEmail()],
            [
                'first_name' => $googleUser->getName() ?? $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'password' => bcrypt(Str::random(24)),
                'email_verified_at' => now()
            ]
        );
    }

    /**
     * Create API token for user
     */
    protected function createAuthToken(User $user): string
    {
        return $user->createToken('auth_token')->plainTextToken;
    }

    /**
     * Format user response data
     */
    protected function formatUserResponse(User $user): array
    {
        return (new UserResource($user))->toArray(request());
        // [
        //     'id' => $user->id,
        //     'name' => $user->name,
        //     'email' => $user->email,
        //     'avatar_url' => $user->avatar_url
        // ];
    }

    /**
     * Logout user (revoke tokens)
     */
    public function logout()
    {
        Auth::user()->currentAccessToken()->delete();
        
        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }
}