<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ContactController extends Controller
{
    /**
     * お問い合わせフォームページを表示
     */
    public function index()
    {
        return view('contact.index');
    }
}
