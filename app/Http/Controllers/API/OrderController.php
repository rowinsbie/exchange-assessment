<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Jobs\MatchOrders;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $symbol = $request->query('symbol');

        if (!$symbol) {
            return response()->json([
                'success' => false,
                'message' => 'Symbol query parameter is required.',
                'data' => null
            ], 400);
        }

        $orders = Order::where('symbol', $symbol)
                        ->where('status', 1)
                        ->orderBy('id', 'asc')
                        ->get();

        return response()->json([
            'success' => true,
            'message' => 'Open orders fetched successfully.',
            'data' => $orders
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'symbol' => 'required|string',
            'side' => 'required|in:buy,sell',
            'price' => 'required|numeric|min:0.0001',
            'amount' => 'required|numeric|min:0.0001'
        ]);

        $user = Auth::user();
        $symbol = $request->symbol;
        $price = $request->price;
        $amount = $request->amount;
        $side = $request->side;

        try {
            DB::transaction(function() use ($user, $symbol, $price, $amount, $side) {
                if($side === 'buy') {
                    $total = $price * $amount;
                    if($user->balance < $total) {
                        abort(400, 'Insufficient USD balance');
                    }
                    $user->balance -= $total;
                    $user->save();
                } else {
                    $asset = Asset::firstOrCreate([
                        'user_id' => $user->id,
                        'symbol' => $symbol
                    ]);
                    if($asset->amount < $amount) {
                        abort(400, 'Insufficient asset balance');
                    }
                    $asset->amount -= $amount;
                    $asset->locked_amount += $amount;
                    $asset->save();
                }

                $order = Order::create([
                    'user_id' => $user->id,
                    'symbol' => $symbol,
                    'side' => $side,
                    'price' => $price,
                    'amount' => $amount,
                    'status' => 1
                ]);

                MatchOrders::dispatch($order);
            });

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully.',
                'data' => null
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 400);
        }
    }

    public function cancel($id)
    {
        $user = Auth::user();
        $order = Order::where('id', $id)->where('user_id', $user->id)->where('status', 1)->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Open order not found.',
                'data' => null
            ], 404);
        }

        DB::transaction(function() use ($order, $user) {
            if($order->side === 'buy') {
                $user->balance += $order->price * $order->amount;
                $user->save();
            } else {
                $asset = Asset::where('user_id', $user->id)->where('symbol', $order->symbol)->first();
                $asset->amount += $order->amount;
                $asset->locked_amount -= $order->amount;
                $asset->save();
            }

            $order->status = 3; 
            $order->save();
        });

        return response()->json([
            'success' => true,
            'message' => 'Order cancelled successfully.',
            'data' => null
        ]);
    }
}
