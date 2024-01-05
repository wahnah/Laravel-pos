<?php

namespace App\Http\Controllers;

use App\Models\Product;
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



}
