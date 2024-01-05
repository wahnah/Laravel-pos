<?php

namespace App\Http\Controllers;

use App\Models\Restock;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Order;
use App\Models\ProductSnapshot;
use App\Models\DaySnapshot;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductSnapshotController extends Controller
{
    public function openDay(Request $request)
{
    // Retrieve all products
    $products = Product::all();

    // Iterate over products and create snapshots
    foreach ($products as $product) {
        ProductSnapshot::create([
            'date' => now()->toDateString(),
            'product_id' => $product->id,
            'quantity' => $product->quantity,
        ]);
    }

    // Redirect to cart or any desired page
    return redirect()->route('cart.index');
}

public function closeDay()
{
    $currentDate = now()->toDateString();
    Log::info('Current Date:', ['date' => $currentDate]);

    // Check if data already exists for the current date
    $existingData = DaySnapshot::where('date', $currentDate)->get();

    if ($existingData->isNotEmpty()) {
        // Update existing data based on product_id
        $existingData->each(function ($data) use ($currentDate) {
            $productId = $data->product_id;

            $restocks = Restock::where('product_id', $productId)->whereDate('restock_date', $currentDate)->get();
            $restockQuantity = $restocks->sum('quantity_added');

            $orderItems = OrderItem::where('product_id', $productId)->whereDate('created_at', $currentDate)->get();
            $orderQuantity = $orderItems->sum('quantity');
            $orderTotal = $orderItems->sum('price');

            $data->update([
                'restock_quantity' => $restockQuantity,
                'order_quantity' => $orderQuantity,
                'order_total' => $orderTotal,
            ]);
        });

        $productIds = Product::pluck('id');

    // Create new DaySnapshot records for missing product IDs
    $missingProductIds = $productIds->diff($existingData->pluck('product_id'));

    foreach ($missingProductIds as $productId) {
        $restocks = Restock::where('product_id', $productId)->whereDate('restock_date', $currentDate)->get();
        $restockQuantity = $restocks->sum('quantity_added');

        $orderItems = OrderItem::where('product_id', $productId)->whereDate('created_at', $currentDate)->get();
        $orderQuantity = $orderItems->sum('quantity');
        $orderTotal = $orderItems->sum('price');

        DaySnapshot::create([
            'date' => $currentDate,
            'product_id' => $productId,
            'restock_quantity' => $restockQuantity,
            'order_quantity' => $orderQuantity,
            'order_total' => $orderTotal,
        ]);
    }
    } else {
        // Create new DaySnapshot records
        $restocks = Restock::whereDate('restock_date', $currentDate)->get();
        $restockData = $restocks->groupBy('product_id')->map(function ($groupedRestocks) {
            return [
                'product_id' => $groupedRestocks->first()->product_id,
                'restock_quantity' => $groupedRestocks->sum('quantity_added'),
            ];
        });

        $orderItems = OrderItem::whereDate('created_at', $currentDate)->get();

        $orderData = $orderItems->groupBy('product_id')->map(function ($groupedOrderItems) {
            $quantity = $groupedOrderItems->sum('quantity');
            $total = $groupedOrderItems->sum('price');
            return [
                'product_id' => $groupedOrderItems->first()->product_id,
                'quantity' => $quantity,
                'total' => $total,
            ];
        });

        $products = Product::all();

        $combinedData = $products->map(function ($product) use ($restockData, $orderData) {
            $productId = $product->id;
            $restockQuantity = $restockData[$productId]['restock_quantity'] ?? 0;
            $orderQuantity = $orderData[$productId]['quantity'] ?? 0;
            $orderTotal = $orderData[$productId]['total'] ?? 0.00;
            return [
                'product_id' => $productId,
                'restock_quantity' => $restockQuantity,
                'order_quantity' => $orderQuantity,
                'order_total' => $orderTotal,
            ];
        });

        Log::info('Combined Data:', ['data' => $combinedData]);

        foreach ($combinedData as $data) {
            DaySnapshot::create([
                'date' => $currentDate,
                'product_id' => $data['product_id'],
                'restock_quantity' => $data['restock_quantity'],
                'order_quantity' => $data['order_quantity'],
                'order_total' => $data['order_total'],
            ]);
        }
    }

    // Redirect to cart or any desired page
    return redirect()->back();
}

public function populateStockSheet()
{
    // Retrieve data from product_snapshots and day_snapshots tables
    $data = DB::table('product_snapshots')
    ->join('day_snapshots', function ($join) {
        $join->on('product_snapshots.product_id', '=', 'day_snapshots.product_id')
            ->on('product_snapshots.date', '=', 'day_snapshots.date');
    })
    ->join('products', 'product_snapshots.product_id', '=', 'products.id') // Assuming products table has the product name
    ->select(
        'product_snapshots.date',
        'product_snapshots.product_id',
        'products.name', // Include the product name in the selection
        'product_snapshots.quantity as open_qty',
        'day_snapshots.restock_quantity',
        'day_snapshots.order_quantity as sold_qty',
        'day_snapshots.order_total as amount_sold'
    )
    ->get();

        Log::info('Data:', ['data' => $data]);

    // Prepare the data for the view
    $stocksheetData = [];
    foreach ($data as $entry) {
        $closeQty = $entry->open_qty + $entry->restock_quantity - $entry->sold_qty;

        $stocksheetData[] = [
            'date' => $entry->date,
            'product_id' => $entry->name,
            'open_qty' => $entry->open_qty,
            'restock_qty' => $entry->restock_quantity,
            'sold_qty' => $entry->sold_qty,
            'amount_sold' => $entry->amount_sold,
            'close_qty' => $closeQty,
        ];
    }

    Log::info('stocksheetData:', ['data' => $stocksheetData]);

    // Pass the data to the view
    return view('orders.stockSheetIndex', ['stocksheetData' => $stocksheetData]);
}

public function populateStockSheetReport(Request $request)
{
    $request->validate([
        'start_date' => 'required|date',
    ]);

    $selectedDate = $request->input('start_date');
    $orders = Order::where('credit', 0)
    ->whereDate('updated_at', $selectedDate)
    ->get();
    foreach ($orders as $order) {
        $id = $order->id;

        $orderItems = OrderItem::where('order_id', $id)->get();
        $totalPrice = 0;
        foreach ($orderItems as $orderItem) {
            $totalPrice += $orderItem->price;
         }
         $order->totalPrice = $totalPrice;
    }

    // Retrieve data from product_snapshots, day_snapshots, and products tables for the selected date
    $data = DB::table('product_snapshots')
        ->join('day_snapshots', function ($join) {
            $join->on('product_snapshots.product_id', '=', 'day_snapshots.product_id')
                ->on('product_snapshots.date', '=', 'day_snapshots.date');
        })
        ->join('products', 'product_snapshots.product_id', '=', 'products.id')
        ->select(
            'product_snapshots.date',
            'product_snapshots.product_id',
            'products.name',
            'products.price',
            'products.orderprice', // Add orderprice column
            'product_snapshots.quantity as open_qty',
            'day_snapshots.restock_quantity',
            'day_snapshots.order_quantity as sold_qty',
            'day_snapshots.order_total as amount_sold'
        )
        ->where('product_snapshots.date', $selectedDate)
        ->get();

    // Prepare the data for the view
    $stocksheetData = [];
    $totalAmount = 0;
    $totalProfit = 0; // Initialize total profit variable

    foreach ($data as $entry) {
        $closeQty = $entry->open_qty + $entry->restock_quantity - $entry->sold_qty;

        // Check if orderprice is zero before calculating profit
        if ($entry->orderprice != 0) {
            $profit = ($entry->sold_qty * $entry->price) - ($entry->sold_qty * $entry->orderprice);
            $totalProfit += $profit; // Accumulate profit
        }else{
            $profit = 0;
        }

        $stocksheetData[] = [
            'date' => $entry->date,
            'product_id' => $entry->name,
            'product_price' => $entry->price,
            'order_price' => $entry->orderprice, // Add orderprice to stocksheetData
            'open_qty' => $entry->open_qty,
            'restock_qty' => $entry->restock_quantity,
            'sold_qty' => $entry->sold_qty,
            'amount_sold' => $entry->amount_sold,
            'close_qty' => $closeQty,
            'profit' => $profit, // Include profit in stocksheetData
        ];

        $totalAmount += $entry->amount_sold;
    }

    // Pass the data to the view
    return view('orders.stockSheetReport', [
        'stocksheetData' => $stocksheetData,
        'selectedDate' => $selectedDate,
        'totalAmount' => $totalAmount,
        'totalProfit' => $totalProfit,
        'orders' => $orders, // Include total profit in the view data
    ]);
}



}
