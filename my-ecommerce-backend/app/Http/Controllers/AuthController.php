<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Twilio\Rest\Client;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cookie;
use Tymon\JWTAuth\Facades\JWTFactory;
use Tymon\JWTAuth\Token;
use Illuminate\Validation\Rule;


class AuthController extends Controller
{
  

// public function sendOtp(Request $request)
// {
//     $validator = Validator::make($request->all(), [
//         'email' => 'required|email',
//     ]);

//     if ($validator->fails()) {
//         return response()->json(['error' => $validator->errors()], 400);
//     }

//     $email = $request->email;

//     // Find or create user by email
//     $user = User::firstOrCreate(['email' => $email]);

//     // Generate 6-digit OTP
//     $otp = rand(1000, 9999);

//     // Save OTP with expiration time (5 minutes validity)
//     $user->otp = $otp;
//     $user->otp_expires_at = Carbon::now()->addMinutes(5);
//     $user->save();

//     // Send OTP to user's email
//     try {
//         Mail::raw("Your OTP code is: {$otp}", function ($message) use ($email) {
//             $message->to($email)
//                     ->subject('Your OneButton OTP Code');
//         });

//         return response()->json(['message' => 'OTP sent to your email successfully!']);
//     } catch (\Exception $e) {
//         \Log::error('Failed to send OTP email: ' . $e->getMessage());
//         return response()->json(['error' => 'Failed to send OTP email. Please try again later.'], 500);
//     }
// }


// public function verifyOtp(Request $request)
// {
//     $request->validate([
//         'email' => 'required|email',
//         'otp' => 'required|digits:4',
//     ]);

//     $user = User::where('email', $request->email)
//                 ->where('otp', $request->otp)
//                 ->where('otp_expires_at', '>', now())
//                 ->first();

//     if (!$user) {
//         return response()->json(['message' => 'Invalid or expired OTP'], 400);
//     }

//     // OTP verified: generate JWT token
//     $token = JWTAuth::fromUser($user);

//     return response()->json([
//         'message' => 'OTP verified successfully',
//         'token' => $token,
//         'registered' => $user->name ? true : false
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

    // Normalize email to avoid case/space mismatches
    $email = strtolower(trim($request->email));

    // Find or create user by email
    $user = User::firstOrCreate(['email' => $email]);

    // Generate 4-digit OTP
    $otp = (string) random_int(1000, 9999); // keep as string

