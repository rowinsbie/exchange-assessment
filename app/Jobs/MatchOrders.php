<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Asset;
use App\Events\OrderMatched;
use App\Events\OrderBookUpdated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class MatchOrders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function handle(): void
    {
        DB::transaction(function () {

            $order = Order::where('id', $this->order->id)
                ->where('status', 1) 
                ->lockForUpdate()
                ->first();

            if (!$order) return;

            $orderAmount = (float) $order->amount;
            $orderPrice  = (float) $order->price;

            if ($order->side === 'buy') {
                $match = Order::where('symbol', $order->symbol)
                    ->where('side', 'sell')
                    ->where('status', 1)
                    ->where('amount', $orderAmount)
                    ->where('price', '<=', $orderPrice)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->first();
            } else {
                $match = Order::where('symbol', $order->symbol)
                    ->where('side', 'buy')
                    ->where('status', 1)
                    ->where('amount', $orderAmount)
                    ->where('price', '>=', $orderPrice)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->first();
            }

            if (!$match) return;

            $amount = $orderAmount;
            $price  = (float) $match->price;
            $usdVolume = $price * $amount;
            $commission = $usdVolume * 0.015;

            $buyer  = $order->side === 'buy' ? $order->user : $match->user;
            $seller = $order->side === 'sell' ? $order->user : $match->user;

            $buyer->balance -= $commission;
            $buyer->save();

            $sellerAsset = Asset::where('user_id', $seller->id)
                ->where('symbol', $order->symbol)
                ->lockForUpdate()
                ->first();
            $sellerAsset->locked_amount = 0;
            $sellerAsset->save();

            $buyerAsset = Asset::firstOrCreate(
                ['user_id' => $buyer->id, 'symbol' => $order->symbol],
                ['amount' => 0, 'locked_amount' => 0]
            );
            $buyerAsset->amount += $amount;
            $buyerAsset->save();

            $order->status = 2;
            $match->status = 2;
            $order->save();
            $match->save();

            event(new OrderMatched($order, [
                'usd_balance' => $buyer->balance,
                'assets' => $buyer->assets()->get(),
            ]));

            event(new OrderMatched($match, [
                'usd_balance' => $seller->balance,
                'assets' => $seller->assets()->get(),
            ]));

            // Update order book
            event(new OrderBookUpdated($order->symbol, 'removed', ['id' => $order->id]));
            event(new OrderBookUpdated($match->symbol, 'removed', ['id' => $match->id]));
        });
    }
}
