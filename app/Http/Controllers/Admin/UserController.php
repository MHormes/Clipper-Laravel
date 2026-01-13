<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index()
    {
        return Inertia::render('admin/users/Index', [
            'users' => User::orderBy('name')->get()
        ]);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|in:admin,user',
            'is_active' => 'required|boolean',
        ]);

        if ($user->id === $request->user()->id && $request->role !== 'admin') {
            return back()->with('error', 'You cannot remove your own admin role.');
        }

        $user->update($request->only('role', 'is_active'));

        return back()->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot delete yourself.');
        }

        // Remove their collection ONLY
        // Series and Clippers are preserved because foreign keys are 'onDelete: set null'
        $user->myCollection()->delete();
        
        $user->delete();

        return back()->with('success', 'User deleted successfully.');
    }
}
