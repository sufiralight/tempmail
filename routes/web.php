<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

if (app()->environment('local')) {
    Route::get('/_ui', function () {
        abort_unless(app()->environment('local'), 404);

        return view('ui-preview');
    })->name('ui.preview');
}