    // Save OTP with expiration time (5 minutes)
    $user->otp = $otp;
  $user->otp_expires_at = Carbon::now()->addSeconds(40);
    $user->save();

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


// public function verifyOtp(Request $request)
// {
//     // Normalize email
//     $request->merge(['email' => strtolower(trim($request->input('email')))]);

//     $request->validate([
//         'email' => 'required|email',
//         'otp'   => 'required|digits:4',
//     ]);

//     $user = User::where('email', $request->email)
//                 ->where('otp', $request->otp)              // exact match
//                 ->where('otp_expires_at', '>', now())      // not expired
//                 ->first();

//     if (!$user) {
//         return response()->json(['message' => 'Invalid or expired OTP'], 400);
//     }

//     // OTP verified → clear OTP to prevent reuse
//     $user->otp = null;
//     $user->otp_expires_at = null;
//     $user->save();

//     // Issue JWT
//     $token = JWTAuth::fromUser($user);

//     return response()->json([
//         'message'    => 'OTP verified successfully',
//         'token'      => $token,
//         'registered' => (bool) $user->name,
//         'user'       => $user, // helpful for your UI
//     ], 200);
// }



// public function verifyOtp(Request $request)
// {
//     $validator = Validator::make($request->all(), [
//         'email' => 'required|email',
//         'otp' => 'required|digits:4',
//     ]);

//     if ($validator->fails()) {
//         return response()->json(['error' => $validator->errors()], 422);
//     }

//     $user = User::where('email', $request->email)
//         ->where('otp', $request->otp)
//         ->where('otp_expires_at', '>=', Carbon::now())
//         ->first();

//     if (!$user) {
//         return response()->json(['error' => 'Invalid or expired OTP'], 401);
//     }

//     // ✅ Clear OTP after successful verification
//     $user->otp = null;
//     $user->otp_expires_at = null;
//     $user->save();

//     // ✅ Access token — short-lived (2 hours)
//     $accessToken = auth()->setTTL(120)->fromUser($user);

//     // ✅ Refresh token — long-lived (15 days)
//     $refreshToken = auth()
//         ->setTTL(60 * 24 * 15)
//         ->claims(['type' => 'refresh'])
//         ->fromUser($user);

//     // ✅ Use consistent cookie name for refresh token
//     $isSecure = app()->environment('production');

//     $cookie = cookie(
//         'user_refresh_token', // consistent name
//         $refreshToken,
//         60 * 24 * 15, // 15 days in minutes
//         '/',
//         null,
//         $isSecure, // true for production, false for localhost
//         true,      // HttpOnly
//         false,
//         'Strict'
//     );

//     return response()->json([
//         'access_token' => $accessToken,
//         'message' => 'OTP verified successfully',
//     ], 200)->withCookie($cookie);
// }

// # ------------------------------------------------------
// # ✅ REFRESH TOKEN ENDPOINT
// # ------------------------------------------------------

// public function refreshToken(Request $request)
// {
//     try {
//         $refreshToken = $request->cookie('user_refresh_token'); // same name as above

//         if (!$refreshToken) {
//             return response()->json(['error' => 'No refresh token found'], 401);
//         }

//         // ✅ Decode payload manually
//         $payload = JWTAuth::setToken($refreshToken)->getPayload();

//         // ✅ Ensure it's a refresh token
//         if ($payload->get('type') !== 'refresh') {
//             return response()->json(['error' => 'Invalid token type'], 401);
//         }

//         // ✅ Retrieve user
//         $userId = $payload->get('sub');
//         $user = User::find($userId);

//         if (!$user) {
//             return response()->json(['error' => 'User not found'], 401);
//         }

//         // ✅ Generate new access token (2 hours)
//         $newAccessToken = auth()->setTTL(120)->fromUser($user);

//         // ✅ Rotate new refresh token (15 days)
//         $newRefreshToken = auth()
//             ->setTTL(60 * 24 * 15)
//             ->claims(['type' => 'refresh'])
//             ->fromUser($user);

//         $isSecure = app()->environment('production');

//         // ✅ Replace old cookie with new
//         $cookie = cookie(
//             'user_refresh_token',
//             $newRefreshToken,
//             60 * 24 * 15,
//             '/',
//             null,
//             $isSecure,
//             true,
//             false,
//             'Strict'
//         );

//         return response()->json([
//             'access_token' => $newAccessToken,
//             'message' => 'Token refreshed successfully',
//         ])->withCookie($cookie);

//     } catch (TokenExpiredException $e) {
//         return response()->json(['error' => 'Refresh token expired'], 401);
//     } catch (TokenInvalidException $e) {
//         return response()->json(['error' => 'Invalid refresh token'], 401);
//     } catch (JWTException $e) {
//         return response()->json(['error' => 'Token refresh failed'], 401);
//     }
// }

# ------------------------------------------------------
# ✅ GET USER PROFILE
# ------------------------------------------------------







// 11/4/2025

public function verifyOtp(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email',
        'otp' => 'required|digits:4',
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 422);
    }

    $email = strtolower(trim($request->email));

    $user = User::where('email', $email)
        ->where('otp', $request->otp)
        ->where('otp_expires_at', '>', now())
        ->first();

    if (!$user) {
        return response()->json(['error' => 'Invalid or expired OTP'], 400);
    }

    // Clear OTP
    $user->otp = null;
    $user->otp_expires_at = null;
    $user->save();

    // Tokens
    $accessToken = auth()->setTTL(120)->fromUser($user);
    $refreshToken = auth()
        ->setTTL(60 * 24 * 15)
        ->claims(['type' => 'refresh'])
        ->fromUser($user);

    $isSecure = app()->environment('production');
    $cookieDomain = config('session.domain');

    $cookie = cookie(
        'user_refresh_token',
        $refreshToken,
        60 * 24 * 15,
        '/',
        $cookieDomain,
        $isSecure,
        true,
        false,
        'Lax'
    );

    // ✅ determine registration status
    $isRegistered = !empty($user->name) && !empty($user->phone);

    return response()->json([
        'message' => 'OTP verified successfully',
        'access_token' => $accessToken,
        'registered' => $isRegistered,
        'user' => [
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'phone' => $user->phone,
        ]
    ], 200)->withCookie($cookie);
}

