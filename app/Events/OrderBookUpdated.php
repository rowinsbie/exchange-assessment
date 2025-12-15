<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class OrderBookUpdated implements ShouldBroadcast
{
    use SerializesModels;

    public string $symbol;
    public string $action;
    public array $order;

    public function __construct(string $symbol, string $action, array $order)
    {
        $this->symbol = $symbol;
        $this->action = $action; 
        $this->order = $order;
    }

    public function broadcastOn()
    {
        return new Channel('orderbook.' . $this->symbol);
    }

    public function broadcastAs()
    {
        return 'orderbook.updated';
    }
}
