<?php

namespace App\Http\Controllers;

use App\Models\Debate;
use Illuminate\Http\Request;

class DebateController extends Controller
{
    public function show(Debate $debate)
    {
        // $room = $debate->room;
        return view('debate.show', compact('debate'));
    }
}
