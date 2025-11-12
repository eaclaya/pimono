<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Resources\UserResource;

class UserController extends Controller
{
    public function profile()
    {
        return new UserResource(auth()->user());
    }

    public function receivers(Request $request)
    {
        $query = User::whereNot('id', auth()->id());

        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query
            ->orderBy('name', 'asc')
            ->paginate(20);

        return UserResource::collection($users);
    }

    public function search(Request $request)
    {
        $search = $request->input('search');

        $users = User::where('name', 'like', "%{$search}%")
            ->orWhere('email', 'like', "%{$search}%")
            ->whereNot('id', auth()->id())
            ->orderBy('name', 'asc')
            ->paginate(20);

        return UserResource::collection($users);
    }
}
