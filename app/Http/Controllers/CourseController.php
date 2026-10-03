<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Instructor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('courses.index', [
            'courses' => Course::with('instructor')->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('courses.create', [
            'course' => new Course,
            'instructors' => Instructor::orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): void
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Course $course): View
    {
        return view('courses.show', compact('course'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Course $course): View
    {
        return view('courses.edit', [
            'course' => $course,
            'instructors' => Instructor::orderBy('name')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Course $course): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Course $course): void
    {
        //
    }
}
