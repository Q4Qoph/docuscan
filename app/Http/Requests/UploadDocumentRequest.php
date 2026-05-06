<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document' => [
                'required',
                'file',
                'mimes:pdf',
                'max:20480', // 20MB in kilobytes
            ],
            'criteria' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'document.required' => 'Please select a PDF file to upload.',
            'document.mimes'    => 'Only PDF files are accepted.',
            'document.max'      => 'The file must not be larger than 20MB.',
            'criteria.max'      => 'Criteria must not exceed 1000 characters.',
        ];
    }
}