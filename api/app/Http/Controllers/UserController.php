<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Resources\UserResource;

class UserController extends Controller
{
    public function recipients()
    {
        $users = User::whereNot('id', auth()->id())->get();

        return UserResource::collection($users);
    }
}
