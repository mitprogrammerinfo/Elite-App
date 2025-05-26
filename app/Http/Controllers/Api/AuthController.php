<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name'     => 'required|string|max:255',
            'last_name'      => 'required|string|max:255',
            'email'          => 'required|email|unique:users,email',
            'password'       => 'required|string|min:6|confirmed',
            'phone_number'   => 'required|string|max:15',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'required|string|max:255',
            'city'           => 'required|string|max:255',
            'zip'            => 'required|string|max:10',
            'state'          => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $verificationCode = rand(1000, 9999);

        $user = User::create([
            'first_name'        => $request->first_name,
            'last_name'        => $request->last_name,
            'email'             => $request->email,
            'password'          => Hash::make($request->password),
            'phone_number'      => $request->phone_number,
            'address_line_1'    => $request->address_line_1,
            'address_line_2'    => $request->address_line_2,
            'city'              => $request->city,
            'zip'               => $request->zip,
            'state'             => $request->state,
            'verification_code' => $verificationCode,
        ]);

        // Send email
        Mail::raw("Your verification code is: $verificationCode", function ($message) use ($user) {
            $message->to($user->email)
                    ->subject('Email Verification Code');
        });
        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'message' => 'User registered successfully. Please check your email for the verification code.',
            'access_token' => $token,
            'user_id' => $user->id
        ]);
    }

    public function verifyEmail(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'code'    => 'required|string',
        ]);

        $user = User::find($request->user_id);

        if ($user->verification_code === $request->code) {
            $user->email_verified_at = now();
            $user->verification_code = null;
            $user->save();
            return response()->json([
                'message' => 'Email verified and user logged in',
            ]);
        }

        return response()->json(['message' => 'Invalid verification code.'], 400);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();
      

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if (!$user->email_verified_at) {
            return response()->json(['message' => 'Email not verified. Please verify your email first.'], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message'      => 'Login successful',
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => new UserResource(($user))
        ]);
    }

    public function resendOtp(Request $request)
{
    $request->validate([
        'email' => 'required|email|exists:users,email',
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json(['message' => 'User not found with this email address.'], 404);
    }

    if ($user->email_verified_at) {
        return response()->json(['message' => 'Email is already verified.'], 400);
    }

    // Generate new verification code
    $verificationCode = rand(1000, 9999);
    $user->verification_code = $verificationCode;
    $user->save();

    // Send email with new code
    Mail::raw("Your new verification code is: $verificationCode", function ($message) use ($user) {
        $message->to($user->email)
                ->subject('New Email Verification Code');
    });

    return response()->json([
        'message' => 'New OTP sent successfully. Please check your email.',
        'user_id' => $user->id
    ]);
}
   // No Use
    public function forgotPasswordTokenBase(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'We couldn\'t find an account with that email.'], 404);
        }

        $resetToken = Str::random(60);
        $user->reset_token = $resetToken;
        $user->reset_token_expires_at = now()->addHour();
        $user->save();

        $resetUrl = url("/reset-password?token=$resetToken");

        Mail::raw("Click this link to reset your password: $resetUrl", function ($message) use ($user) {
            $message->to($user->email)
                    ->subject('Password Reset Request');
        });

        return response()->json(['message' => 'Password reset link sent to your email.']);
    }
   // No Use
    public function resetPasswordTokenBase(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token'    => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('reset_token', $request->token)
                    ->where('reset_token_expires_at', '>', now())
                    ->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid or expired token.'], 400);
        }

        $user->password = Hash::make($request->password);
        $user->reset_token = null;
        $user->reset_token_expires_at = null;
        $user->save();

        return response()->json(['message' => 'Password reset successfully.']);
    }

    public function updatePassword(Request $request)
{
    $user = $request->user(); 

    if (!$user) {
        return response()->json(['message' => 'Unauthenticated.'], 401);
    }

    // Validate input
    $validator = Validator::make($request->all(), [
        'current_password' => 'required|string',
        'new_password' => 'required|string|min:6|confirmed',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Check current password
    if (!Hash::check($request->current_password, $user->password)) {
        return response()->json(['message' => 'Current password is incorrect.'], 400);
    }

    // Update to new password
    $user->password = Hash::make($request->new_password);
    $user->save();

    // Optional: revoke all old tokens and issue a new one
    $user->tokens()->delete();
    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'message' => 'Password updated successfully.',
        'access_token' => $token,
        'token_type' => 'Bearer',
    ]);
}

    public function forgotPassword(Request $request)
{
    $request->validate([
        'email' => 'required|email',
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json(['message' => 'We couldn\'t find an account with that email.'], 404);
    }

    $otp = rand(1000, 9999); // 4-digit code

    $user->verification_code = $otp;
    $user->verification_code_expires_at = now()->addMinutes(10); // Optional
    $user->save();

    Mail::raw("Your password reset code is: $otp", function ($message) use ($user) {
        $message->to($user->email)->subject('Password Reset Code');
    });

    return response()->json(['message' => 'OTP sent to your email.']);
} 


public function verifyOtp(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'code'  => 'required|string',
    ]);

    $user = User::where('email', $request->email)
                ->where('verification_code', $request->code)
                ->where('verification_code_expires_at', '>', now())
                ->first();

    if (!$user) {
        return response()->json(['message' => 'Invalid or expired OTP.'], 400);
    }

    // Clear the OTP fields
    $user->verification_code = null;
    $user->verification_code_expires_at = null;
    $user->save();

    // Create a password reset token (this is a Sanctum token)
    $token = $user->createToken('password_reset_token')->plainTextToken;

    return response()->json([
        'message' => 'OTP verified. You can now reset your password.',
        'access_token' => $token,
        'token_type' => 'Bearer',
    ]);
}

