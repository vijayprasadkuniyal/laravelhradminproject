<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

   
   public function via($notifiable)
 {
     return ['broadcast'];
 }

 public function toBroadcast($notifiable)
 {
     return new BroadcastMessage([
         'message' => 'This is a real-time notification!',
     ]);
 }
}
