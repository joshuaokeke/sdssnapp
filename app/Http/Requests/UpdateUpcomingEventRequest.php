<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class UpdateUpcomingEventRequest extends FormRequest
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
            'title' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'start_date' => ['nullable', 'date'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'end_date' => ['nullable', 'date'],
            // 'status' => ['nullable', 'in:true,false'],
            // 'status' => ['nullable', 'in:1,0'],

            'category' => ['nullable', 'string'],
            'registration_link' => ['nullable', 'url'],
            'venue' => ['nullable', 'string'],
            'location' => ['nullable', 'string'],

            // // draft, published
            'status' => ['nullable', 'in:draft,published,closed'],
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

    protected function prepareForValidation()
    {
        $this->merge([
            'start_date' => $this->start_date ? Carbon::createFromFormat('Y-m-d', $this->start_date)->format('Y-m-d') : null,
            'start_time' => $this->start_time ? Carbon::parse($this->start_time)->format('H:i') : null,
        ]);
    }
}
