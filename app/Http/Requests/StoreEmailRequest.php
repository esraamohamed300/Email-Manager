<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // auth middleware already handles this
    }

    public function rules(): array
    {
        return [
            'composeEmail'   => ['required', 'email', 'exists:users,email'],
            'composeSubject' => ['required', 'string', 'max:255'],
            'composeBody'    => ['required', 'string'],
            'attachment'     => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'composeEmail.required' => 'Recipient email is required.',
            'composeEmail.email'    => 'Please enter a valid email address.',
            'composeEmail.exists'   => 'No user found with that email address.',
            'composeSubject.required' => 'Subject is required.',
            'composeBody.required'  => 'Message body is required.',
            'attachment.mimes'      => 'Only JPG, PNG, GIF and PDF files are allowed.',
            'attachment.max'        => 'Attachment must not exceed 5 MB.',
        ];
    }
}