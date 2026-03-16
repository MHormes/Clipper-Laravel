<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Http\UploadedFile;

class StoreSeriesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Allow any authenticated user to proceed.
        // Specific route protection is handled by middleware in web.php (e.g., admin check for updates).
        return !!$this->user();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');
        $isClipperRequest = $this->routeIs('series.store-clipper-request');
        $series = $this->route('series'); // Get the series model from the route
        $seriesId = is_object($series) ? $series->id : $series;

        $rules = [
            'name' => [
                $isClipperRequest ? 'nullable' : 'required', 
                'string', 
                'max:255',
                ($isUpdate || $isClipperRequest) ? Rule::unique('series')->ignore($seriesId) : 'unique:series,name'
            ],
            'custom' => 'boolean',
            'image' => ($isUpdate || $isClipperRequest) ? 'nullable|image|max:10240' : 'required|image|max:10240',
            'clippers' => [
                'array',
                $this->boolean('custom') ? 'max:100' : 'max:4',
                // This calls the custom function below
                fn ($attribute, $value, $fail) => $this->validateClipperSlots($value, $fail, $isClipperRequest)
            ],
            'clippers.*.image' => 'nullable|image|max:10240',
            'clippers.*.id' => 'nullable|string',
            'clippers.*.series_number' => 'nullable|integer|min:1',
            'clippers.*.auto_add_to_collection' => 'boolean',
            'deleted_ids' => 'nullable|array',
            'deleted_ids.*' => 'string|exists:clippers,id'
        ];

        return $rules;
    }

    /**
     * Custom logic to validate the nested clipper array.
     */
    protected function validateClipperSlots($value, $fail, $isClipperRequest = false)
    {
        $clippers = collect($value);
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');
        $isCustom = $this->boolean('custom');

        // 1. Initial global checks
        if ($clippers->isEmpty() && !$isClipperRequest) {
            $fail('The series must have at least one clipper slot.');
            return;
        }

        // 2. Interaction-specific checks
        if ($isClipperRequest) {
            // Must have at least one NEW image being suggested
            $hasNew = $clippers->contains(fn($c) => !empty($c['image']) && $c['image'] instanceof UploadedFile);
            if (!$hasNew) {
                $fail('Please upload at least one new clipper image for your request.');
            }
        }

        // 3. Per-slot integrity checks
        foreach ($value as $index => $slot) {
            $hasId = !empty($slot['id'] ?? null);
            $hasImage = isset($slot['image']) && $slot['image'] instanceof UploadedFile;

            // Strict check for Custom series: every slot MUST have something
            if ($isCustom && !$hasId && !$hasImage) {
                // We skip this check if it's a clipper request and the slot is simply untouched (no ID, no Image)
                // but wait... in custom series, if a slot is there, it must be valid.
                $fail("Clipper slot #" . ($index + 1) . " must have an image.");
            }

            // General check for brand new series: must have at least one image total
            if (!$isUpdate && !$isClipperRequest && $clippers->every(fn($c) => empty($c['image']))) {
                 $fail('Please upload at least one clipper image.');
                 break;
            }
        }
    }
}
