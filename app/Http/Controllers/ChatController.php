<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index()
    {
        return view('chat');
    }

    public function list(Request $request)
    {
        $afterId = $request->query('after_id');

        $query = Message::whereNotNull('username')
            ->whereNotNull('content')
            ->orderBy('id', 'asc');

        if ($afterId) {
            $query->where('id', '>', $afterId);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|string|max:255',
            'content' => 'required|string|max:1000',
        ]);

        $message = Message::create($data);

        return response()->json($message, 201);
    }
}
