<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Admin;
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
        Mail::raw("Your OTP code is: {$otp}", function ($message) use ($admin) {
            $message->to($admin->email)
                    ->subject('Your Admin OTP Code');
        });

        return response()->json(['message' => 'OTP sent to your email successfully!'], 200);
    } catch (\Exception $e) {
        \Log::error('Failed to send OTP email to admin: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to send OTP email. Please try again later.'], 500);
    }
}


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

    // Generate and return JWT
    $token = JWTAuth::fromUser($admin);

    return response()->json(['token' => $token], 200);
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

