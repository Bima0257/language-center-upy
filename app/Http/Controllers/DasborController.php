<?php

namespace App\Http\Controllers;

use App\Services\LayananDasbor;
use Inertia\Inertia;
use Inertia\Response;

class DasborController extends Controller
{
    public function __construct(
        private LayananDasbor $layananDasbor,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Dasbor', $this->layananDasbor->dataUntuk(auth()->user()));
    }
}
