<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class VerifyApiEmail extends BaseVerifyEmail
{
    protected string $platform;
    public function __construct(string $platform)
    {
        $this->platform = $platform;
    }
    protected function verificationUrl($notifiable)
    {
        $signedUrl = URL::temporarySignedRoute(
            'api.user.profile.email.verification.verify',
            Carbon::now()->addMinutes((int) Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );

        $frontendUrl = config('app.frontend_url');
        $appDeepLink = config('app.app_deeplink');

        return $this->platform === 'mobile'
            ? "{$appDeepLink}?url=" . urlencode($signedUrl)
            : "{$frontendUrl}/verify-email?url=" . urlencode($signedUrl);
    }
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Verifikasi Email Anda')
            ->line('Klik tombol di bawah ini untuk verifikasi email Anda.')
            ->action('Verifikasi Email', $this->verificationUrl($notifiable))
            ->line('Jika Anda tidak merasa melakukan pengejuan verifikasi email, abaikan email ini.');
    }
}
