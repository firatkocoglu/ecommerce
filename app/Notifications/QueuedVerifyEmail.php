<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

class QueuedVerifyEmail extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {   
        $verifyUrl = $this->verificationUrl($notifiable);
        
        return (new MailMessage)
            ->subject('E-Commerce App | Verify your email')
            ->greeting('Welcome to E-Commerce App!')
            ->line('One last step to use E-Commerce App! Please verify your email address with the link below:')
            ->action('Verify Email', $verifyUrl)
            ->line('If you did not create an account, no further action is required.');
    }

    protected function verificationUrl($notifiable): string {
        // Create the standard signed backend URL to the verify route
        $backendSignedUrl = URL::temporarySignedRoute(
            'api.v1.verification.verify',
            Carbon::now()->addMinutes(60),
            ['id' => $notifiable->getKey(), 'hash' => sha1($notifiable->getEmailForVerification()), 'redirect' => config('app.frontend_url') . '/verify-success',
        ]
        );

        // FRONTEND_URL=/ (e.g. http://localhost:5173)
        $frontend = rtrim(config('app.frontend_url', ''), '/');
        if ($frontend) {
            // e.g. http://localhost:5173/verify-email?url=<encodedSignedUrl>
            return $frontend . '/verify-email?url=' . urlencode($backendSignedUrl);
        }

        // Fallback: send the backend URL directly
        return $backendSignedUrl;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
