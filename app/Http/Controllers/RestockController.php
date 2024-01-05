<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Restock;
use Carbon\Carbon;
use PDF;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class RestockController extends Controller
{
    public function index(Request $request) {
        $restocks = new Restock();

        $restocks = $restocks->with(['product'])->latest()->paginate(10);


        return view('products.restockHistory', compact('restocks'));
    }


    public function report(Request $request){
        $request->validate([
            'start_date' => 'required|date',
        ]);
        // Get the restock history records with the specified start date
        $start_date = $request->input('start_date');
        $restocks = Restock::whereDate('restock_date', $start_date)
    ->groupBy('product_id')
    ->select('product_id', DB::raw('SUM(quantity_added) as total_quantity'))
    ->with('product') // Load the related product model
    ->get();
        // Generate the PDF report
        //$pdf = PDF::loadView('products.restockHistoryReport', compact('restocks', 'start_date'));
        // Open the print dialog
        //$pdf->setPaper('A4', 'portrait');
        //return $pdf->stream();
        return view('products.restockHistoryReport', compact('restocks', 'start_date'));
    }


    public function restockProduct($productId, $quantityAdded)
{

    // Find the product by its ID
    $product = Product::findOrFail($productId);

    // Create a new restock record
    $restock = new Restock([
        'quantity_added' => $quantityAdded,
        'restock_date' => Carbon::now(),
    ]);

    // Save the restock record for the product
    $product->restocks()->save($restock);

    // Update the product's quantity
    $product->quantity += $quantityAdded;
    $product->save();

    // Perform any additional logic as needed
    return redirect()->route('close-day');
}
}
