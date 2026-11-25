<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class RouteManagementController extends Controller
{
    public function index()
    {
        return view('admin.routes.manage');
    }
}
