<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\PhoneNumberHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserQuota;
use App\Services\WACloudNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    protected $waCloudService;

    public function __construct(WACloudNotificationService $waCloudService)
    {
        $this->waCloudService = $waCloudService;
    }

    /**
     * Show the form for requesting a password reset.
     */
    public function showLinkRequestForm()
    {
        return view('auth.passwords.email');
    }

    /**
     * Send a new password via WhatsApp to the user's phone number.
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
        ], [
            'phone.required' => 'Nomor HP wajib diisi.',
        ]);

        // Normalize phone number using PhoneNumberHelper
        $normalizedPhone = PhoneNumberHelper::normalize($request->phone);
        
        if (!$normalizedPhone) {
            return back()->withErrors([
                'phone' => 'Format nomor HP tidak valid. Gunakan format: 081234567890',
            ])->withInput();
        }

        // Find user by phone number (try various formats)
        $phoneWithoutPlus = ltrim($normalizedPhone, '+');
        $phoneWithZero = '0' . substr($phoneWithoutPlus, 2);
        
        $user = User::where('phone', $normalizedPhone)
            ->orWhere('phone', $phoneWithoutPlus)
            ->orWhere('phone', '+' . $phoneWithoutPlus)
            ->orWhere('phone', $phoneWithZero)
            ->first();

        // Always return success message for security (prevent user enumeration)
        if (!$user) {
            Log::warning('Password reset requested for non-existent phone number', [
                'phone' => $normalizedPhone,
                'ip' => $request->ip(),
            ]);
            
            return back()->with('status', 'Jika nomor HP terdaftar, password baru akan dikirim ke WhatsApp Anda.');
        }

        // Check quota before sending
        $userQuota = UserQuota::getOrCreateForUser($user->id);
        $hasQuota = $userQuota->text_quota > 0 || $userQuota->free_text_quota > 0 || $userQuota->balance > 0;
        
        Log::info('Password reset: Checking quota', [
            'user_id' => $user->id,
            'text_quota' => $userQuota->text_quota,
            'free_text_quota' => $userQuota->free_text_quota,
            'balance' => $userQuota->balance,
            'has_quota' => $hasQuota,
        ]);

        // Generate new password
        $newPassword = Str::random(12); // Generate 12 character random password
        
        // Update user password
        $user->password = Hash::make($newPassword);
        $user->save();

        // Send password via WhatsApp using WACloudNotificationService
        $message = "Halo {$user->name},\n\n";
        $message .= "Password baru Anda telah dibuat.\n\n";
        $message .= "🔐 *Password Baru:* {$newPassword}\n\n";
        $message .= "⚠️ *Penting:* Segera login dan ubah password Anda setelah masuk ke akun.\n\n";
        $message .= "Jika Anda tidak meminta reset password, segera hubungi admin.";

        // Normalize phone number for WACloud (format: 62xxxxxxxxxx, without +)
        $phoneForWA = PhoneNumberHelper::normalizeForChatId($normalizedPhone);
        if (!$phoneForWA) {
            Log::error('Password reset: Failed to normalize phone for WACloud', [
                'user_id' => $user->id,
                'normalized_phone' => $normalizedPhone,
            ]);
            return back()->withErrors([
                'phone' => 'Format nomor HP tidak valid untuk mengirim WhatsApp.',
            ]);
        }
        
        Log::info('Password reset: Attempting to send WhatsApp via WACloud', [
            'user_id' => $user->id,
            'phone' => $phoneForWA,
            'has_quota' => $hasQuota,
        ]);
        
        // Use WACloudNotificationService (settings from database)
        $result = $this->waCloudService->sendNotification($phoneForWA, $message);

        if ($result['success']) {
            Log::info('Password reset successful', [
                'user_id' => $user->id,
                'phone' => $normalizedPhone,
                'phone_for_wa' => $phoneForWA,
            ]);

            return back()->with('status', 'Password baru telah dikirim ke WhatsApp Anda. Silakan cek pesan WhatsApp Anda.');
        } else {
            $errorMessage = $result['error'] ?? 'Unknown error';
            
            Log::error('Failed to send password reset via WhatsApp', [
                'user_id' => $user->id,
                'phone' => $normalizedPhone,
                'phone_for_wa' => $phoneForWA,
                'error' => $errorMessage,
                'has_quota' => $hasQuota,
                'text_quota' => $userQuota->text_quota,
                'free_text_quota' => $userQuota->free_text_quota,
                'balance' => $userQuota->balance,
                'wacloud_configured' => !empty(\App\Models\Setting::getValue('notification_api_key')),
            ]);

            // Check if error is related to configuration
            $configError = false;
            if (stripos($errorMessage, 'tidak dikonfigurasi') !== false || 
                stripos($errorMessage, 'not configured') !== false || 
                stripos($errorMessage, 'WACloud') !== false ||
                stripos($errorMessage, 'Base URL') !== false ||
                stripos($errorMessage, 'API key') !== false ||
                stripos($errorMessage, 'Device ID') !== false) {
                $configError = true;
            }

            // Check if error is related to quota
            $quotaError = false;
            if (stripos($errorMessage, 'quota') !== false || stripos($errorMessage, 'insufficient') !== false) {
                $quotaError = true;
            }

            // Check if error is connection error
            $connectionError = false;
            if (stripos($errorMessage, 'Connection error') !== false || 
                stripos($errorMessage, 'Could not resolve') !== false ||
                stripos($errorMessage, 'cURL error') !== false) {
                $connectionError = true;
            }

            $errorMsg = 'Gagal mengirim password ke WhatsApp. ';
            if ($configError) {
                $errorMsg .= 'Konfigurasi WACloud belum lengkap. Silakan hubungi admin untuk mengisi API Key, Device ID, dan Base URL di halaman Settings.';
            } elseif ($connectionError) {
                $errorMsg .= 'Tidak dapat terhubung ke server WACloud. Pastikan Base URL sudah benar di halaman Settings.';
            } elseif ($quotaError) {
                $errorMsg .= 'Quota tidak mencukupi. ';
            } else {
                $errorMsg .= 'Silakan hubungi admin atau coba lagi nanti.';
            }

            return back()->withErrors([
                'phone' => $errorMsg,
            ]);
        }
    }
}
