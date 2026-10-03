<?php

namespace Tests\Unit;

use App\Rules\ValidCourseCode;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class ValidCourseCodeTest extends TestCase
{
    #[TestWith(['ABC0'])]
    #[TestWith(['ITEC3'])]
    #[TestWith(['WEBDEV3'])]
    #[TestWith(['ABCDEFG9'])]
    public function test_accepts_three_to_seven_capital_letters_followed_by_one_digit(string $value): void
    {
        $errors = [];

        (new ValidCourseCode)->validate('code', $value, function (string $message) use (&$errors): void {
            $errors[] = $message;
        });

        $this->assertSame([], $errors);
    }

    #[TestWith(['AB1'])]
    #[TestWith(['ABCDEFGH1'])]
    #[TestWith(['ABC'])]
    #[TestWith(['ABC12'])]
    #[TestWith(['abc1'])]
    #[TestWith(['ABC_1'])]
    #[TestWith(["ABC1\n"])]
    #[TestWith([123])]
    #[TestWith([[]])]
    #[TestWith([null])]
    public function test_rejects_invalid_format_and_non_string_values(mixed $value): void
    {
        $errors = [];

        (new ValidCourseCode)->validate('code', $value, function (string $message) use (&$errors): void {
            $errors[] = $message;
        });

        $this->assertSame([
            'The :attribute must be 3–7 capital letters followed by one digit, e.g. WEBDEV3.',
        ], $errors);
    }
}
