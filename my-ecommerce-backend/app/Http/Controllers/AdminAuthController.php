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

//     // Generate short-lived access token (2 hours)
//     $accessToken = auth()->setTTL(120)->fromUser($admin);

//     // Generate long-lived refresh token (5 days = 7200 minutes)
//     $refreshToken = auth()->setTTL(7200)->claims(['type' => 'refresh'])->fromUser($admin);

//     // Set refresh token as httpOnly, secure cookie
//     $cookie = cookie(
//         'refresh_token',   // name
//         $refreshToken,     // value
//         7200,              // minutes (5 days)
//         '/',               // path
//         null,              // domain (null = current)
//         true,              // secure (HTTPS only)
//         true,              // httpOnly
//         false,             // raw
//         'Strict'           // sameSite
//     );

//     // Return both: access token + refresh token (cookie)
//     return response()->json([
//         'access_token' => $accessToken,
//         'message' => 'OTP verified successfully',
//     ], 200)->withCookie($cookie);
// }

    // -------------------------------
    // ✅ VERIFY OTP & ISSUE TOKENS
    // -------------------------------
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|digits:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $admin = Admin::where('email', $request->email)
            ->where('otp', $request->otp)
            ->where('otp_expires_at', '>=', Carbon::now())
            ->first();

        if (!$admin) {
            return response()->json(['error' => 'Invalid or expired OTP'], 401);
        }

        // ✅ Clear OTP after successful verification
        $admin->otp = null;
        $admin->otp_expires_at = null;
        $admin->save();

        // ✅ Access token — short-lived (2 hours)
        $accessToken = auth()->setTTL(120)->fromUser($admin);

        // ✅ Refresh token — long-lived (5 days)
        $refreshToken = auth()->setTTL(60 * 24 * 5)->claims(['type' => 'refresh'])->fromUser($admin);

        // ✅ Environment check for secure cookies
        $isSecure = app()->environment('production');

        // ✅ Persistent cookie (5 days)
        $cookie = cookie(
    'refresh_token',
    $refreshToken,
    7200, // 5 days
    '/', 
    null, // domain
    false, // ❌ not secure for localhost
    true,  // HttpOnly
    false,
    'Strict'
);


        return response()->json([
            'access_token' => $accessToken,
            'message' => 'OTP verified successfully',
        ], 200)->withCookie($cookie);
    }

    // -------------------------------
    // ✅ REFRESH TOKEN ENDPOINT
    // -------------------------------
public function refreshToken(Request $request)
{
    try {
        $refreshToken = $request->cookie('refresh_token');
        if (!$refreshToken) {
            return response()->json(['error' => 'No refresh token found'], 401);
        }

        // ✅ Decode token payload manually (don't try to "login" with it)
        $payload = JWTAuth::setToken($refreshToken)->getPayload();

        // ✅ Check if it's a refresh token (not an access token)
        if ($payload->get('type') !== 'refresh') {
            return response()->json(['error' => 'Invalid token type'], 401);
        }

        // ✅ Get the user ID from token payload
        $adminId = $payload->get('sub');
        $admin = \App\Models\Admin::find($adminId);

        if (!$admin) {
            return response()->json(['error' => 'User not found'], 401);
        }

        // ✅ Issue a new short-lived access token (2 hours)
        $newAccessToken = auth()->setTTL(120)->fromUser($admin);

        // ✅ Rotate a new refresh token (valid for 5 days)
        $newRefreshToken = auth()->setTTL(7200)->claims(['type' => 'refresh'])->fromUser($admin);

        // ✅ Create a new cookie (not secure for localhost)
        $cookie = cookie(
            'refresh_token',
            $newRefreshToken,
            7200, // minutes (5 days)
            '/',
            null,
            false, // secure=false for localhost
            true,  // httpOnly
            false,
            'Strict'
        );

        return response()->json([
            'access_token' => $newAccessToken,
            'message' => 'Token refreshed successfully'
        ])->withCookie($cookie);

    } catch (TokenExpiredException $e) {
        return response()->json(['error' => 'Refresh token expired'], 401);
    } catch (TokenInvalidException $e) {
        return response()->json(['error' => 'Invalid refresh token'], 401);
    } catch (JWTException $e) {
        return response()->json(['error' => 'Token refresh failed'], 401);
    }
}






    // **3. Logout Admin**
    public function logout(Request $request) {
        JWTAuth::invalidate(JWTAuth::getToken());
        return response()->json(['message' => 'Admin logged out successfully']);
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

