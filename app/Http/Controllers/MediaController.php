<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function serve(Request $request, string $path)
    {
        $disk = Storage::disk('public');

        if (str_contains($path, '..') || ! $disk->exists($path)) {
            abort(404);
        }

        $root = rtrim($disk->path(''), '/\\');
        $full = realpath($disk->path($path));

        if ($full === false || strncmp($full, $root, strlen($root)) !== 0) {
            abort(404);
        }

        return $disk->response($path);
    }
}
