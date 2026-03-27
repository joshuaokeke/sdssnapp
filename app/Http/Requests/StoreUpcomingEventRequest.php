<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

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
            // 'status' => ['required', 'in:1,0'],
            // 'status' => ['nullable', 'in:1,0'],
            // // draft, published
            'status' => ['nullable', 'in:draft,published'],
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
            'start_date' => $this->start_date ? Carbon::createFromFormat('d-m-Y', $this->start_date)->format('Y-m-d') : null,
            'start_time' => $this->start_time ? Carbon::parse($this->start_time)->format('H:i') : null,
        ]);
    }
}
