<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class KycApprovalNotification extends Notification
{
    use Queueable;

    protected $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        // $status = $this->user->kyc_approval == 'Unverified' ? 'Rejected' : 'Approved';
        // $url = url('https://member.luckyantfxasia.com/login');

        // return (new MailMessage)
        //     ->subject('LuckyAnt Trading KYC Status - ' . $status)
        //     ->greeting('Dear ' . $this->user->name)
        //     ->line($this->getMessage())
        //     ->action('Login Portal', $url)
        //     ->line('Thank you for using our application!');
        return (new MailMessage)
            ->subject('KYC Approval Confirmation!')
            ->markdown('emails.kyc_approval_confirmation_email', [
                'user' => $this->user,
            ]);

    }

    // protected function getMessage(): string
    // {
    //     $message = '';

    //     if ($this->user->kyc_approval == 'Verified') {
    //         $message = 'Your KYC has been approved.';
    //     } elseif ($this->user->kyc_approval == 'Unverified') {
    //         return "Your KYC needs further verification. Please resubmit your KYC information. Reason: {$this->user->kyc_approval_description}";
    //     }

    //     return $message;
    // }

    public function toArray($notifiable): array
    {
        return [];
    }
}
