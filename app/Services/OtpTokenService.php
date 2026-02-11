<?php

namespace App\Services;

use App\Enums\OtpTokenType;
use App\Mail\ForgetPasswordMail;
use App\Mail\VerifyAccountMail;
use App\Models\OtpToken;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

use function Laravel\Prompts\info;
use function Symfony\Component\Clock\now;

class OtpTokenService
{

    protected function generateOtp()
    {
        return rand(100000, 999999);
    }

    protected function getToken($type = null, $user = null)
    {
        $user = $user ?? request()->user();

        $type = $type ?? OtpTokenType::ACCOUNT_VERIFICATION->value;
        info("User: " . $user->id . " Type:" . $type);

        // Delete previous token
        $previousToken = OtpToken::where('user_id', $user->id)
            ->where('type', $type)
            ->get();

        if ($previousToken) {
            // $previousToken->delete();
            $previousToken->each->delete();
        }

        $otp = $this->generateOtp();
        $expires = Carbon::now()->addMinutes(10);
        $token = OtpToken::create([
            'user_id' => $user->id,
            'type' => $type,
            'token' => Hash::make($otp),
            'expires' => $expires,
        ]);
        return [
            "otp" => $otp,
            "token" => $token
        ];
    }

    // FIX: optional  $type should come last
    public function sendToken($type = null, $user)
    {
        $type = OtpTokenType::ACCOUNT_VERIFICATION->value;
        info("User: " . $user->id . " Type:" . $type);
        $theToken = $this->getToken($type, $user);

        // $mail = Mail::raw("
        // This is a test email, from: VeriScore by GluedInsights. \n 
        // Your otp is: " . $theToken['otp'] . " \n
        // Thank you.\n", function ($message) {
        //     $message->to(env('DEV_MAIL'))->subject(Str::title(OtpTokenType::ACCOUNT_VERIFICATION->value));
        // });

        // Send accout verification
        // Mail::to(env('DEV_MAIL'))->send(new VerifyAccountMail($user, $theToken['otp']));
        Mail::to($user->email)->send(new VerifyAccountMail($user, $theToken['otp']));
        info("Sent OTP to user email: " . $user->email);
    }

    public function verifyToken($type = null, $user, $otp)
    {
        $type = $type ?? OtpTokenType::ACCOUNT_VERIFICATION->value;
        info("Verify Token, User: " . $user?->id . " Type:" . $type);

        $theToken = OtpToken::where('user_id', $user?->id)
            ->where('type', $type)
            ->where('expires', '>=', now())
            ->latest()
            ->first();

        if (!$theToken || !Hash::check($otp, $theToken['token'])) {
            return false;
        }
        $theToken->delete();
        return true;
    }


    // sendForgotPasswordToken
    public function sendForgetPasswordToken($user)
    {
        $type = OtpTokenType::FORGET_PASSWORD->value;
        info("User: " . $user->id . " Type:" . $type);
        $theToken = $this->getToken($type, $user);

        // $mail = Mail::raw("
        // This is a test email, from: VeriScore by GluedInsights. \n 
        // Your otp is: " . $theToken['otp'] . " \n
        // Thank you.\n", function ($message) {
        //     $message->to(env('DEV_MAIL'))->subject(Str::title(OtpTokenType::ACCOUNT_VERIFICATION->value));
        // });

        // Send account verification
        Mail::to($user->email)->send(new ForgetPasswordMail($user, $theToken['otp']));
    }
}
