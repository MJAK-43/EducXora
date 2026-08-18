<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

Route::get('/', fn (): Response => Inertia::render('Home'))
    ->name('home');

Route::middleware('local.only')
    ->get('/dev/ui', fn (): Response => Inertia::render('Dev/UiKit'))
    ->name('dev.ui');
