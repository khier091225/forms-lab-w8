<?php

use App\Http\Controllers\CourseController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('courses.index'));
Route::resource('courses', CourseController::class);
