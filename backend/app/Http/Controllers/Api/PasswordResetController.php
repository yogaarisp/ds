<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Config;

class PasswordResetController extends Controller
{
    /**
     * Send password reset email
     */
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $customer = Customer::where('email', $request->email)->first();

        if (!$customer) {
            return response()->json([
                'message' => 'Email tidak terdaftar'
            ], 404);
        }

        // Check if email is configured
        $mailUsername = Setting::get('mail_username', '');
        if (empty($mailUsername)) {
            return response()->json([
                'message' => 'Fitur reset password belum dikonfigurasi. Silakan hubungi admin untuk mereset password Anda.'
            ], 503);
        }

        // Generate reset token
        $token = Str::random(64);
        $customer->reset_token = $token;
        $customer->reset_token_expires_at = now()->addHour();
        $customer->save();

        // Configure mail settings from database
        $this->configureMailSettings();

        // Send email
        try {
            $resetUrl = config('app.frontend_url', 'http://localhost:5173') . '/customer/reset-password?token=' . $token;
            
            Mail::send('emails.password-reset', [
                'customer' => $customer,
                'resetUrl' => $resetUrl,
                'storeName' => Setting::get('store_name', 'Dimsum Sarang')
            ], function ($message) use ($customer) {
                $message->to($customer->email, $customer->name)
                        ->subject('Reset Password - ' . Setting::get('store_name', 'Dimsum Sarang'));
            });

            return response()->json([
                'message' => 'Link reset password telah dikirim ke email Anda'
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send reset email: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Gagal mengirim email. Pastikan konfigurasi email sudah benar di Pengaturan → Email.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Reset password with token
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'password' => 'required|min:6|confirmed',
        ]);

        $customer = Customer::where('reset_token', $request->token)
            ->where('reset_token_expires_at', '>', now())
            ->first();

        if (!$customer) {
            return response()->json([
                'message' => 'Token tidak valid atau sudah kadaluarsa'
            ], 400);
        }

        // Update password
        $customer->password = Hash::make($request->password);
        $customer->reset_token = null;
        $customer->reset_token_expires_at = null;
        $customer->save();

        return response()->json([
            'message' => 'Password berhasil direset. Silakan login dengan password baru Anda.'
        ]);
    }

    /**
     * Verify reset token
     */
    public function verifyToken(Request $request)
    {
        $request->validate([
            'token' => 'required',
        ]);

        $customer = Customer::where('reset_token', $request->token)
            ->where('reset_token_expires_at', '>', now())
            ->first();

        if (!$customer) {
            return response()->json([
                'valid' => false,
                'message' => 'Token tidak valid atau sudah kadaluarsa'
            ], 400);
        }

        return response()->json([
            'valid' => true,
            'email' => $customer->email
        ]);
    }

    /**
     * Configure mail settings from database
     */
    private function configureMailSettings()
    {
        $mailHost = trim(Setting::get('mail_host', 'smtp.gmail.com'));
        $mailPort = trim(Setting::get('mail_port', '587'));
        $mailUsername = trim(Setting::get('mail_username', ''));
        $mailPassword = str_replace(' ', '', Setting::get('mail_password', ''));
        $mailEncryption = trim(Setting::get('mail_encryption', 'tls'));
        $mailFromAddress = trim(Setting::get('mail_from_address', ''));
        $mailFromName = trim(Setting::get('mail_from_name', 'Dimsum Sarang'));

        // Reset mail configuration
        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.transport', 'smtp');
        Config::set('mail.mailers.smtp.host', $mailHost);
        Config::set('mail.mailers.smtp.port', $mailPort);
        Config::set('mail.mailers.smtp.username', $mailUsername);
        Config::set('mail.mailers.smtp.password', $mailPassword);
        Config::set('mail.mailers.smtp.encryption', $mailEncryption);
        Config::set('mail.from.address', $mailFromAddress ?: $mailUsername);
        Config::set('mail.from.name', $mailFromName);
        
        // Add stream options for SSL/TLS
        Config::set('mail.mailers.smtp.stream', [
            'ssl' => [
                'allow_self_signed' => true,
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        // Purge the mailer to ensure it uses the new configuration
        Mail::forgetMailers();
    }

    /**
     * Test email configuration
     */
    public function testEmail(Request $request)
    {
        $request->validate([
            'test_email' => 'required|email',
        ]);

        // Check if email is configured
        $mailUsername = Setting::get('mail_username', '');
        if (empty($mailUsername)) {
            return response()->json([
                'success' => false,
                'message' => 'Konfigurasi email belum diisi. Silakan isi form di atas terlebih dahulu.'
            ], 400);
        }

        // Configure mail settings
        $this->configureMailSettings();

        try {
            $testEmail = $request->test_email;
            $storeName = Setting::get('store_name', 'Dimsum Sarang');

            Mail::raw("haloo dimsum sarang lovers", function ($message) use ($testEmail) {
                $message->to($testEmail)
                        ->subject('Test');
            });

            return response()->json([
                'success' => true,
                'message' => 'Email test berhasil dikirim ke ' . $testEmail . '! Silakan cek inbox Anda.'
            ]);
        } catch (\Exception $e) {
            \Log::error('Test email failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim email test.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