public function refreshToken(Request $request)
{
    try {
        $refreshToken = $request->cookie('user_refresh_token');

        if (!$refreshToken) {
            return response()->json(['error' => 'No refresh token found'], 401);
        }

        // ✅ Decode refresh token payload
        $payload = JWTAuth::setToken($refreshToken)->getPayload();

        if ($payload->get('type') !== 'refresh') {
            return response()->json(['error' => 'Invalid token type'], 401);
        }

        $user = User::find($payload->get('sub'));

        if (!$user) {
            return response()->json(['error' => 'User not found'], 401);
        }

        // ✅ New access & refresh tokens
        $newAccessToken = auth()->setTTL(120)->fromUser($user);
        $newRefreshToken = auth()
            ->setTTL(60 * 24 * 15)
            ->claims(['type' => 'refresh'])
            ->fromUser($user);

        $isSecure = app()->environment('production');
        $cookieDomain = config('session.domain');

        $cookie = cookie(
            'user_refresh_token',
            $newRefreshToken,
            60 * 24 * 15,
            '/',
            $cookieDomain,
            $isSecure,
            true,
            false,
            'Lax'
        );

        return response()->json([
            'access_token' => $newAccessToken,
            'message' => 'Token refreshed successfully',
        ])->withCookie($cookie);

    } catch (TokenExpiredException $e) {
        return response()->json(['error' => 'Refresh token expired'], 401);
    } catch (TokenInvalidException $e) {
        return response()->json(['error' => 'Invalid refresh token'], 401);
    } catch (JWTException $e) {
        return response()->json(['error' => 'Token refresh failed'], 401);
    }
}


public function getUserProfile(Request $request)
{
    try {
        $user = JWTAuth::parseToken()->authenticate();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
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


public function getUserToken() {
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
        try {
            // 🔁 Refresh token automatically
            $newToken = JWTAuth::parseToken()->refresh();
            $user = JWTAuth::setToken($newToken)->toUser();

            return response()->json([
                'id' => $user->id,
                'phone' => $user->phone,
                'name' => $user->name,
                'email' => $user->email,
                'access_token' => $newToken, // ✅ return new token
            ], 200);

        } catch (JWTException $ex) {
            return response()->json(['error' => 'Token expired completely'], 401);
        }

    } catch (TokenInvalidException $e) {
        return response()->json(['error' => 'Invalid token'], 401);
    } catch (JWTException $e) {
        return response()->json(['error' => 'Token missing or invalid'], 401);
    }
}

public function register(Request $request)
{
    $request->validate([
        'email' => 'required|email|exists:users,email',
        'name' => 'required|string|max:255',
        'phone' => [
            'required',
            'digits:10',
            Rule::unique('users', 'phone')->ignore(User::where('email', $request->email)->first()?->id),
        ],
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json(['message' => 'User not found'], 404);
    }

    $user->update([
        'name' => $request->name,
        'phone' => $request->phone,
    ]);

    // Optional: issue new short-lived token so frontend updates cleanly
    $accessToken = auth()->setTTL(120)->fromUser($user);

    return response()->json([
        'message' => 'User registered successfully',
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
        ],
        'access_token' => $accessToken,
    ]);
}



// public function register(Request $request)
// {
//     $request->validate([
//         'email' => 'required|email|exists:users,email',
//         'name' => 'required|string|max:255',
//         'phone' => 'required|digits:10|unique:users,phone',
//     ]);

//     $user = User::where('email', $request->email)->first();

//     if (!$user) {
//         return response()->json(['message' => 'User not found'], 404);
//     }

//     $user->name = $request->name;
//     $user->phone = $request->phone;
//     $user->save();

//     return response()->json(['message' => 'User registered successfully']);
// }





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


// public function getUserProfile(Request $request){
//     try {
//         $user = JWTAuth::parseToken()->authenticate();
        
//         if (!$user) {
//             return response()->json(['error' => 'User not found'], 404);
//         }

//         return response()->json([
//             'id' => $user->id,
//             'phone' => $user->phone,
//             'name' => $user->name,
//             'email' => $user->email,
//             'created_at' => $user->created_at,
//             'updated_at' => $user->updated_at,
//         ], 200);

//     } catch (TokenExpiredException $e) {
//         return response()->json(['error' => 'Token expired'], 401);
        
//     } catch (TokenInvalidException $e) {
//         return response()->json(['error' => 'Invalid token'], 401);
        
//     } catch (JWTException $e) {
//         return response()->json(['error' => 'Token missing or invalid'], 401);
//     }
// }



public function getAllUsers()
{
    try {
        // Authenticate via admin guard
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['error' => 'Admin not found'], 404);
        }

        // ✅ If you also want to ensure only admins can fetch:
        // if ($admin->role !== 'admin') {
        //     return response()->json(['error' => 'Forbidden'], 403);
        // }

        $users = User::all();

        return response()->json($users, 200);

    } catch (TokenExpiredException $e) {
        return response()->json(['error' => 'Token expired'], 401);

    } catch (TokenInvalidException $e) {
        return response()->json(['error' => 'Invalid token'], 401);

    } catch (JWTException $e) {
        return response()->json(['error' => 'Token missing or invalid'], 401);
    }
}


}







