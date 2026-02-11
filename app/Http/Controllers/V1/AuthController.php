<?php

namespace App\Http\Controllers\V1;

use App\Enums\AppRole;
use App\Enums\OtpTokenType;
use App\Enums\UserRoleEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;
use App\Http\Requests\Api\RegisterRequest;
use App\Models\Company;
use App\Models\CompanyEmployee;
use App\Models\OtpToken;
use App\Models\Team;
use App\Models\User;
use App\Services\OtpTokenService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
// use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

use function Laravel\Prompts\info;

class AuthController extends Controller
{
    public function __construct(public OtpTokenService $otpTokenService)
    {
        $this->otpTokenService = $otpTokenService;
    }

    /**
     * Register User - Validate input, create user, send email verification OTP, assign role, generate token
     * @param name
     * @param email
     * @param password
     * @param first_name
     * @param last_name
     * @param security_question
     * @param answer
     * @param phone_number
     * @param gender
     * @param dob
     * @param profession
     * @param address
     * @param city
     * @param state
     * @param country
     */
    public function register(RegisterRequest $request)
    {

        try {
            //code...
            DB::beginTransaction();

            // Create user
            $user = User::create($request->validated());

            // Create social media record
            $user->social()->create();

            // Represent the user role
            $user->assignRole(UserRoleEnum::USER->value);
            $user->role = UserRoleEnum::USER->value;
            $user->name = $request->first_name . ' ' . $request->last_name . ' ' . $request->other_name . ' ' . User::count();
            $user->save();


            // Unset sensitive information
            $data = $user->toArray();
            unset($data['password']);
            info('Registered', $data);

            // Generate token
            $token = $user->createToken('auth_token')->plainTextToken;
            // Send OTP to user email
            $this->otpTokenService->sendToken(OtpTokenType::ACCOUNT_VERIFICATION->value, $user);
            $user->load(['social']);

            Log::info($user->toArray());
            info("User: " . $user);
            DB::commit();
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            info($th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'User registered failed, please try again later.',
                'error' => $th->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'account created successfully',
            'data' => $user,
            'token' => $token,
            'type' => 'Bearer',
        ]);
    }

    /**
     * Login
     * @param email
     * @param password
     */
    public function login(Request $request)
    {
        DB::beginTransaction();

        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string']
        ]);

        // incorrect email or password
        if (!Auth::attempt($request->only('email', 'password'))) {
            Log::error("Login validation failed");
            return response()->json([
                'success' => true,
                'message' => 'Unauthorized, incorrect email or password',
            ], 401);
        }

        $user = Auth::user();
        // load user with profile
        // $user->load(['userProfile.profileImage']);
        $token = $user->createToken('auth_token')->plainTextToken;
        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'token' => $token,
            'user' => $user,
        ]);
    }


    /**
     * Reset password for the currently authenticated user
     * @param current_password
     * @param password
     */
    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            // 'email' => ['required', 'email'],
            'new_password' => ['required', 'string'],
            'current_password' => ['required', 'string', 'current_password'],
        ]);

        // Update password
        $auth = Auth::user()->update([
            // 'password' => $data['new_password'],
            'password' => Hash::make($request->new_password),
        ]);
        if (!$auth) {
            return response()->json([
                'success' => false,
                'message' => 'Password reset failed.',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Password reset successful.',
            'user' => $request->user(),
        ]);
    }

    /**
     * Forgot password
     * @param email
     */
    public function forgotPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                // vulnerability
                // 'email' => 'required|email|exists:users,email', 
                'email' => 'required|email',
            ]);

            if ($validator->fails()) {
                Log::error("Email verification failed");
                return response()->json(['error' => $validator->errors()], 401);
            }
        } catch (ValidationException $e) {
            // throw $e;
            info($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong, forgot password process failed.',
            ]);
        }


        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'success' => true,
                'message' => 'You will receive an OTP to setup your new password, if an account with this email exist.',
            ]);
        }
        // Send otp to user
        $this->otpTokenService->sendForgetPasswordToken($user);

        return response()->json([
            'success' => true,
            'message' => 'Check your email inbox for an OTP to setup your new password.',
        ]);
    }


    /**
     * Handle an incoming forget password reset request.
     * @param otp
     * @param password
     * @throws \Illuminate\Validation\ValidationException
     */
    public function confirmPassword(Request $request)
    {

        DB::beginTransaction();

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string|max:255',
            'password' => 'required|string|min:6',
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'success' => true,
                'message' => 'Password reset failed, check your email.',
            ]);
        }
        if ($validator->fails()) {
            Log::error("Register validation failed");
            return response()->json(['error' => $validator->errors()], 401);
        }

        // Validate OTP Code
        $tokenVerified = $this->otpTokenService->verifyToken(OtpTokenType::FORGET_PASSWORD->value, $user, $request->otp);
        if (!$tokenVerified) {
            return response()->json([
                'success' => false,
                'message' => 'OTP verification failed, invalid OTP.',
            ]);
        }
        $user->update(['password' => Hash::make($request->password)]);
        // Update password
        $auth = $user->update([
            'password' => Hash::make($request->password),
        ]);
        if (!$auth) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Password reset failed.',
            ]);
        }
        DB::commit();
        return response()->json([
            'success' => true,
            'message' => 'Password reset successful.',
        ]);
    }


    /**
     * Verify account
     * @param otp
     */
    public function verifyAccount(Request $request)
    {

        $data = $request->validate([
            'otp' => ['required', 'min:6', 'max:6'],
        ]);

        if ($request->user()->hasVerifiedEmail()) {
            return response()->json([
                'success' => true,
                'message' => 'Email already verify.',
            ]);
        }

        // Validate OTP Code
        $user = $request->user();
        $tokenVerified = $this->otpTokenService->verifyToken(OtpTokenType::ACCOUNT_VERIFICATION->value, $user, $data['otp']);
        if (!$tokenVerified) {
            return response()->json([
                'success' => false,
                'message' => 'Email verification failed, invalid OTP, request for another OTP.',
            ]);
        }


        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return response()->json([
            'success' => true,
            'message' => 'Email verification successful.',
            'user' => $request->user(),
        ]);
    }


    /**
     * Send a new email verification token.
     */
    public function resendVerificationToken(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json([
                'success' => true,
                'message' => 'Email already verified',
            ]);
        }

        $user = $request->user();
        // Send OTP to user email
        $this->otpTokenService->sendToken(OtpTokenType::ACCOUNT_VERIFICATION->value, $user);

        return response()->json([
            'success' => true,
            'message' => 'Otp verification sent to your email',
        ]);
    }

    /**
     * Destroy current users token.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        $message = 'Logged out successfully';
        return ApiResponse::success([], $message);
    }



    /**
     * Destroy the user's token.
     */
    public function logoutDevices(Request $request)
    {
        // $user->tokens()->delete();
        $request->user()->tokens()->delete();
        $message = 'Logged out successfully';
        return ApiResponse::success([], $message);
    }
}
