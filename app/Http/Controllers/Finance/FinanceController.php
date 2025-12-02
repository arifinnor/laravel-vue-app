<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class FinanceController extends Controller
{
    /**
     * Display the finance index page.
     */
    public function index(): Response
    {
        return Inertia::render('Finance/Index');
    }
}
