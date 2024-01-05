<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderStoreRequest;
use App\Http\Requests\OrderUpdateRequest;
use App\Models\Order;
use App\Models\Customer;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use PDF;

class OrderController extends Controller
{
    public function index(Request $request) {
        $orders = new Order();
        if($request->start_date) {
            $orders = $orders->where('created_at', '>=', $request->start_date);
        }
        if($request->end_date) {
            $orders = $orders->where('created_at', '<=', $request->end_date . ' 23:59:59');
        }
        $orders = $orders->with(['items', 'payments', 'customer'])->latest()->paginate(10);

        $total = $orders->map(function($i) {
            return $i->total();
        })->sum();
        $receivedAmount = $orders->map(function($i) {
            return $i->receivedAmount();
        })->sum();

        return view('orders.index', compact('orders', 'total', 'receivedAmount'));
    }

    public function credlist() {

        $orders = Order::where('credit', 0)->get();

        foreach ($orders as $order) {
            $id = $order->id;

            $orderItems = OrderItem::where('order_id', $id)->get();
            $totalPrice = 0;
            foreach ($orderItems as $orderItem) {
                $totalPrice += $orderItem->price;
             }
             $order->totalPrice = $totalPrice;
        }

        return view('orders.crditliist', compact('orders'));
    }

    public function store(OrderStoreRequest $request)
{
    $customer_id = $request->customer_id;

    // Create a new order
    $order = Order::create([
        'customer_id' => $customer_id,
        'user_id' => $request->user()->id,
    ]);

    // Query the cart items by customer_id
    $cart = DB::table('user_cart')
        ->where('customer_id', $customer_id)
        ->get();

    foreach ($cart as $item) {
        // Query the product price based on product_id
        $product = Product::find($item->product_id);

        // Create order items with the product price
        $order->items()->create([
            'price' => $product->price * $item->quantity,
            'quantity' => $item->quantity,
            'product_id' => $item->product_id,
        ]);

        // Update product quantity
        $product->quantity -= $item->quantity;
        $product->save();
    }

    // Detach all cart items for the customer
    DB::table('user_cart')
        ->where('customer_id', $customer_id)
        ->delete();

    // Create a payment record
    $order->payments()->create([
        'amount' => $request->amount,
        'user_id' => $request->user()->id,
    ]);

    $oid = $order->id;
    return $oid;
}

/**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $order_id)
    {
        $order = Order::findOrFail($order_id);
        $orderItems = OrderItem::where('order_id', $order_id)->get();
        $totalPrice = 0;

        // Fetch customers for the dropdown
        $customers = Customer::all(); // Replace 'Customer' with your actual model name

        foreach ($orderItems as $orderItem) {
            $totalPrice += $orderItem->price;
        }

        return view('orders.edit', compact('order', 'order_id', 'orderItems', 'totalPrice', 'customers'));
    }


public function update(OrderUpdateRequest $request, $order_id)
{
    $order = Order::findOrFail($order_id);
    $order->customer_id = $request->customer_id;
    $order->credit = $request->validated()['credit'];

    if (!$order->save()) {
        return redirect()->back()->with('error', 'Sorry, there\'s a problem while updating the order.');
    }

    return redirect()->route('orders.edit', ['order_id' => $order_id])->with('success', 'Success, your order has been updated.');
}

public function updatee(OrderUpdateRequest $request, $order_id)
{
    $order = Order::findOrFail($order_id);
    $order->credit = $request->validated()['credit'];

    if (!$order->save()) {
        return redirect()->back()->with('error', 'Sorry, there\'s a problem while paying credit.');
    }

    return redirect()->back()->with('success', 'Success, credit has been paid.');
}



    public function creceipt(Request $request, $order_id)
{
    $order = Order::findOrFail($order_id);
    $cust_id = $order->customer_id;
    $custFN = Customer::where('id', $cust_id)->value('first_name');
    $custLN = Customer::where('id', $cust_id)->value('last_name');
    $orderItems = OrderItem::where('order_id', $order_id)->get();

    return view('orders.creditreceipt', compact('orderItems', 'custFN', 'custLN'));
}

public function oreceipt(Request $request, $order_id)
{
    $orderItems = OrderItem::where('order_id', $order_id)->get();

    return view('orders.creditoreceipt', compact('orderItems'));
}


    public function receipt(Request $request)
{
    $orderId = $request->input('orderId');
    $receivedAmount = $request->input('receivedAmount');
    $changeAmount = $request->input('changeAmount');
    $orderItems = OrderItem::where('order_id', $orderId)->get();

    return view('receipt.receipt', compact('orderItems','receivedAmount', 'changeAmount'));
}

public function getAllOrderItems()
{
    $orderItems = OrderItem::paginate(10);

    // You can perform any additional logic or processing here

    return view('orders.orderItemsIndex', compact('orderItems'));
}



public function report(Request $request)
{
    $request->validate([
        'start_date' => 'required|date',
    ]);
    // Get the restock history records with the specified start date
    $start_date = $request->input('start_date');
    $restocks = OrderItem::whereDate('created_at', $start_date)
    ->groupBy('product_id')
    ->select('product_id', DB::raw('SUM(quantity) as total_quantity'))
    ->with('product') // Load the related product model
    ->get();
    // Generate the PDF report
    //$pdf = PDF::loadView('orders.orderReports', compact('restocks', 'start_date'));
    // Open the print dialog
    //$pdf->setPaper('A4', 'portrait');
    //return $pdf->stream();
    return view('orders.orderReports', compact('restocks', 'start_date'));
}
}
