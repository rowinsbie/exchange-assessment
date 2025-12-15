<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Repositories\OrderRepository;

class OrderController extends Controller
{

    private $orderRepository;

    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }


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

        $orders = $this->orderRepository->getOrders($symbol);

        return response()->json([
            'success' => true,
            'message' => 'Open orders fetched successfully.',
            'data' => $orders
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->orderRepository->validateRequest($request);
        $user = Auth::user();

        try {
            DB::transaction(function() use ($user, $data) {
                $this->orderRepository->processUserBalance($user, $data);
                $order = $this->orderRepository->createOrder($user, $data);
                $this->orderRepository->dispatchEvents($order);
            });

            return response()->json([
                'success' => true,
                'message' => "Order placed successfully",
                'data'    => null,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => null,
            ], 400);
        }
    }


    public function cancel($id)
    {
        $user = Auth::user();

        try {
            DB::transaction(function () use ($id, $user) {
                $order = Order::where('id', $id)
                    ->where('user_id', $user->id)
                    ->where('status', 1)
                    ->lockForUpdate()
                    ->first();

                if (!$order) {
                    throw new \Exception('Open order not found');
                }

                $this->orderRepository->cancelOrder($order, $user);
            });

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully.',
                'data' => null
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 404);
        }
    }
}
