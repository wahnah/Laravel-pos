<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderStoreRequest;
use App\Http\Requests\OrderUpdateRequest;
use App\Http\Requests\CustomerStoreRequest;
use App\Models\DaySnapshot;
use App\Models\Order;
use App\Models\Customer;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use PDF;
use Milon\Barcode\DNS1D; // For 1D barcodes like code39
use Milon\Barcode\DNS2D; // For 2D barcodes like QR codes



class OrderController extends Controller
{
    public function index(Request $request) {
        $orders = new Order();
        if($request->search) {
            $orders = $orders->where('id', '=', $request->search);
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

    return view('orders.creditreceipt', compact('orderItems', 'order_id', 'custFN', 'custLN'));
}
public function updateOrderItem(Request $request, $orderItemId) {


    $orderItem = OrderItem::find($orderItemId);
    $newQuantity = $request->input('newQuantity');
    $previousQuantity = $orderItem->quantity;
    $product = $orderItem->product;
    $dateString = $orderItem->created_at->toDateString();
    //$dateTime = Carbon::parse($dateString);
    $existingData = DaySnapshot::where('date', $dateString)
                            ->where('product_id', $product->id) // Add this line for product_id filtering
                            ->first();


    if (!is_null($existingData)) {
        if ($newQuantity > $previousQuantity) {
            // Increment product quantity
            $product->quantity -= ($newQuantity - $previousQuantity);
            $existingData->order_quantity += ($newQuantity - $previousQuantity);
            $dayototal = ($newQuantity - $previousQuantity) * $product->price;
            $existingData->order_total += $dayototal;

        } elseif($newQuantity < $previousQuantity){
            // Decrement product quantity
            $product->quantity += ($previousQuantity - $newQuantity);
            $existingData->order_quantity -= ($previousQuantity - $newQuantity);
            $dayototall = ($previousQuantity - $newQuantity) * $product->price;
            $existingData->order_total -= $dayototall;
        }else{

            return redirect()->back()->with('success', 'Order item quantity  has not been updated.');
        }
    }

    $orderItem->quantity = $newQuantity;
    $orderItem->price = $newQuantity * $product->price;
    $orderItem->save();
    $product->save();
    $existingData->save();

    return redirect()->back()->with('success', 'Order item quantity updated successfully.');
}


public function oreceipt(Request $request, $order_id)
{
    $orderItems = OrderItem::where('order_id', $order_id)->get();

    return view('orders.creditoreceipt', compact('orderItems', 'order_id'));
}

public function generateBarcode($order_id) {
    $barcode = new DNS1D();
    $barcode->setStorPath(storage_path('app/public/barcodes')); // Set storage path for generated barcodes

    // Ensure the directory exists, create it if not
    $barcode_storage_path = storage_path('app/public/barcodes');
    if (!file_exists($barcode_storage_path)) {
        mkdir($barcode_storage_path, 0755, true);
    }

    // Generate the barcode image as a PNG string
    $barcode_image_data = $barcode->getBarcodePNG("$order_id", 'PHARMA', 2, 50);

    // Debug: Echo the generated image data
    // echo $barcode_image_data;

    // Generate a unique filename for the barcode image
    $barcode_filename = 'barcode_' . $order_id . '.png';

    // Save the barcode image to the filesystem
    $barcode_full_path = $barcode_storage_path . DIRECTORY_SEPARATOR . $barcode_filename;
    file_put_contents($barcode_full_path, $barcode_image_data);

    // Return the full path of the saved barcode image
    return $barcode_full_path;
}


public function receipt(Request $request)
{
    $orderId = $request->input('orderId');
    $receivedAmount = $request->input('receivedAmount');
    $changeAmount = $request->input('changeAmount');
    $orderItems = OrderItem::where('order_id', $orderId)->get();





    return view('receipt.receipt', compact('orderItems','receivedAmount', 'changeAmount', 'orderId'));
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

public function createCust($order_id)
    {
        return view('customers.create2', compact('order_id'));
    }


    public function custStore(CustomerStoreRequest $request, $order_id)
    {
        $avatar_path = '';

        if ($request->hasFile('avatar')) {
            $avatar_path = $request->file('avatar')->store('customers', 'public');
        }

        $customer = Customer::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'avatar' => $avatar_path,
            'user_id' => $request->user()->id,
        ]);

        if (!$customer) {
            return redirect()->back()->with('error', 'Sorry, there\'re a problem while creating customer.');
        }

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


    public function generateDailySummary()
{
    // Get yesterday's date
    $yesterday = Carbon::yesterday()->toDateString();

    // Retrieve orders with credit field equal to 0 created at yesterday's date
    $orders = Order::where('credit', 0)
                    ->whereDate('created_at', $yesterday)
                    ->get();

    $orderss = Order::whereDate('created_at', $yesterday)
                    ->get();

    // Calculate the total sum of prices from order items for these orders
    $totalPriceSumm = 0;
    $totalPriceSum = 0;
    foreach ($orders as $order) {
        $totalPriceSum += $order->items()->sum('price');
    }

    foreach ($orderss as $order) {
        $totalPriceSumm += $order->items()->sum('price');
    }

    // Retrieve cashing information for yesterday
    $cashinginfo = Cashinginfo::whereDate('created_at', $yesterday)->first();


    // Calculate the total credit (totalPriceSum) and subtract it from the total amount
    $totalAmount = $cashinginfo->cash_at_hand + $cashinginfo->bank_deposit + $cashinginfo->momo_payments + $totalPriceSum;

    $diff = $totalPriceSumm - $totalAmount;
    // Pass the relevant data to the view
    return view('orders.dailyReports', [
        'diff' => $diff,
        'total_amount' => $totalAmount,
        'total_amount_all' => $totalPriceSumm,
    ]);


}
public function deleteOrderItem(Request $request, $orderItemId)
{
    $orderItem = OrderItem::find($orderItemId);



    $orderID = $orderItem->order_id;



    // Count the number of OrderItems with the same order_id
    $orderItemCount = OrderItem::where('order_id', $orderID)->count();

    $orderItemQuantity = $orderItem->quantity;
    $product = $orderItem->product;
    $dateString = $orderItem->created_at->toDateString();
    //$dateTime = Carbon::parse($dateString);
    $existingData = DaySnapshot::where('date', $dateString)
                        ->where('product_id', $product->id) // Add this line for product_id filtering
                        ->first();
if (!is_null($existingData)) {
    if ($orderItemCount > 1) {
        //dd($existingData);
        // If there are more than one order items with the same order_id, delete only the order item
        $product->quantity += $orderItemQuantity;
        $existingData->order_quantity -= $orderItemQuantity;
        $dayototall = $orderItemQuantity * $product->price;
        $existingData->order_total -= $dayototall;
        $product->save();
        $existingData->save();
        $orderItem->delete();
    } else {
        // If there is only one order item with the same order_id, delete both the order item and the order
        $product->quantity += $orderItemQuantity;
        $existingData->order_quantity -= $orderItemQuantity;
        $dayototall = $orderItemQuantity * $product->price;
        $existingData->order_total -= $dayototall;
        $product->save();
        $existingData->save();
        $orderItem->delete();
        // Assuming you have a relationship between OrderItem and Order
        $order = Order::find($orderID);
        if ($order) {
            $order->delete();
        }
    }
}

    $orders = new Order();


        $orders = $orders->with(['items', 'payments', 'customer'])->latest()->paginate(10);

        $total = $orders->map(function($i) {
            return $i->total();
        })->sum();
        $receivedAmount = $orders->map(function($i) {
            return $i->receivedAmount();
        })->sum();

        return view('orders.index', compact('orders', 'total', 'receivedAmount'));
}


}
