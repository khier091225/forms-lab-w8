<?php

namespace Tests\Feature;

use App\Models\Course;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class CourseUploadTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_image_is_stored_with_a_generated_filename_and_relative_path(): void
    {
        $disk = Storage::fake('public');
        $image = UploadedFile::fake()->image('../../original.png');

        $response = $this->post(route('courses.store'), [
            ...$this->validData(), 'image' => $image,
        ]);

        $course = Course::sole();
        $response->assertRedirectToRoute('courses.show', $course)
            ->assertSessionHas('status', 'Course created.');
        $this->assertMatchesRegularExpression('/\Acourses\/[A-Za-z0-9]{40}\.(jpg|png)\z/', $course->image_path);
        $this->assertDatabaseHas('courses', ['id' => $course->id, 'image_path' => $image->hashName('courses')]);
        $disk->assertExists($course->image_path);
        $disk->assertMissing('original.png');
    }

    public function test_script_disguised_as_an_image_is_rejected_using_its_contents(): void
    {
        $disk = Storage::fake('public');
        $script = UploadedFile::fake()->createWithContent('photo.jpg', '<?php echo "not an image";');
        $image = new UploadedFile($script->getPathname(), 'photo.jpg', 'image/jpeg', null, true);

        $response = $this->from(route('courses.create'))->post(route('courses.store'), [
            ...$this->validData(), 'image' => $image,
        ]);

        $response->assertRedirectToRoute('courses.create')
            ->assertSessionHasErrors(['image' => 'The image field must be an image.']);
        $this->assertDatabaseCount('courses', 0);
        $disk->assertDirectoryEmpty('/');
    }

    public function test_replacement_image_removes_old_file_and_keeps_unrelated_files(): void
    {
        $disk = Storage::fake('public');
        $disk->put('courses/old.png', 'old image');
        $disk->put('courses/unrelated.png', 'another course image');
        $course = Course::factory()->create(['code' => 'WEBDEV3', 'image_path' => 'courses/old.png']);
        $image = UploadedFile::fake()->image('replacement.png');

        $response = $this->put(route('courses.update', $course), [
            ...$this->validData(), 'image' => $image,
        ]);

        $response->assertRedirectToRoute('courses.show', $course)
            ->assertSessionHas('status', 'Course updated.');
        $this->assertDatabaseHas('courses', ['id' => $course->id, 'image_path' => $image->hashName('courses')]);
        $disk->assertExists([$image->hashName('courses'), 'courses/unrelated.png']);
        $disk->assertMissing('courses/old.png');
    }

    #[TestWith([null])]
    #[TestWith(['courses/existing.png'])]
    public function test_update_without_upload_keeps_current_image(?string $imagePath): void
    {
        $disk = Storage::fake('public');
        if ($imagePath) {
            $disk->put($imagePath, 'existing image');
        }
        $course = Course::factory()->create(['code' => 'WEBDEV3', 'image_path' => $imagePath]);

        $response = $this->put(route('courses.update', $course), $this->validData());

        $response->assertRedirectToRoute('courses.show', $course);
        $this->assertDatabaseHas('courses', ['id' => $course->id, 'image_path' => $imagePath]);
        $this->assertSame($imagePath ? [$imagePath] : [], $disk->allFiles());
    }

    public function test_failed_edit_keeps_image_and_restores_unchecked_active(): void
    {
        $disk = Storage::fake('public');
        $disk->put('courses/existing.png', 'existing image');
        $course = Course::factory()->create(['code' => 'WEBDEV3', 'image_path' => 'courses/existing.png']);
        $image = UploadedFile::fake()->image('replacement.png');

        $response = $this->from(route('courses.edit', $course))
            ->put(route('courses.update', $course), [
                ...$this->validData(), 'title' => '', 'is_active' => '0', 'image' => $image,
            ]);

        $response->assertRedirectToRoute('courses.edit', $course)
            ->assertSessionHasErrors(['title' => 'The title field is required.']);
        $this->assertDatabaseHas('courses', ['id' => $course->id, 'image_path' => 'courses/existing.png', 'is_active' => true]);
        $this->assertSame(['courses/existing.png'], $disk->allFiles());
        $form = $this->withCookie(config('session.cookie'), session()->getId())
            ->get(route('courses.edit', $course));
        $form->assertSeeText('The title field is required.');
        $document = new DOMDocument;
        $document->loadHTML($form->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $this->assertSame(false, (new DOMXPath($document))->evaluate('boolean(//input[@type="checkbox" and @name="is_active"]/@checked)'));
    }

    public function test_failed_replacement_write_preserves_original_course_and_file(): void
    {
        $disk = Storage::fake('public');
        $disk->put('existing.png', 'existing image');
        $disk->put('courses', 'a file prevents creating the upload directory');
        $course = Course::factory()->create(['code' => 'WEBDEV3', 'image_path' => 'existing.png', 'title' => 'Original Course']);
        $image = UploadedFile::fake()->image('replacement.png');

        $response = $this->put(route('courses.update', $course), [
            ...$this->validData(), 'image' => $image,
        ]);

        $response->assertInternalServerError();
        $this->assertDatabaseHas('courses', ['id' => $course->id, 'image_path' => 'existing.png', 'title' => 'Original Course']);
        $disk->assertExists('existing.png');
    }

    #[TestWith([null])]
    #[TestWith(['courses/existing.png'])]
    public function test_delete_removes_course_and_its_image_only(?string $imagePath): void
    {
        $disk = Storage::fake('public');
        if ($imagePath) {
            $disk->put($imagePath, 'course image');
        }
        $disk->put('courses/unrelated.png', 'another course image');
        $course = Course::factory()->create(['image_path' => $imagePath]);
        $instructor = $course->instructor;

        $response = $this->post(route('courses.destroy', $course), ['_method' => 'DELETE']);

        $response->assertRedirectToRoute('courses.index')
            ->assertSessionHas('status', 'Course deleted.');
        $this->assertModelMissing($course);
        $this->assertModelExists($instructor);
        $this->assertSame(['courses/unrelated.png'], $disk->allFiles());
        $this->get(route('courses.index'))->assertSeeText('Course deleted.');
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function test_detail_page_shows_image_when_present_and_has_protected_delete_form(bool $hasImage): void
    {
        $course = Course::factory()->create([
            'title' => '<script>alert("image")</script>',
            'image_path' => $hasImage ? 'courses/example.png' : null,
            'is_active' => $hasImage,
        ]);

        $response = $this->get(route('courses.show', $course));

        $response->assertOk()->assertSeeText($hasImage ? 'Active' : 'Inactive');
        $document = new DOMDocument;
        $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $page = new DOMXPath($document);
        $this->assertSame($hasImage ? asset('storage/courses/example.png') : '', $page->evaluate('string(//img/@src)'));
        $this->assertSame($hasImage ? '<script>alert("image")</script>' : '', $page->evaluate('string(//img/@alt)'));
        $this->assertSame(route('courses.destroy', $course), $page->evaluate('string(//form/@action)'));
        $this->assertSame('POST', $page->evaluate('string(//form/@method)'));
        $this->assertSame('DELETE', $page->evaluate('string(//input[@name="_method"]/@value)'));
        $this->assertSame(csrf_token(), $page->evaluate('string(//input[@name="_token"]/@value)'));
        $this->assertSame("return confirm('Delete this course? This cannot be undone.');", $page->evaluate('string(//form/@onsubmit)'));
        $response->assertDontSee('<script>alert("image")</script>', false);
    }

    public function test_deleting_unknown_course_returns_not_found_without_touching_files(): void
    {
        $disk = Storage::fake('public');
        $disk->put('courses/existing.png', 'course image');

        $response = $this->delete(route('courses.destroy', ['course' => 999]));

        $response->assertNotFound();
        $disk->assertExists('courses/existing.png');
    }

    /**
     * @return array<string, mixed>
     */
    private function validData(): array
    {
        return ['code' => 'WEBDEV3', 'title' => 'Web Development', 'units' => 3, 'is_active' => '1'];
    }
}
