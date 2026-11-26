<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReadingPlanReminder extends Notification
{
    use Queueable;

    public function __construct(
        public int $planId,
        public string $bookTitle,
        public string $timing,
        public string $title,
        public string $body
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'plan_id' => $this->planId,
            'book_title' => $this->bookTitle,
            'timing' => $this->timing,
            'title' => $this->title,
            'body' => $this->body,
        ];
    }
}
