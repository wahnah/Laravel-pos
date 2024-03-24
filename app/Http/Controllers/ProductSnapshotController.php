<?php

namespace App\Http\Controllers;

use App\Models\Restock;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Order;
use App\Models\ProductSnapshot;
use App\Models\DaySnapshot;
use App\Models\Phystockcount;
use App\Models\Cashinginfo;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PDF;
use Illuminate\Support\Facades\Mail;

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
    Log::info('Current Date1:', ['date' => $currentDate]);

    // Check if data already exists for the current date
    $existingData = DaySnapshot::where('date', $currentDate)->get();
    Log::info('Current data:', ['data' => $existingData->isNotEmpty()]);

    if ($existingData->isNotEmpty()) {


        // Update existing data based on product_id
        $existingData->each(function ($data) use ($currentDate) {
            $productId = $data->product_id;
            Log::info('product id data:', ['data' => $productId]);

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

public function compareCloseQtyToPrevDay()
{

    // Get the current date
    $currentDate = Carbon::today()->toDateString();

    // Get the previous date
    $prevDate = Carbon::yesterday()->toDateString();


    // Retrieve product data
    $data = DB::table('product_snapshots')
        ->join('day_snapshots', function ($join) {
            $join->on('product_snapshots.product_id', '=', 'day_snapshots.product_id')
                ->on('product_snapshots.date', '=', 'day_snapshots.date');
        })
        ->join('products', 'product_snapshots.product_id', '=', 'products.id')
        ->select(
            'product_snapshots.product_id',
            'products.name',
            'product_snapshots.quantity as open_qty',
            'day_snapshots.restock_quantity',
            'day_snapshots.order_quantity as sold_qty'
        )
        ->where('product_snapshots.date', $prevDate)
        ->get();

    // Prepare data for display
    $displayData = [];
    foreach ($data as $entry) {
        // Calculate close quantity for the current day
        $closeQty = $entry->open_qty + $entry->restock_quantity - $entry->sold_qty;

        // Get the previous day's close quantity from Phystockcount table
        $prevCloseQty = Phystockcount::where('product_id', $entry->product_id)
            ->where('date', $currentDate)
            ->value('pre_close_qty');


        // Calculate the unsigned difference
        $difference = abs($closeQty - $prevCloseQty);

        // Add data to the display array
        $displayData[] = [
            'product_id' => $entry->product_id,
            'product_name' => $entry->name,
            'close_qty_current_day' => $closeQty,
            'pre_close_qty_previous_day' => $prevCloseQty,
            'unsigned_difference' => $difference,
        ];
    }

    // Get yesterday's date
    $yesterday = Carbon::yesterday()->toDateString();

    // Retrieve orders with credit field equal to 0 created at yesterday's date
    $orders = Order::where('credit', 0)
                    ->whereDate('created_at', $yesterday)
                    ->get();

    $totalPriceSum = 0;
    foreach ($orders as $order) {
                $totalPriceSum += $order->items()->sum('price');
                    }



    $orderss = OrderItem::whereDate('created_at', $yesterday)->sum('price');

    // Calculate the total sum of prices from order items for these orders
    $totalPriceSumm = 0;






    $totalPriceSumm += $orderss;


    // Retrieve cashing information for yesterday
    $cashinginfo = Cashinginfo::whereDate('date', $currentDate)->first();

     if (!$cashinginfo) {
        // Handle case where cashinginfo for yesterday is not found
        // You might want to log this or display a message to the user
        // For simplicity, let's assume default values for cash_at_hand, bank_deposit, and momo_payments
        $cashinginfo = new Cashinginfo();
        $cashinginfo->cash_at_hand = 0;
        $cashinginfo->bank_deposit = 0;
        $cashinginfo->momo_payments = 0;
    }

    // Calculate the total credit (totalPriceSum) and subtract it from the total amount
    $totalAmount = $cashinginfo->cash_at_hand + $cashinginfo->direct_banked_transactions + $cashinginfo->momo_payments + $totalPriceSum;

    $diff = $totalPriceSumm - $totalAmount;

    $products = Product::where('quantity', '<', 10)->get();

    // Pass the data to the view
    return view('orders.dailyReports', ['displayData' => $displayData, 'products' => $products, 'diff' => $diff,
    'total_amount' => $totalAmount,
    'total_amount_all' => $totalPriceSumm]);
}



public function generatePDFAndSendEmaildaily(Request $request)
{
    // Get the current date
    $currentDate = Carbon::today()->toDateString();

    // Get the previous date
    $prevDate = Carbon::yesterday()->toDateString();


    // Retrieve product data
    $data = DB::table('product_snapshots')
        ->join('day_snapshots', function ($join) {
            $join->on('product_snapshots.product_id', '=', 'day_snapshots.product_id')
                ->on('product_snapshots.date', '=', 'day_snapshots.date');
        })
        ->join('products', 'product_snapshots.product_id', '=', 'products.id')
        ->select(
            'product_snapshots.product_id',
            'products.name',
            'product_snapshots.quantity as open_qty',
            'day_snapshots.restock_quantity',
            'day_snapshots.order_quantity as sold_qty'
        )
        ->where('product_snapshots.date', $prevDate)
        ->get();

    // Prepare data for display
    $displayData = [];
    foreach ($data as $entry) {
        // Calculate close quantity for the current day
        $closeQty = $entry->open_qty + $entry->restock_quantity - $entry->sold_qty;

        // Get the previous day's close quantity from Phystockcount table
        $prevCloseQty = Phystockcount::where('product_id', $entry->product_id)
            ->where('date', $currentDate)
            ->value('pre_close_qty');


        // Calculate the unsigned difference
        $difference = abs($closeQty - $prevCloseQty);

        // Add data to the display array
        $displayData[] = [
            'product_id' => $entry->product_id,
            'product_name' => $entry->name,
            'close_qty_current_day' => $closeQty,
            'pre_close_qty_previous_day' => $prevCloseQty,
            'unsigned_difference' => $difference,
        ];
    }

    // Get yesterday's date
    $yesterday = Carbon::yesterday()->toDateString();

    // Retrieve orders with credit field equal to 0 created at yesterday's date
    $orders = Order::where('credit', 0)
                    ->whereDate('created_at', $yesterday)
                    ->get();

    $totalPriceSum = 0;
    foreach ($orders as $order) {
                $totalPriceSum += $order->items()->sum('price');
                    }



    $orderss = OrderItem::whereDate('created_at', $yesterday)->sum('price');

    // Calculate the total sum of prices from order items for these orders
    $totalPriceSumm = 0;






    $totalPriceSumm += $orderss;


    // Retrieve cashing information for yesterday
    $cashinginfo = Cashinginfo::whereDate('date', $currentDate)->first();

     if (!$cashinginfo) {
        // Handle case where cashinginfo for yesterday is not found
        // You might want to log this or display a message to the user
        // For simplicity, let's assume default values for cash_at_hand, bank_deposit, and momo_payments
        $cashinginfo = new Cashinginfo();
        $cashinginfo->cash_at_hand = 0;
        $cashinginfo->bank_deposit = 0;
        $cashinginfo->momo_payments = 0;
    }

    // Calculate the total credit (totalPriceSum) and subtract it from the total amount
    $totalAmount = $cashinginfo->cash_at_hand + $cashinginfo->direct_banked_transactions + $cashinginfo->momo_payments + $totalPriceSum;

    $diff = $totalPriceSumm - $totalAmount;

    $products = Product::where('quantity', '<', 10)->get();


    // Generate PDF from the daily report view
    $pdf = PDF::loadView('pdf.dailyreport', ['displayData' => $displayData, 'products' => $products, 'diff' => $diff, 'total_amount' => $totalAmount, 'total_amount_all' => $totalPriceSumm]);

// Get the PDF content as a string
$pdf_content = $pdf->output();



// Send email with PDF attached
Mail::send('emails.email-template', [], function ($message) use ($pdf_content, $yesterday) {
    $subject = 'Daily Report for ' . $yesterday;
    $message->to(config('settings.email'))
            ->subject($subject)
            ->attachData($pdf_content, 'Daily_Report.pdf', [
                'mime' => 'application/pdf'
            ]);
});

// No need to delete the temporary PDF file if it's generated in memory

return redirect()->back()->with('message', 'PDF generated and email sent successfully.');
}

public function moneyinfo(Request $request)
    {
        // Validate the incoming request data
        $request->validate([
            'cash' => 'required|numeric',
            'momo' => 'required|numeric',
            'banked' => 'required|numeric',
        ]);

        // Create a new Cashinginfo instance and populate it with the request data
        $cashinginfo = new Cashinginfo();
        $cashinginfo->date = now()->toDateString(); // Assuming yesterday's date
        $cashinginfo->cash_at_hand = $request->cash;
        $cashinginfo->momo_payments = $request->momo;
        $cashinginfo->direct_banked_transactions = $request->banked;

        // Save the new Cashinginfo record
        $cashinginfo->save();

        // Optionally, you can return a response to indicate success or failure
        return response()->json(['message' => 'Yesterday\'s payment information recorded successfully'], 201);
    }



}
