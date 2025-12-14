<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Asset;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Pusher\Pusher;

class MatchOrders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function handle(): void
    {
        $order = Order::where('id', $this->order->id)
                      ->where('status', 1) 
                      ->first();

        if (!$order) {
            return; 
        }

        DB::transaction(function () use ($order) {

            $match = Order::where('symbol', $order->symbol)
                ->where('side', $order->side === 'buy' ? 'sell' : 'buy')
                ->where('status', 1)
                ->when($order->side === 'buy', fn($q) => $q->where('price', '<=', $order->price))
                ->when($order->side === 'sell', fn($q) => $q->where('price', '>=', $order->price))
                ->orderBy('id')
                ->first();

            if (!$match) {
                return; 
            }

            $buyer  = $order->side === 'buy' ? $order->user : $match->user;
            $seller = $order->side === 'sell' ? $order->user : $match->user;

            $price = $match->price;
            $amount = min($order->amount, $match->amount); 
            $usdVolume = $price * $amount;
            $commission = $usdVolume * 0.015; 

            $buyer->balance -= $usdVolume + $commission;
            $buyer->save();

            $sellerAsset = Asset::firstOrCreate([
                'user_id' => $seller->id,
                'symbol' => $order->symbol
            ]);
            $sellerAsset->locked_amount -= $amount;
            $sellerAsset->save();

            $buyerAsset = Asset::firstOrCreate([
                'user_id' => $buyer->id,
                'symbol' => $order->symbol
            ]);
            $buyerAsset->amount += $amount;
            $buyerAsset->save();

            $order->status = 2;
            $order->save();

            $match->status = 2;
            $match->save();

            $pusher = new Pusher(
                env('PUSHER_APP_KEY'),
                env('PUSHER_APP_SECRET'),
                env('PUSHER_APP_ID'),
                [
                    'cluster' => env('PUSHER_APP_CLUSTER'),
                    'useTLS' => true
                ]
            );

            $data = [
                'order_id' => $order->id,
                'matched_order_id' => $match->id,
                'symbol' => $order->symbol,
                'price' => $price,
                'amount' => $amount,
                'buyer_id' => $buyer->id,
                'seller_id' => $seller->id,
                'commission' => $commission
            ];

            $pusher->trigger('private-user.' . $buyer->id, 'OrderMatched', $data);
            $pusher->trigger('private-user.' . $seller->id, 'OrderMatched', $data);
        });
    }
}
