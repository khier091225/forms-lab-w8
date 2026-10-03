<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Instructor;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class CourseFormTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_create_form_has_blank_fields_defaults_and_sorted_instructors(): void
    {
        Instructor::factory()->create(['name' => 'Zoe Rivera']);
        Instructor::factory()->create(['name' => 'Ada Lovelace']);

        $response = $this->get(route('courses.create'));

        $response->assertOk()->assertSeeTextInOrder(['Ada Lovelace', 'Zoe Rivera']);
        $form = $this->formXPath($response);
        $this->assertSame(route('courses.store'), $form->evaluate('string(//form/@action)'));
        $this->assertSame('POST', $form->evaluate('string(//form/@method)'));
        $this->assertSame('multipart/form-data', $form->evaluate('string(//form/@enctype)'));
        $this->assertSame(csrf_token(), $form->evaluate('string(//input[@name="_token"]/@value)'));
        $this->assertSame('', $form->evaluate('string(//input[@name="code"]/@value)'));
        $this->assertSame('', $form->evaluate('string(//input[@name="title"]/@value)'));
        $this->assertSame('3', $form->evaluate('string(//input[@name="units"]/@value)'));
        $this->assertSame(true, $form->evaluate('boolean(//input[@type="checkbox"]/@checked)'));
        $this->assertSame('0', $form->evaluate('string(//input[@type="hidden" and @name="is_active"]/@value)'));
        $this->assertSame('file', $form->evaluate('string(//input[@name="image"]/@type)'));
    }

    public function test_edit_form_displays_saved_values_and_current_image(): void
    {
        $course = Course::factory()->create([
            'code' => 'CSC2',
            'title' => 'Programming Fundamentals',
            'description' => 'Learn to write programs.',
            'units' => 4,
            'is_active' => false,
            'image_path' => 'courses/example.png',
        ]);

        $response = $this->get(route('courses.edit', $course));

        $response->assertOk()->assertSeeText('Edit CSC2')->assertSeeText('Update');
        $form = $this->formXPath($response);
        $this->assertSame(route('courses.update', $course), $form->evaluate('string(//form/@action)'));
        $this->assertSame('POST', $form->evaluate('string(//form/@method)'));
        $this->assertSame('multipart/form-data', $form->evaluate('string(//form/@enctype)'));
        $this->assertSame(csrf_token(), $form->evaluate('string(//input[@name="_token"]/@value)'));
        $this->assertSame('PUT', $form->evaluate('string(//input[@name="_method"]/@value)'));
        $this->assertSame('CSC2', $form->evaluate('string(//input[@name="code"]/@value)'));
        $this->assertSame('Programming Fundamentals', $form->evaluate('string(//input[@name="title"]/@value)'));
        $this->assertSame('Learn to write programs.', $form->evaluate('string(//textarea[@name="description"])'));
        $this->assertSame('4', $form->evaluate('string(//input[@name="units"]/@value)'));
        $this->assertSame((string) $course->instructor_id, $form->evaluate('string(//select/option[@selected]/@value)'));
        $this->assertSame(false, $form->evaluate('boolean(//input[@type="checkbox"]/@checked)'));
        $this->assertSame(asset('storage/courses/example.png'), $form->evaluate('string(//img[@alt="Current image"]/@src)'));
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function test_form_restores_old_input_and_errors_including_unchecked_active(bool $editing): void
    {
        $course = Course::factory()->create();
        $instructor = Instructor::factory()->create();
        $this->withSession([
            '_old_input' => [
                'code' => 'CSC4',
                'title' => '<script>alert("old input")</script>',
                'description' => 'My revised description.',
                'units' => '2',
                'instructor_id' => (string) $instructor->id,
                'is_active' => '0',
            ],
            'errors' => [
                'default' => [
                    'messages' => ['title' => ['The title field is required.']],
                    'format' => ':message',
                ],
            ],
        ]);

        $response = $this->get($editing ? route('courses.edit', $course) : route('courses.create'));

        $response->assertSeeText('The title field is required.')
            ->assertDontSee('<script>alert("old input")</script>', false);
        $form = $this->formXPath($response);
        $this->assertSame('CSC4', $form->evaluate('string(//input[@name="code"]/@value)'));
        $this->assertSame('<script>alert("old input")</script>', $form->evaluate('string(//input[@name="title"]/@value)'));
        $this->assertSame('My revised description.', $form->evaluate('string(//textarea[@name="description"])'));
        $this->assertSame('2', $form->evaluate('string(//input[@name="units"]/@value)'));
        $this->assertSame((string) $instructor->id, $form->evaluate('string(//select/option[@selected]/@value)'));
        $this->assertSame(false, $form->evaluate('boolean(//input[@type="checkbox"]/@checked)'));
    }

    private function formXPath(TestResponse $response): DOMXPath
    {
        $document = new DOMDocument;
        $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);

        return new DOMXPath($document);
    }
}
