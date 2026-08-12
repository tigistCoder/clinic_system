<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('role', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role') && $request->role !== 'all') {
            $query->where('role', $request->role);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $sortBy = $request->get('sort_by', 'name');
        switch ($sortBy) {
            case 'status':
                $query->orderBy('status')->orderBy('name');
                break;
            case 'role':
                $query->orderBy('role')->orderBy('name');
                break;
            default:
                $query->orderBy('name');
        }

        $staff = $query->paginate(8)->appends($request->query());

        $stats = [
            'total'          => User::count(),
            'active'         => User::where('status', 'active')->count(),
            'inactive'       => User::where('status', 'inactive')->count(),
            'doctors_count'  => User::where('role', 'doctor')->count(),
            'nurses_count'   => User::where('role', 'nurse')->count(),
            'lab_count'      => User::where('role', 'lab')->count(),
            'pharmacy_count' => User::where('role', 'pharmacy')->count(),
            'admin_count'    => User::whereIn('role', ['admin', 'reception'])->count(),
        ];

        return view('staff.index', compact('staff', 'stats'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'regex:/^[a-zA-Z\s]+$/', 'min:3', 'max:255'],
            'email'    => ['required', 'string', 'email:rfc', 'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role'     => ['required', 'in:admin,reception,nurse,doctor,lab,pharmacy'],
            'status'   => ['nullable', 'in:active,inactive'],
        ], [
            'name.regex'   => 'Staff full name must contain letters only.',
            'email.email'  => 'Please enter a valid email address.',
            'email.regex'  => 'Please enter a valid email address with a domain dot (e.g. user@domain.com).',
            'email.unique' => 'This email is already registered.',
        ]);

        try {
            DB::beginTransaction();

            $email = strtolower(trim($validated['email']));
            $username = $this->makeUniqueUsername($email, $validated['name']);
            $status   = $validated['status'] ?? 'active';

            User::create([
                'name'     => $validated['name'],
                'username' => $username,
                'email'    => $email,
                'password' => $validated['password'],
                'role'     => $validated['role'],
                'status'   => $status,
            ]);

            DB::commit();

            return redirect()->route('staff.index')->with('success', 'Staff member registered successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Staff store failed: " . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Operation failed: ' . $e->getMessage());
        }
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'regex:/^[a-zA-Z\s]+$/', 'min:3', 'max:255'],
            'email'    => ['required', 'string', 'email:rfc', 'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'string', 'min:6'],
            'role'     => ['required', 'in:admin,reception,nurse,doctor,lab,pharmacy'],
            'status'   => ['required', 'in:active,inactive'],
        ], [
            'name.regex'   => 'Staff full name must contain letters only.',
            'email.email'  => 'Please enter a valid email address.',
            'email.regex'  => 'Please enter a valid email address with a domain dot (e.g. user@domain.com).',
            'email.unique' => 'This email is already registered.',
        ]);

        try {
            DB::beginTransaction();

            $data['email'] = strtolower(trim($data['email']));

            if (! empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }

            $user->update($data);

            DB::commit();

            return redirect()->route('staff.index')->with('success', 'Staff member updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Staff update failed for User #{$user->id}: " . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Operation failed: ' . $e->getMessage());
        }
    }

    public function destroy(User $user)
    {
        try {
            DB::beginTransaction();

            $user->delete();

            DB::commit();

            return redirect()->route('staff.index')->with('success', 'Staff member deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Staff delete failed for User #{$user->id}: " . $e->getMessage());
            return redirect()->back()->with('error', 'Operation failed: ' . $e->getMessage());
        }
    }

    private function makeUniqueUsername(string $email, string $name): string
    {
        $base = Str::slug(Str::before($email, '@') ?: $name);
        $username = $base;
        $counter = 1;

        while (User::where('username', $username)->exists()) {
            $username = $base.'-'.$counter;
            $counter++;
        }

        return $username;
    }
}