public function resetPassword(Request $request)
{
    $request->validate([
        'password' => 'required|string|min:6|confirmed',
    ]);

    // Get authenticated user using the token
    $user = $request->user();
    
    if (!$user) {
        return response()->json(['message' => 'Unauthorized or token expired'], 401);
    }

    // Update password
    $user->password = Hash::make($request->password);
    $user->save();

    // Revoke all tokens (optional - you might want to keep the user logged in)
    $user->tokens()->delete();

    return response()->json(['message' => 'Password reset successfully.']);
}
public function logout(Request $request)
{
    $user = $request->user();
    
    if (!$user) {
        return response()->json(['message' => 'No user found'], 400);
    }

    $user->tokens->each(function ($token) {
        $token->delete();
    });

    return response()->json(['message' => 'Successfully logged out']);
}

public function updateProfile(Request $request)
{
    $user = $request->user();
    if (!$user) {
        return response()->json(['message' => 'Unauthenticated'], 401);
    }

    $validator = Validator::make($request->all(), [
        'first_name' => 'sometimes|string|max:255|nullable',
        'last_name' => 'sometimes|string|max:255|nullable',
        'email' => 'sometimes|email|unique:users,email,'.$user->id,
        'phone_number' => 'sometimes|string|max:15|nullable',
        'avatar' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Get only filled fields (ignore nulls)
    $data = array_filter($request->only([
       'first_name', 'last_name', 'email', 'phone_number'
    ]), function($value) {
        return $value !== null;
    });

   
    if ($request->hasFile('avatar')) {
        $avatar = $request->file('avatar');
        $filename = 'avatar_'.$user->id.'_'.time().'.'.$avatar->getClientOriginalExtension();
        $path = $avatar->storeAs('images/avatars', $filename, 'public');
        $data['avatar'] = $path;
        
        // Delete old avatar if exists
        if ($user->avatar && !str_contains($user->avatar, 'ui-avatars.com')) {
            Storage::disk('public')->delete($user->avatar);
        }
    }

    $user->update($data);

    return response()->json([
        'message' => 'Profile updated successfully',
        'user' => new UserResource(($user))
    ]);
}

public function getProfile(Request $request)
{
    $user = $request->user();

    if (!$user) {
        return response()->json(['message' => 'Unauthenticated'], 401);
    }

    $user->loadCount([
        'surveys as completed_surveys' => fn ($q) => $q->where('status', 'completed'),
        'surveys as incompleted_surveys' => fn ($q) => $q->where('status', '!=', 'completed'),
    ]);
    
    $completedSurveys = $user->completed_surveys;
    $incompletedSurveys = $user->incompleted_surveys;

    return response()->json([
        'message' => 'Fetched Profile data successfully',
        'completed_surveys' => $completedSurveys,
        'incompleted_surveys' => $incompletedSurveys,
        'total_surveys' => $completedSurveys + $incompletedSurveys,
        'user' => new UserResource(($user))
    ]);
}

}
