<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    use HttpResponses;



    public function register(StoreUserRequest $request)
    {

        $request->validated($request->all());
        $verificationCode = Str::random(6);
        $user = User::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'verification_code' => $verificationCode,
        ]);

        Log::info("verification code {$verificationCode} sent to the phone {$user->phone}");
        //[2024-08-31 18:45:02] local.INFO: verification code Kgy3jU sent to the phone 01127713166  

        return $this->success([
            'user' => $user,
            'token' => $user->createToken('API Token of' . $user->name)->plainTextToken
        ]);
    }

    public function verify(Request $request)
    {
        $validator = $request->validate([
            'phone' => ['required', 'string'],
            'verification_code' => ['required', 'string', 'size:6'],
        ]);

        $user = User::where('phone', $request->phone)
            ->where('verification_code', $request->verification_code)
            ->first();

        if (!$user) {
            return $this->error('', 'invalid verification code',  401);
        }

        $user->is_verified = 1;
        $user->verification_code = null;
        $user->save();
        return $this->success(['message' => 'user verified successfully']);
    }

    public function login(LoginUserRequest $request)
    {
        $request->validated($request->all());
        if (!Auth::attempt($request->only(['phone', 'password']))) {
            return $this->error('', 'Credentials are do not match',  401);
        }

        $user = User::where('phone', $request->phone)->first();

        if (!$user->is_verified) {
            return $this->error('', 'user account is not verified',  403);
        }
        return $this->success([
            'user' => $user,
            'token' => $user->createToken('Api Token of' . $user->name)->plainTextToken
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return $this->success(['message' => 'successfully logged out']);
    }
}
