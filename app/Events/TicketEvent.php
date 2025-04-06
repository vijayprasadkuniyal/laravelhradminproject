<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */

    public $message;
    public $employeeId;
    public $type;
    public $ticket_id;

    public function __construct($message, $employeeId,$type,$ticket_id)
    {
        $this->message = $message;
        $this->employeeId = $employeeId;
        $this->type = $type;
        $this->ticket_id = $ticket_id;
    }
    //$this->employeeId;

    public function broadcastOn()
    {
        return new Channel('ticket-channel-' . $this->employeeId);
    }

    public function broadcastAs()
    {
        return 'ticket-event';
    }
}
