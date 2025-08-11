<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $users = User::select('id', 'name', 'email', 'role', 'created_at');

            return datatables()->of($users)
                ->addIndexColumn()
                ->addColumn('role_badge', function ($row) {
                    $badgeClass = [
                        'admin' => 'bg-primary',
                        'inbound_staff' => 'bg-success',
                        'outbound_staff' => 'bg-info'
                    ];
                    return '<span class="badge ' . $badgeClass[$row->role] . '">' . ucfirst(str_replace('_', ' ', $row->role)) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    $btn = '<div class="d-flex gap-2">';
                    $btn .= '<a href="' . route('users.edit', $row->id) . '" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i></a>';
                    if (auth()->id() != $row->id) {
                        $btn .= '<button class="btn btn-danger btn-sm delete-btn" data-id="' . $row->id . '"><i class="fas fa-trash"></i></button>';
                    }
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['role_badge', 'action'])
                ->make(true);
        }

        return view('users.index');
    }

    public function create()
    {
        $roles = [
            'admin' => 'Admin',
            'inbound_staff' => 'Inbound Staff',
            'outbound_staff' => 'Outbound Staff'
        ];
        return view('users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::min(8)],
            'role' => 'required|in:admin,inbound_staff,outbound_staff'
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role
        ]);

        return redirect()->route('users.index')->with('success', 'User created successfully');
    }

    public function edit(User $user)
    {
        $roles = [
            'admin' => 'Admin',
            'inbound_staff' => 'Inbound Staff',
            'outbound_staff' => 'Outbound Staff'
        ];
        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'role' => 'required|in:admin,inbound_staff,outbound_staff'
        ]);

        $user->update($request->only(['name', 'email', 'role']));

        return redirect()->route('users.index')->with('success', 'User updated successfully');
    }

    public function destroy(User $user)
    {
        if (auth()->id() == $user->id) {
            return response()->json(['error' => 'You cannot delete your own account'], 422);
        }

        $user->delete();
        return response()->json(['success' => 'User deleted successfully']);
    }

    public function changePassword(Request $request, User $user)
    {
        $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)]
        ]);

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return redirect()->route('users.index')->with('success', 'Password changed successfully');
    }
}
