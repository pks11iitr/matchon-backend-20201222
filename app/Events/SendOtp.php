<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class SendOtp
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $mobile,$message,$templateid;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($mobile,$message,$templateid)
    {
        $this->message=$message;
        $this->mobile=$mobile;
        $this->templateid=$templateid;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
