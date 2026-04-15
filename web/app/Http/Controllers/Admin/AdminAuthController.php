<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AdminAuthController extends Controller
{
    public function logout()
    {
        session()->forget(['admin_token', 'admin_role', 'admin_id']);
        return redirect('/login');
    }
}
