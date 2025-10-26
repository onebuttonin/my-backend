<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Auth;


class AdminAuthController extends Controller {
    // **1. Admin Login - Generate OTP**

    // public function login(Request $request) {
    //     $validator = Validator::make($request->all(), [
    //         'email' => 'required|email',
    //         'password' => 'required|string|min:6',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['error' => $validator->errors()], 422);
    //     }

    //     $admin = Admin::where('email', $request->email)->first();

    //     if (!$admin || !Hash::check($request->password, $admin->password)) {
    //         return response()->json(['error' => 'Invalid credentials'], 401);
    //     }

    //     // Generate OTP
    //     $otp = rand(100000, 999999);
    //     $admin->otp = $otp;
    //     $admin->otp_expires_at = Carbon::now()->addMinutes(10);
    //     $admin->save();

    //     // Send OTP (Example: Email)
    //     Mail::raw("Your OTP code is: $otp", function ($message) use ($admin) {
    //         $message->to($admin->email)
    //             ->subject("Admin Login OTP");
    //     });

    //     return response()->json(['message' => 'OTP sent to your email.'], 200);
    // }

    // **2. Verify OTP and Get JWT Token**
    // public function verifyOTP(Request $request) {
    //     $validator = Validator::make($request->all(), [
    //         'email' => 'required|email',
    //         'otp' => 'required|digits:6',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['error' => $validator->errors()], 422);
    //     }

    //     $admin = Admin::where('email', $request->email)
    //                   ->where('otp', $request->otp)
    //                   ->where('otp_expires_at', '>=', Carbon::now())
    //                   ->first();

    //     if (!$admin) {
    //         return response()->json(['error' => 'Invalid or expired OTP'], 401);
    //     }

    //     // Clear OTP after successful login
    //     $admin->otp = null;
    //     $admin->otp_expires_at = null;
    //     $admin->save();

    //     // Generate JWT token
    //     $token = JWTAuth::fromUser($admin);

    //     return response()->json(['token' => $token], 200);
    // }


    public function login(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email',
        'password' => 'required|string|min:6',
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 422);
    }

    // Authenticate admin
    $admin = Admin::where('email', $request->email)->first();
    if (!$admin || !Hash::check($request->password, $admin->password)) {
        return response()->json(['error' => 'Invalid credentials'], 401);
    }

    // Generate OTP
    $otp = rand(100000, 999999);
    $admin->otp = $otp;
    $admin->otp_expires_at = Carbon::now()->addMinutes(10);
    $admin->save();

    // Send OTP via email
    try {
        Mail::raw("Your OTP code for Admin login is: {$otp}", function ($message) use ($admin) {
            $message->to($admin->email)
                    ->subject('Your Admin OTP Code');
        });

        return response()->json(['message' => 'OTP sent to your email successfully!'], 200);
    } catch (\Exception $e) {
        \Log::error('Failed to send OTP email to admin: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to send OTP email. Please try again later.'], 500);
    }
}


// public function verifyOtp(Request $request)
// {
//     $validator = Validator::make($request->all(), [
//         'email' => 'required|email',
//         'otp' => 'required|digits:6',
//     ]);

//     if ($validator->fails()) {
//         return response()->json(['error' => $validator->errors()], 422);
//     }

//     // Find matching admin with valid OTP
//     $admin = Admin::where('email', $request->email)
//                   ->where('otp', $request->otp)
//                   ->where('otp_expires_at', '>=', Carbon::now())
//                   ->first();

//     if (!$admin) {
//         return response()->json(['error' => 'Invalid or expired OTP'], 401);
//     }

//     // Clear OTP after verification
//     $admin->otp = null;
//     $admin->otp_expires_at = null;
//     $admin->save();

//     // Generate and return JWT
//     // $token = JWTAuth::fromUser($admin);
//       $token = auth()->setTTL(7200)->fromUser($admin);

//     return response()->json(['token' => $token], 200);
// }

public function verifyOtp(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email',
        'otp' => 'required|digits:6',
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 422);
    }

    // Find matching admin with valid OTP
    $admin = Admin::where('email', $request->email)
                  ->where('otp', $request->otp)
                  ->where('otp_expires_at', '>=', Carbon::now())
                  ->first();

    if (!$admin) {
        return response()->json(['error' => 'Invalid or expired OTP'], 401);
    }

    // Clear OTP after verification
    $admin->otp = null;
    $admin->otp_expires_at = null;
    $admin->save();

    // Generate short-lived access token (2 hours)
    $accessToken = auth()->setTTL(120)->fromUser($admin);

    // Generate long-lived refresh token (5 days = 7200 minutes)
    $refreshToken = auth()->setTTL(7200)->claims(['type' => 'refresh'])->fromUser($admin);

    // Set refresh token as httpOnly, secure cookie
    $cookie = cookie(
        'refresh_token',   // name
        $refreshToken,     // value
        7200,              // minutes (5 days)
        '/',               // path
        null,              // domain (null = current)
        true,              // secure (HTTPS only)
        true,              // httpOnly
        false,             // raw
        'Strict'           // sameSite
    );

    // Return both: access token + refresh token (cookie)
    return response()->json([
        'access_token' => $accessToken,
        'message' => 'OTP verified successfully',
    ], 200)->withCookie($cookie);
}




    // **3. Logout Admin**
    public function logout(Request $request) {
        JWTAuth::invalidate(JWTAuth::getToken());
        return response()->json(['message' => 'Admin logged out successfully']);
    }


// AuthController.php
public function refreshToken(Request $request)
    {
        try {
            // Get refresh token from httpOnly cookie
            $refreshToken = $request->cookie('refresh_token');

            if (!$refreshToken) {
                return response()->json(['error' => 'No refresh token found'], 401);
            }

            // Try authenticating using refresh token
            $admin = auth()->setToken($refreshToken)->user();

            if (!$admin) {
                return response()->json(['error' => 'Invalid refresh token'], 401);
            }

            // ✅ Issue new short-lived access token (2 hours)
            $newAccessToken = auth()->setTTL(120)->fromUser($admin);

            // Optionally — issue a fresh refresh token (for rotation)
            $newRefreshToken = auth()->setTTL(7200)->fromUser($admin); // 5 days
            $cookie = cookie(
                'refresh_token',
                $newRefreshToken,
                7200 * 60, // minutes
                null,
                null,
                true,  // secure
                true   // httpOnly
            );

            return response()->json([
                'access_token' => $newAccessToken,
            ])->withCookie($cookie);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Token refresh failed'], 401);
        }
    }


    


public function getAdminProfile()
{
    try {
        // Ensure we use the 'admin' guard
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['error' => 'Admin not found'], 404);
        }

        return response()->json([
            'id' => $admin->id,
            'name' => $admin->name,
            'email' => $admin->email,
            'created_at' => $admin->created_at,
            'updated_at' => $admin->updated_at,
        ], 200);
        
    } catch (TokenExpiredException $e) {
        return response()->json(['error' => 'Token expired'], 401);

    } catch (TokenInvalidException $e) {
        return response()->json(['error' => 'Invalid token'], 401);

    } catch (JWTException $e) {
        return response()->json(['error' => 'Token missing or invalid'], 401);
    }
}




}

