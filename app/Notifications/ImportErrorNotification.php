<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ImportErrorNotification extends Notification
{
    protected $errors;
    protected $type;

    public function __construct(array $errors, string $type)
    {
        $this->errors = $errors;
        $this->type = $type;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject("Import Error: {$this->type}")
            ->line('The following errors occurred during the import:')
            ->line(implode("\n", $this->errors))
            ->line('Please correct these issues and try again.');
    }
}
