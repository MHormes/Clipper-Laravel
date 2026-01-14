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
        $systemId = '019bb7be-fec4-7390-a7e1-63b1a0c1067f';

        // 1. Prevent self-deletion
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot delete yourself.');
        }

        // 2. Prevent deleting the System User
        if ($user->id === $systemId) {
            return back()->with('error', 'The system fallback user cannot be deleted.');
        }

        // 3. Re-assign Series and Clippers to the System User
        // This prevents 'cascade delete' from removing the records
        $user->requestedSeries()->update(['requested_by' => $systemId]);
        $user->acceptedSeries()->update(['accepted_by' => $systemId]);
        
        $user->requestedClippers()->update(['requested_by' => $systemId]);
        $user->acceptedClippers()->update(['accepted_by' => $systemId]);

        // 4. Remove their personal collection items
        // These are usually private to the user, so deleting them is fine
        $user->myCollection()->delete();
        
        // 5. Finally, delete the user
        $user->delete($user->id);

        return back()->with('success', 'User deleted and their contributions archived.');
    }
}
