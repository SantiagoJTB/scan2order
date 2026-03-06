<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// serve single page application root
Route::view('/', 'app');

// keep docs route accessible separately
Route::get('/docs', function () {
    // collect all routes for display
    $all = collect(app('router')->getRoutes())->map(function($route){
        $mw = $route->gatherMiddleware();
        $protected = collect($mw)->contains(function($m){
            return Str::startsWith($m, 'auth') || $m === 'auth:sanctum';
        });
        return [
            'methods' => $route->methods(),
            'uri' => $route->uri(),
            'name' => $route->getName(),
            'action' => $route->getActionName(),
            'middleware' => $mw,
            'status' => $protected ? 'protected' : 'public',
        ];
    });

    // group by first segment
    $grouped = $all->groupBy(function($r){
        $parts = explode('/', $r['uri']);
        return $parts[0] ?: 'root';
    });

    $descriptions = [
        'register' => 'User registration and authentication endpoints.',
        'login' => 'Authentication operations for issuing tokens.',
        'logout' => 'Invalidate current API token.',
        'health' => 'Simple health check of API and database.',
        'restaurants' => 'Manage restaurant records.',
        'categories' => 'CRUD for product categories.',
        'products' => 'CRUD operations on restaurant products.',
        'tables' => 'Restaurant table management.',
        'orders' => 'Create and query orders.',
        'order-items' => 'Manipulate items within an order.',
        'payments' => 'Handle order payments.',
        'root' => 'General routes and home page.',
    ];

    return view('api-docs', ['grouped' => $grouped, 'descriptions' => $descriptions]);
});
