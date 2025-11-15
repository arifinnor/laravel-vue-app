<?php

namespace App\Http\Requests\Teachers;

use Illuminate\Foundation\Http\FormRequest;

class TeacherIndexRequest extends FormRequest
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
            'per_page' => ['sometimes', 'integer', 'in:'.implode(',', config('pagination.per_page_options'))],
            'with_trashed' => ['sometimes', 'string', 'in:none,only,all'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'cursor' => ['sometimes', 'nullable', 'string'],
        ];
    }

    /**
     * Get validated data with defaults.
     *
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated($key, $default);

        return [
            'per_page' => (int) ($validated['per_page'] ?? config('pagination.default')),
            'with_trashed' => $validated['with_trashed'] ?? 'none',
            'search' => $validated['search'] ?? null,
            'cursor' => $validated['cursor'] ?? null,
        ];
    }
}
