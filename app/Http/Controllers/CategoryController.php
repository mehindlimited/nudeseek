<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        // Your logic to fetch and display categories
        return view('categories.index');
    }
}
