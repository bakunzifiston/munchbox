<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard) {}

    public function index(Request $request): View
    {
        $range = $request->string('range')->toString();
        if (! in_array($range, ['today', '7d', '30d'], true)) {
            $range = '7d';
        }

        return view('admin.dashboard.index', $this->dashboard->overview(range: $range));
    }
}
