<?php

namespace App\Events;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Broadcasting\PrivateChannel;

class OrderMatched implements ShouldBroadcast
{
    public $order;
    public $balances;

    public function __construct($order, $balances)
    {
        $this->order = $order;
        $this->balances = $balances;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('user.' . $this->order->user_id);
    }

    public function broadcastAs()
    {
        return 'order.matched';
    }
}
