<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LayananAnalitik;
use Inertia\Inertia;
use Inertia\Response;

class AnalitikController extends Controller
{
    public function __construct(
        private LayananAnalitik $layananAnalitik,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Analitik/index', $this->layananAnalitik->data());
    }
}
