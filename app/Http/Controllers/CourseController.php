<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\Course;
use App\Models\Instructor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

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
    public function store(StoreCourseRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('courses', 'public');
            abort_if($data['image_path'] === false, 500, 'The course image could not be saved.');
        }
        unset($data['image']);

        $course = Course::create($data);

        return redirect()->route('courses.show', $course)
            ->with('status', 'Course created.');
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
    public function update(UpdateCourseRequest $request, Course $course): RedirectResponse
    {
        $data = $request->validated();
        $oldImagePath = $course->image_path;

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('courses', 'public');
            abort_if($data['image_path'] === false, 500, 'The course image could not be saved.');
        }
        unset($data['image']);

        $course->update($data);

        if ($request->hasFile('image') && $oldImagePath) {
            Storage::disk('public')->delete($oldImagePath);
        }

        return redirect()->route('courses.show', $course)
            ->with('status', 'Course updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Course $course): RedirectResponse
    {
        if ($course->image_path) {
            Storage::disk('public')->delete($course->image_path);
        }

        $course->delete();

        return redirect()->route('courses.index')->with('status', 'Course deleted.');
    }
}
