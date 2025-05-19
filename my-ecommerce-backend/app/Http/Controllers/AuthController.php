<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Twilio\Rest\Client;
// use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Facades\JWTAuth;





class AuthController extends Controller
{
    // ✅ Send OTP
//     public function sendOtp(Request $request)
//     {
//         $validator = Validator::make($request->all(), [
//             'phone' => 'required|digits:10',
//         ]);

//         if ($validator->fails()) {
//             return response()->json(['error' => $validator->errors()], 400);
//         }

//         $phone = $request->phone;

//         // Find or create user by phone number
//         $user = User::firstOrCreate(['phone' => $phone]);

//         // Generate 6-digit OTP
//         $otp = rand(100000, 999999);

//         // Save OTP with expiration time (5 minutes validity)
//         $user->otp = $otp;
//         $user->otp_expires_at = Carbon::now()->addMinutes(5);
//         $user->save();

//         // Simulate OTP sending (use SMS service like Twilio in production)
//         // Twilio/Msg91 API integration code goes here
//         // For now, just return OTP in response for testing
//         return response()->json([
//             'message' => 'OTP sent successfully!',
//             'otp' => $otp  // Remove this in production
//         ]);
//     }

// public function verifyOtp(Request $request)
// {
//     $user = User::where('phone', $request->phone)
//                 ->where('otp', $request->otp)
//                 ->where('otp_expires_at', '>', now())
//                 ->first();

//     if (!$user) {
//         return response()->json(['message' => 'Invalid or expired OTP'], 400);
//     }

//     // ✅ Generate the JWT token here after OTP verification
//     $token = JWTAuth::fromUser($user);

//     return response()->json([
//         'message' => 'OTP verified successfully',
//         'token' => $token,  // Include the token in the response
//         'registered' => $user->name && $user->email ? true : false
//     ], 200);
// }

public function sendOtp(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email',
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 400);
    }

    $email = $request->email;

    // Find or create user by email
    $user = User::firstOrCreate(['email' => $email]);

    // Generate 6-digit OTP
    $otp = rand(100000, 999999);

    // Save OTP with expiration time (5 minutes validity)
    $user->otp = $otp;
    $user->otp_expires_at = Carbon::now()->addMinutes(5);
    $user->save();

    // Send OTP to user's email
    try {
        Mail::raw("Your OTP code is: {$otp}", function ($message) use ($email) {
            $message->to($email)
                    ->subject('Your OneButton OTP Code');
        });

        return response()->json(['message' => 'OTP sent to your email successfully!']);
    } catch (\Exception $e) {
        \Log::error('Failed to send OTP email: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to send OTP email. Please try again later.'], 500);
    }
}


public function verifyOtp(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'otp' => 'required|digits:6',
    ]);

    $user = User::where('email', $request->email)
                ->where('otp', $request->otp)
                ->where('otp_expires_at', '>', now())
                ->first();

    if (!$user) {
        return response()->json(['message' => 'Invalid or expired OTP'], 400);
    }

    // OTP verified: generate JWT token
    $token = JWTAuth::fromUser($user);

    return response()->json([
        'message' => 'OTP verified successfully',
        'token' => $token,
        'registered' => $user->name ? true : false
    ], 200);
}



public function register(Request $request)
{
    $request->validate([
        'email' => 'required|email|exists:users,email',
        'name' => 'required|string|max:255',
        'phone' => 'required|digits:10|unique:users,phone',
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json(['message' => 'User not found'], 404);
    }

    $user->name = $request->name;
    $user->phone = $request->phone;
    $user->save();

    return response()->json(['message' => 'User registered successfully']);
}

public function checkUser(Request $request)
{
    // 🔄 Now using email instead of phone to find the user
    $user = User::where('email', $request->email)->first();

    if ($user) {
        // ✅ Check if the user is fully registered (name and phone must be present)
        $isRegistered = !empty($user->name) && !empty($user->phone);

        return response()->json([
            'registered' => $isRegistered,
            'user' => $user
        ]);
    }

    // ❌ User not found
    return response()->json(['registered' => false]);
}


public function destroy($id){

    $user = User::find($id);

    if (!$user) {
        return response()->json(['message' => 'user not found'], 404);
    }

    $user->delete();

    return response()->json(['message' => 'user deleted successfully'], 200);

}


public function getUserProfile(Request $request){
    try {
        $user = JWTAuth::parseToken()->authenticate();
        
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json([
            'id' => $user->id,
            'phone' => $user->phone,
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ], 200);

    } catch (TokenExpiredException $e) {
        return response()->json(['error' => 'Token expired'], 401);
        
    } catch (TokenInvalidException $e) {
        return response()->json(['error' => 'Invalid token'], 401);
        
    } catch (JWTException $e) {
        return response()->json(['error' => 'Token missing or invalid'], 401);
    }
}


public function getUserToken(){
    try {
        $user = JWTAuth::parseToken()->authenticate();
        
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json([
            'id' => $user->id,
            'phone' => $user->phone,
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ], 200);

    } catch (TokenExpiredException $e) {
        return response()->json(['error' => 'Token expired'], 401);
        
    } catch (TokenInvalidException $e) {
        return response()->json(['error' => 'Invalid token'], 401);
        
    } catch (JWTException $e) {
        return response()->json(['error' => 'Token missing or invalid'], 401);
    }
}


  public function getAllUsers(){

    $Users = User::all();

    return response()->json([$Users]);

  }




}






