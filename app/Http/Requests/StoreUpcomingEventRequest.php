<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUpcomingEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'banner' => ['required', 'image', 'max:5480'], // 2MB size
            'title' => ['required', 'string'],
            'description' => ['required', 'string'],
            'category' => ['required', 'string'],
            'start_time' => ['required', 'date_format:H:i'],
            'start_date' => ['required', 'date'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'end_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'in:true,false'],
            'contact_name' => ['nullable', 'string'],
            'contact_phone_number' => ['nullable', 'string'],
            'speakers' => ['nullable', 'array'],
            'speakers.*.name' => ['nullable', 'string'],
            'speakers.*.bio' => ['nullable', 'string'],
            'facilitators' => ['nullable', 'array'],
            'facilitators.*.name' => ['nullable', 'string'],
            'facilitators.*.bio' => ['nullable', 'string'],
        ];
    }
}
