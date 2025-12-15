<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\Asset;
use App\Jobs\MatchOrders;
use App\Events\OrderBookUpdated;
use Illuminate\Http\Request;

class OrderRepository {

    public function getOrders($symbol)
    {
        return Order::where('symbol', $symbol)
                        ->where('status', 1)
                        ->orderBy('id', 'asc')
                        ->get();
    }

    public function validateRequest(Request $request): array
    {
        return $request->validate([
            'symbol' => 'required|string',
            'side'   => 'required|in:buy,sell',
            'price'  => 'required|numeric|min:0.0001',
            'amount' => 'required|numeric|min:0.0001',
        ]);
    }

    public function processUserBalance($user, array $data): void
    {
        if ($data['side'] === 'buy') {
            $this->handleBuyOrder($user, $data['price'], $data['amount']);
        } else {
            $this->handleSellOrder($user, $data['symbol'], $data['amount']);
        }
    }

    public function handleBuyOrder($user, float $price, float $amount): void
    {
        $total = $price * $amount;

        if ($user->balance < $total) {
            abort(400, 'Insufficient USD balance');
        }

        $user->balance -= $total;
        $user->save();
    }

    public function handleSellOrder($user, string $symbol, float $amount): void
    {
        $asset = Asset::firstOrCreate([
            'user_id' => $user->id,
            'symbol'  => $symbol,
        ]);

        if ($asset->amount < $amount) {
            abort(400, 'Insufficient asset balance');
        }

        $asset->amount -= $amount;
        $asset->locked_amount += $amount;
        $asset->save();
    }

    public function createOrder($user, array $data): Order
    {
        return Order::create([
            'user_id' => $user->id,
            'symbol'  => $data['symbol'],
            'side'    => $data['side'],
            'price'   => $data['price'],
            'amount'  => $data['amount'],
            'status'  => 1,
        ]);
    }


    public function dispatchEvents(Order $order): void
    {
        MatchOrders::dispatch($order);
        \Log::info("dispatch");
        event(new OrderBookUpdated(
            $order->symbol,
            'created',
            $order->toArray()
        ));
    }

    public function cancelOrder(Order $order, $user): void
    {
        if($order->side === 'buy') {
            $user->balance += $order->price * $order->amount;
            $user->save();
        } else {
            $asset = Asset::where('user_id', $user->id)
                ->where('symbol', $order->symbol)
                ->first();
            $asset->amount += $order->amount;
            $asset->locked_amount -= $order->amount;
            $asset->save();
        }

        $order->status = 3;
        $order->save();

        event(new OrderBookUpdated(
            $order->symbol,
            'removed',
            ['id' => $order->id]
        ));
    }
}
