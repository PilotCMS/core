<?php

namespace Pilot\Core\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LivePreviewRenderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return config('pilot.editor_bridge.live_preview', true);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'source' => ['nullable', 'string', 'in:mysql,headless'],
            'locale' => ['nullable', 'string', 'max:12'],
            'content_id' => ['nullable', 'integer', 'exists:contents,id'],
            'slug' => ['nullable', 'string', 'max:255'],
            'space' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'array'],
            'story' => ['nullable', 'array'],
            'blocks' => ['nullable', 'array'],
            'body' => ['nullable', 'array'],
        ];
    }

    public function source(): string
    {
        return $this->input('source', $this->hasHeadlessPayload() ? 'headless' : config('cms.delivery_source', 'mysql'));
    }

    public function hasHeadlessPayload(): bool
    {
        return $this->hasAny(['content', 'story', 'blocks', 'body']);
    }
}
