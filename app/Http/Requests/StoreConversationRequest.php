<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConversationRequest extends FormRequest
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
            'sent_to' => ['required', 'array'],
            'subject' => ['required', 'string'],
            'message' => ['required', 'string'],
            'reason' => ['nullable'],
            'sent_to' => ['required', 'array'],
            'sent_to.*' => ['nullable', 'exists:users,id']
        ];
    }
}
