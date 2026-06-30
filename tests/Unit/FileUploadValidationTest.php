<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

class FileUploadValidationTest extends TestCase
{
    // ── Test 1: invalid file type is rejected ────────────────────────────────
    public function test_invalid_file_type_is_rejected(): void
    {
        $file = UploadedFile::fake()->create('virus.exe', 100);

        $validator = Validator::make(
            ['attachment' => $file],
            ['attachment' => 'mimes:jpg,jpeg,png,gif,pdf']
        );

        $this->assertTrue($validator->fails());
    }

    // ── Test 2: valid image file passes validation ────────────────────────────
    public function test_valid_image_file_passes_validation(): void
    {
        // Use create() instead of image() — doesn't require GD extension
        $file = UploadedFile::fake()->create('photo.jpg', 100, 'image/jpeg');

        $validator = Validator::make(
            ['attachment' => $file],
            ['attachment' => 'mimes:jpg,jpeg,png,gif,pdf|max:5120']
        );

        $this->assertFalse($validator->fails());
    }
    // ── Test 3: file exceeding 5MB is rejected ───────────────────────────────
    public function test_file_exceeding_size_limit_is_rejected(): void
    {
        $file = UploadedFile::fake()->create('big.pdf', 6000); // 6MB

        $validator = Validator::make(
            ['attachment' => $file],
            ['attachment' => 'mimes:jpg,jpeg,png,gif,pdf|max:5120']
        );

        $this->assertTrue($validator->fails());
    }
}