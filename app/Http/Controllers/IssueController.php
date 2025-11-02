<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class IssueController extends Controller
{
    /**
     * 問題報告・機能リクエストフォームページを表示
     */
    public function index()
    {
        return view('issues.index');
    }
}
