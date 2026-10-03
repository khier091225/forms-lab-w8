<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Instructor;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class CourseValidationTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[TestWith(['1', true])]
    #[TestWith(['0', false])]
    public function test_valid_submission_creates_normalized_course_and_redirects(string $activeInput, bool $active): void
    {
        $instructor = Instructor::factory()->create();

        $response = $this->post(route('courses.store'), $this->validData([
            'code' => '  webdev3  ',
            'instructor_id' => $instructor->id,
            'is_active' => $activeInput,
            'image_path' => 'courses/unvalidated.png',
            'id' => 999,
        ]));

        $course = Course::sole();
        $response->assertRedirectToRoute('courses.show', $course)
            ->assertSessionHas('status', 'Course created.');
        $this->assertDatabaseCount('courses', 1);
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'code' => 'WEBDEV3',
            'title' => 'Web Development',
            'description' => 'Build web applications.',
            'units' => 3,
            'instructor_id' => $instructor->id,
            'is_active' => $active,
            'image_path' => null,
        ]);
        $this->assertNotSame(999, $course->id);
    }

    public function test_optional_fields_and_omitted_checkbox_are_saved_as_empty_and_inactive(): void
    {
        $response = $this->post(route('courses.store'), [
            'code' => 'CSC1',
            'title' => 'Programming',
            'units' => 1,
            'description' => '',
            'instructor_id' => '',
        ]);

        $response->assertSessionHasNoErrors()->assertSessionHas('status', 'Course created.');
        $this->assertDatabaseHas('courses', [
            'code' => 'CSC1',
            'description' => null,
            'instructor_id' => null,
            'is_active' => false,
            'units' => 1,
        ]);
    }

    #[TestWith(['CSC1', 'CSC1'])]
    #[TestWith(['  webdev3  ', 'WEBDEV3'])]
    public function test_update_accepts_own_code_and_saves_validated_changes(string $code, string $expectedCode): void
    {
        $course = Course::factory()->create(['code' => 'CSC1', 'image_path' => 'courses/existing.png']);

        $response = $this->post(route('courses.update', $course), $this->validData([
            '_method' => 'PUT',
            'code' => $code,
            'title' => 'Updated Course',
            'description' => '',
            'units' => 6,
            'is_active' => '0',
            'image_path' => 'courses/unvalidated.png',
        ]));

        $response->assertRedirectToRoute('courses.show', $course)
            ->assertSessionHas('status', 'Course updated.');
        $this->assertDatabaseCount('courses', 1);
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'code' => $expectedCode,
            'title' => 'Updated Course',
            'description' => null,
            'units' => 6,
            'is_active' => false,
            'instructor_id' => null,
            'image_path' => 'courses/existing.png',
        ]);
    }

    public function test_empty_submission_returns_required_errors_without_creating_a_course(): void
    {
        $response = $this->from(route('courses.create'))->post(route('courses.store'), []);

        $response->assertRedirectToRoute('courses.create')->assertSessionHasErrors([
            'code' => 'The code field is required.',
            'title' => 'The title field is required.',
            'units' => 'The units field is required.',
        ]);
        $this->assertDatabaseCount('courses', 0);
    }

    /**
     * @param  array<string, mixed>  $invalidData
     */
    #[DataProvider('invalidFields')]
    public function test_invalid_submission_returns_field_message_without_saving(array $invalidData, string $field, string $message): void
    {
        $response = $this->from(route('courses.create'))->post(route('courses.store'), $this->validData($invalidData));

        $response->assertRedirectToRoute('courses.create')->assertSessionHasErrors([$field => $message]);
        $this->assertDatabaseCount('courses', 0);
    }

    public function test_duplicate_code_is_rejected_after_normalization(): void
    {
        Course::factory()->create(['code' => 'WEBDEV3']);

        $response = $this->from(route('courses.create'))->post(route('courses.store'), $this->validData(['code' => ' webdev3 ']));

        $response->assertRedirectToRoute('courses.create')
            ->assertSessionHasErrors(['code' => 'That course code is already taken.']);
        $this->assertDatabaseCount('courses', 1);
    }

    #[TestWith(['WEBDEV3', 'That course code is already taken.'])]
    #[TestWith(['abc', 'The code must be 3–7 capital letters followed by one digit, e.g. WEBDEV3.'])]
    public function test_invalid_update_preserves_existing_course(string $code, string $message): void
    {
        $course = Course::factory()->create(['code' => 'CSC1', 'title' => 'Original Course']);
        Course::factory()->create(['code' => 'WEBDEV3']);

        $response = $this->from(route('courses.edit', $course))
            ->put(route('courses.update', $course), $this->validData(['code' => $code]));

        $response->assertRedirectToRoute('courses.edit', $course)
            ->assertSessionHasErrors(['code' => $message]);
        $this->assertDatabaseHas('courses', ['id' => $course->id, 'code' => 'CSC1', 'title' => 'Original Course']);
        $this->assertDatabaseCount('courses', 2);
    }

    public function test_failed_submission_displays_summary_errors_and_preserves_old_input(): void
    {
        $response = $this->from(route('courses.create'))->post(route('courses.store'), $this->validData([
            'code' => 'abc',
            'title' => '',
            'is_active' => '0',
        ]));

        $response->assertRedirectToRoute('courses.create')->assertSessionHasInput('code', 'abc');
        $this->assertDatabaseCount('courses', 0);
        $this->withCookie(config('session.cookie'), session()->getId())
            ->get(route('courses.create'))
            ->assertSeeText('Please fix the 2 error(s) below.')
            ->assertSeeText('The code must be 3–7 capital letters followed by one digit, e.g. WEBDEV3.')
            ->assertSeeText('The title field is required.')
            ->assertSee('value="abc"', false);
    }

    public function test_instructor_array_is_rejected_even_if_the_instructor_exists(): void
    {
        $instructor = Instructor::factory()->create();

        $response = $this->from(route('courses.create'))->post(route('courses.store'), $this->validData([
            'instructor_id' => [$instructor->id],
        ]));

        $response->assertRedirectToRoute('courses.create')
            ->assertSessionHasErrors(['instructor_id' => 'The instructor field must be an integer.']);
        $this->assertDatabaseCount('courses', 0);
    }

    public function test_created_course_with_valid_image_redirects_with_visible_flash_status(): void
    {
        $disk = Storage::fake('public');
        $image = UploadedFile::fake()->image('course.png')->size(2048);

        $response = $this->post(route('courses.store'), $this->validData(['image' => $image]));

        $course = Course::sole();
        $response->assertRedirectToRoute('courses.show', $course);
        $this->assertDatabaseHas('courses', ['id' => $course->id, 'code' => 'WEBDEV3', 'image_path' => $image->hashName('courses')]);
        $disk->assertExists($image->hashName('courses'));
        $this->get(route('courses.show', $course))->assertSeeText('Course created.');
        $this->get(route('courses.show', $course))->assertDontSeeText('Course created.');
    }

    #[TestWith([false, 'The image field must be an image.'])]
    #[TestWith([true, 'The image field must not be greater than 2048 kilobytes.'])]
    public function test_invalid_image_is_rejected_without_saving(bool $oversized, string $message): void
    {
        $disk = Storage::fake('public');
        $image = $oversized
            ? UploadedFile::fake()->image('large.png')->size(2049)
            : UploadedFile::fake()->create('document.txt', 1, 'text/plain');

        $response = $this->from(route('courses.create'))->post(route('courses.store'), $this->validData(['image' => $image]));

        $response->assertRedirectToRoute('courses.create')->assertSessionHasErrors(['image' => $message]);
        $this->assertDatabaseCount('courses', 0);
        $disk->assertDirectoryEmpty('/');
    }

    public function test_valid_image_is_accepted_at_the_size_limit(): void
    {
        $disk = Storage::fake('public');
        $course = Course::factory()->create(['code' => 'WEBDEV3', 'image_path' => 'courses/existing.png']);
        $image = UploadedFile::fake()->image('course.png')->size(2048);

        $response = $this->put(route('courses.update', $course), $this->validData(['image' => $image]));

        $response->assertRedirectToRoute('courses.show', $course)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('courses', ['id' => $course->id, 'image_path' => $image->hashName('courses')]);
        $disk->assertExists($image->hashName('courses'));
    }

    /**
     * @return array<string, array{array<string, mixed>, string, string}>
     */
    public static function invalidFields(): array
    {
        return [
            'code type' => [['code' => ['WEBDEV3']], 'code', 'The code field must be a string.'],
            'code length' => [['code' => str_repeat('A', 21)], 'code', 'The code field must not be greater than 20 characters.'],
            'code format' => [['code' => 'abc'], 'code', 'The code must be 3–7 capital letters followed by one digit, e.g. WEBDEV3.'],
            'title required' => [['title' => ''], 'title', 'The title field is required.'],
            'title type' => [['title' => ['invalid']], 'title', 'The title field must be a string.'],
            'title length' => [['title' => str_repeat('A', 256)], 'title', 'The title field must not be greater than 255 characters.'],
            'description type' => [['description' => ['invalid']], 'description', 'The description field must be a string.'],
            'description length' => [['description' => str_repeat('A', 1001)], 'description', 'The description field must not be greater than 1000 characters.'],
            'units required' => [['units' => ''], 'units', 'The units field is required.'],
            'units integer' => [['units' => '1.5'], 'units', 'The units field must be an integer.'],
            'units below range' => [['units' => 0], 'units', 'Units must be between 1 and 6.'],
            'units above range' => [['units' => 7], 'units', 'Units must be between 1 and 6.'],
            'unknown instructor' => [['instructor_id' => 999], 'instructor_id', 'The selected instructor is invalid.'],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validData(array $overrides = []): array
    {
        return array_replace([
            'code' => 'WEBDEV3',
            'title' => 'Web Development',
            'description' => 'Build web applications.',
            'units' => 3,
            'instructor_id' => '',
            'is_active' => '1',
        ], $overrides);
    }
}
