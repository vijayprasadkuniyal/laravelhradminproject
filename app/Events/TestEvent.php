<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TestEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */

     public $message;
    public $employeeId;

    public function __construct($message, $employeeId)
    {
        $this->message = $message;
        $this->employeeId = $employeeId;
    }
    //$this->employeeId;

    public function broadcastOn()
    {
        return new Channel('employee-channel-' . $this->employeeId);
    }

    public function broadcastAs()
    {
        return 'my-event';
    }
}
