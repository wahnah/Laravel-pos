<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Customer;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        // Check the user's role
        if (Auth::check() && Auth::user()->role == 0) {
            // If user role is 0, redirect to the home route
            $orders = Order::with(['items', 'payments'])->get();
        $customers_count = Customer::count();

        return view('home', [
            'orders_count' => $orders->count(),
            'income' => OrderItem::sum('price'),
            'income_today' => $orders->where('created_at', '>=', date('Y-m-d').' 00:00:00')->map(function($i) {
                if($i->receivedAmount() > $i->total()) {
                    return $i->total();
                }
                return $i->receivedAmount();
            })->sum(),
            'customers_count' => $customers_count
        ]);
        } else {
            // If user role is not 0, redirect to the cart.index route
            return redirect()->route('cart.index');
        }
    }
}
