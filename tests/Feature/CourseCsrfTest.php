<?php

namespace Tests\Feature;

use App\Models\Course;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class CourseCsrfTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * Enable request forgery checks while keeping the isolated test configuration.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['env'] = 'local';
    }

    #[TestWith(['POST', null])]
    #[TestWith(['PUT', null])]
    #[TestWith(['DELETE', null])]
    #[TestWith(['POST', 'invalid-token'])]
    public function test_missing_or_invalid_token_returns_419_without_changing_courses(string $method, ?string $token): void
    {
        $course = Course::factory()->create(['code' => 'WEBDEV3', 'title' => 'Original Course']);
        $url = $method === 'POST' ? route('courses.store') : route('courses.update', $course);

        $response = $this->call($method, $url, [
            '_token' => $token, 'code' => 'WEBDEV4', 'title' => 'Changed Course', 'units' => 3,
        ]);

        $response->assertStatus(419);
        $this->assertDatabaseCount('courses', 1);
        $this->assertDatabaseHas('courses', ['id' => $course->id, 'code' => 'WEBDEV3', 'title' => 'Original Course']);
    }

    public function test_form_session_token_allows_creating_a_course(): void
    {
        $this->withSession([]);
        $token = csrf_token();

        $response = $this->post(route('courses.store'), [
            '_token' => $token, 'code' => 'WEBDEV3', 'title' => 'Web Development', 'units' => 3,
        ]);

        $course = Course::sole();
        $response->assertRedirectToRoute('courses.show', $course)
            ->assertSessionHas('status', 'Course created.');
        $this->assertDatabaseHas('courses', ['id' => $course->id, 'code' => 'WEBDEV3', 'title' => 'Web Development']);
    }
}
