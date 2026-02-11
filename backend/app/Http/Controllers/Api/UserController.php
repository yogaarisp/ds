<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    public function index(Request $request)
    {
        $query = User::query();

        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        return response()->json($query->orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|in:owner,kasir,gudang',
            'avatar' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            $validated['avatar'] = $this->imageService->upload($request->file('avatar'), 'avatars');
        }

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        return response()->json($user, 201);
    }

    public function show(User $user)
    {
        return response()->json($user);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'email' => 'email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
            'role' => 'in:owner,kasir,gudang',
            'is_active' => 'boolean',
            'avatar' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar
            if ($user->avatar) {
                $this->imageService->delete($user->avatar);
            }
            $validated['avatar'] = $this->imageService->upload($request->file('avatar'), 'avatars');
        }

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json($user);
    }

    public function destroy(User $user)
    {
        // Delete avatar if exists
        if ($user->avatar) {
            $this->imageService->delete($user->avatar);
        }
        
        $user->delete();

        return response()->json(null, 204);
    }
}
