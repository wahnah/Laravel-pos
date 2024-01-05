<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{

public function create()
{
    return view('users.create');
}

public function index()
    {
        if (request()->wantsJson()) {
            return response(
                User::all()
            );
        }
        $users = User::latest()->paginate(10);
        return view('users.index')->with('users', $users);
    }

public function store(Request $request)
{

    $users = User::all();
    // Validate the form data
    $validatedData = $request->validate([
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|string|min:8',
        'role' => 'required|in:0,1', // Validate role as 0 or 1
    ]);

    // Create the user
    User::create([
        'first_name' => $validatedData['first_name'],
        'last_name' => $validatedData['last_name'],
        'email' => $validatedData['email'],
        'password' => bcrypt($validatedData['password']),
        'role' => $validatedData['role'],
    ]);

    $users = User::latest()->paginate(10);
    return view('users.index')->with('users', $users);
}

public function destroy(User $user)
    {
        if ($user->avatar) {
            Storage::delete($user->avatar);
        }

        $user->delete();

       return response()->json([
           'success' => true
       ]);
    }


}
