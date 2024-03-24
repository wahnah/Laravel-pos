<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Phystockcount;
use App\Models\Cashinginfo;
use App\Models\OrderItem;
use App\Models\DaySnapshot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;



class CartController extends Controller
{
    public function index(Request $request)
{
    $customer_id = $request->input('customer_id', 1);  // Assuming the user ID is the customer ID
    Log::info($customer_id);
    if ($request->wantsJson()) {
        //$customer_id = $request->customer_id; // Assuming the user ID is the customer ID
        $cartItems = DB::table('user_cart')
            ->join('products', 'user_cart.product_id', '=', 'products.id')
            ->where('user_cart.customer_id', $customer_id) // Use customer_id here
            ->select('products.*', 'user_cart.quantity', 'user_cart.user_id', 'user_cart.product_id', 'user_cart.customer_id')
            ->get();

        $formattedCartItems = $cartItems->map(function ($item) use ($customer_id)  {
            $product = DB::table('products')->where('id', $item->product_id)->first();
            return [
                'id' => $item->product_id,
                'name' => $item->name,
                'description' => $item->description,
                'image' => $item->image,
                'barcode' => $item->barcode,
                'category_id' => $item->category_id,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
                'pivot' => [
                    'user_id' => $item->user_id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'customer_id' => $customer_id,
                ],
                'price' => $product->price,
                'quantity' => $product->quantity,
                'status' => $product->status,
            ];
        });

        Log::info($formattedCartItems);

        return response()->json($formattedCartItems);
    }

    return view('cart.index');
}


public function minicart(Request $request, $orderItemId) {


    $orderItem = OrderItem::find($orderItemId);

    if ($request->wantsJson()) {
        $data = [
            'id' => $orderItem->id,
            'prod_id' => $orderItem->product_id,
            'prod_name' => $orderItem->product->name,
            'quantity' => $orderItem->quantity,
            'order_id' => $orderItem->order_id,
        ];

        return response()->json($data);
    }

    return view('cart.minicart', compact('orderItemId', 'orderItem'));
}

public function swap(Request $request)
    {
        // Access the content of swapItem from the request
        $swapItem = $request->all();

        // Perform any operations with swapItem
        // For example, you can access pivot data like this:
        $pivotData = $swapItem['pivot'];


        $quantity = $pivotData['quantity'];
        $productId = $pivotData['product_id'];
        $orderItemId = $pivotData['orderitem_id'];
        $orderId = $pivotData['order_id'];

        Log::info($quantity);
        Log::info($productId);
        Log::info($orderItemId);
        Log::info($orderId);

        $repprod = Product::find($productId);
        $orderItem = OrderItem::find($orderItemId);
        $orderItemQuantity = $orderItem->quantity;
        $product = $orderItem->product;
        $dateString = $orderItem->created_at->toDateString();
        //$dateTime = Carbon::parse($dateString);
        $existingData = DaySnapshot::where('date', $dateString)
                            ->where('product_id', $product->id) // Add this line for product_id filtering
                            ->first();


        $existingDatarepprod = DaySnapshot::where('date', $dateString)
                            ->where('product_id', $productId) // Add this line for product_id filtering
                            ->first();
        if (!is_null($existingData) && !is_null($existingDatarepprod)) {
            Log::info($existingData);

                // Decrement product quantity
                $product->quantity += $orderItemQuantity;
                $existingData->order_quantity -= $orderItemQuantity;
                $dayototall = $orderItemQuantity * $product->price;
                $existingData->order_total -= $dayototall;
                $repprod->quantity -= $quantity;
                $total = $quantity * $repprod->price;
                $existingDatarepprod->order_quantity += $quantity;
                $existingDatarepprod->order_total += $total;
            }

        $orderItemnew = new OrderItem();

        // Assuming you have properties like 'name', 'quantity', 'price', etc.
        $orderItemnew->price = $quantity * $product->price;
        $orderItemnew->quantity = $quantity;
        $orderItemnew->order_id = $orderId;
        $orderItemnew->product_id = $productId;

        // Set other properties as needed

        $orderItemnew->save();
        $repprod->save();
        $product->save();
        $orderItem->delete();
        $existingDatarepprod->save();
        $existingData->save();

        return response()->json(['message' => 'Swap operation successful']);
    }




public function store(Request $request)
{
    $request->validate([
        'barcode' => 'required|exists:products,barcode',
    ]);

    $barcode = $request->barcode;
    $customer_id = $request->customer_id;

    $product = Product::where('barcode', $barcode)->first();

    // Fetch cart item with pivot data
    $cartItem = DB::table('user_cart')
        ->where('customer_id', $customer_id)
        ->join('products', 'user_cart.product_id', '=', 'products.id')
        ->where('products.barcode', $barcode)
        ->select('user_cart.*', 'products.price', 'products.quantity as product_quantity')
        ->first();

    if ($cartItem) {
        // Check product quantity
        if ($product->quantity <= $cartItem->quantity) {
            return response([
                'message' => 'Product available only: ' . $product->quantity,
            ], 400);
        }

        // Update only quantity
        DB::table('user_cart')
            ->where('customer_id', $customer_id)
            ->where('product_id', $cartItem->product_id)
            ->update(['quantity' => $cartItem->quantity + 1]);
    } else {
        if ($product->quantity < 1) {
            return response([
                'message' => 'Product out of stock',
            ], 400);
        }

        // Insert a new cart item
        DB::table('user_cart')->insert([
            'customer_id' => $customer_id,
            'user_id' => auth()->user()->id, // Get the authenticated user's ID
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
    }

    return response('', 204);
}

public function changeQty(Request $request)
{
    $customer_id = $request->customer_id;
    $request->validate([
        'product_id' => 'required|exists:products,id',
        'quantity' => 'required|integer|min:1',
    ]);

    $product = Product::find($request->product_id);

    // Query the cart item by customer_id
    $cart = DB::table('user_cart')
        ->where('customer_id', $customer_id)
        ->where('product_id', $request->product_id)
        ->first();

    if ($cart) {
        // check product quantity
        if ($product->quantity < $request->quantity) {
            return response([
                'message' => 'Product available only: ' . $product->quantity,
            ], 400);
        }

        // Update the quantity
        DB::table('user_cart')
            ->where('customer_id', $customer_id)
            ->where('product_id', $request->product_id)
            ->update(['quantity' => $request->quantity]);
    }

    return response([
        'success' => true
    ]);
}


    public function delete(Request $request)
{
    $customer_id = $request->customer_id;
    $request->validate([
        'product_id' => 'required|integer|exists:products,id'
    ]);

    // Query the cart by customer_id
    $cartItem = DB::table('user_cart')
        ->where('customer_id', $customer_id)
        ->where('product_id', $request->product_id)
        ->first();

    if ($cartItem) {
        // Detach the cart item
        DB::table('user_cart')
            ->where('customer_id', $customer_id)
            ->where('product_id', $request->product_id)
            ->delete();
    }

    return response('', 204);
}

public function empty(Request $request)
{
    $customer_id = $request->customer_id;


    // Detach all cart items for the given customer_id
    DB::table('user_cart')->where('customer_id', $customer_id)->delete();

    return response('', 204);
}


    public function getCartItemsByCustomerId($customer_id)
{
    $cartItems = DB::table('user_cart')
        ->join('products', 'user_cart.product_id', '=', 'products.id')
        ->where('user_cart.customer_id', $customer_id)
        ->select('products.*', 'user_cart.quantity', 'user_cart.user_id', 'user_cart.product_id', 'user_cart.customer_id',)
        ->get();

    $formattedCartItems = $cartItems->map(function ($item) use ($customer_id) { // Pass $customer_id into the closure
        $product = DB::table('products')->where('id', $item->product_id)->first();

        return [
            'id' => $item->product_id,
            'name' => $item->name,
            'description' => $item->description,
            'image' => $item->image,
            'barcode' => $item->barcode,
            'category_id' => $item->category_id,
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
            'pivot' => [
                'user_id' => $item->user_id,
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'customer_id' => $customer_id,
            ],
            'price' => $product->price,
            'quantity' => $product->quantity,
            'status' => $product->status,
        ];
    });

    return response()->json($formattedCartItems);
}

public function dailyStockcountprod()
{

    $products = Product::all();

    return response()->json($products);
}

public function dailyStockcount()
{
    $currentDate = now()->toDateString();

    $existingData = Phystockcount::where('date', $currentDate)->get();


    return response()->json($existingData);
}

public function dailymoneyentry()
{
    $currentDate = now()->toDateString();

    $existing = Cashinginfo::where('date', $currentDate)->get();


    return response()->json($existing);
}

public function storeDailyStockcount(Request $request)
{

        // Get the product data from the request
        $productData = $request->all();

        // Define an array to store the product counts
        $productCounts = [];

        // Loop through each key-value pair in the product data
        foreach ($productData as $key => $value) {
            // Extract the product ID from the key
            $productId = str_replace('product[', '', $key); // Remove 'product['
            $productId = rtrim($productId, ']'); // Remove ']'

            // Add the product ID and count to the product counts array
            $productCounts[$productId] = $value;
        }

        // Now $productCounts contains the product IDs as keys and their counts as values
        // You can process this data further, such as storing it in the database

        // Example: Loop through each product and store the count in the database
        foreach ($productCounts as $productId => $count) {
            Phystockcount::create([
                'product_id' => $productId,
                'pre_close_qty' => $count,
                'date' => now()->toDateString(),
            ]);
        }


    return response()->json(['message' => 'Stock counts stored successfully'], 200);
}



}
