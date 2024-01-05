<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category; // Import the Category model

class CategoryController extends Controller
{
    public function index()
    {
        if (request()->wantsJson()) {
            return response(
                Category::all()
            );
        }
    }

    public function category()
    {
        return view('products.category');
    }

public function addCategory(Request $request)
{
    $category = Category::create([
        'name' => $request->name,
    ]);

    if (!$category) {
        return redirect()->back()->with('error', 'Sorry, there was a problem while adding the category.');
    }

    return redirect()->back()->with('success', 'Success, the category has been added.');
}
}
