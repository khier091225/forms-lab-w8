<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Instructor;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CourseScaffoldTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_seeded_courses_are_listed_with_detail_links(): void
    {
        $this->seed();

        $response = $this->get(route('courses.index'));

        $response->assertOk()->assertViewIs('courses.index');
        $this->assertDatabaseCount('courses', 8);
        $this->assertDatabaseCount('instructors', 8);

        foreach (Course::all() as $course) {
            $response->assertSeeText($course->code)
                ->assertSeeText($course->title)
                ->assertSee(route('courses.show', $course));
        }
    }

    public function test_course_details_display_instructor_and_units_and_escape_content(): void
    {
        $instructor = Instructor::factory()->create(['name' => 'Ada Lovelace']);
        $course = Course::factory()->for($instructor)->create([
            'code' => 'CSC1',
            'title' => '<script>alert("course")</script>',
            'description' => 'An introduction to programming.',
            'units' => 3,
        ]);

        $response = $this->get(route('courses.show', $course));

        $response->assertSeeText('CSC1')
            ->assertSeeText('<script>alert("course")</script>')
            ->assertDontSee('<script>alert("course")</script>', false)
            ->assertSeeText('An introduction to programming.')
            ->assertSeeText('Instructor: Ada Lovelace')
            ->assertSeeText('Units: 3')
            ->assertSee(route('courses.edit', $course));
    }

    public function test_course_remains_viewable_after_its_instructor_is_deleted(): void
    {
        $course = Course::factory()->create();

        $course->instructor->delete();

        $this->assertModelExists($course);
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'instructor_id' => null,
        ]);
        $this->get(route('courses.show', $course))->assertSeeText('Instructor: —');
    }

    public function test_unknown_course_returns_not_found(): void
    {
        $this->get(route('courses.show', ['course' => 999]))->assertNotFound();
    }

    public function test_course_defaults_and_instructor_relationship_are_available(): void
    {
        $course = Course::factory()->create();

        $course->refresh();

        $this->assertSame(true, $course->is_active);
        $this->assertSame(3, $course->units);
        $this->assertSame($course->id, $course->instructor->courses()->first()->id);
    }
}
