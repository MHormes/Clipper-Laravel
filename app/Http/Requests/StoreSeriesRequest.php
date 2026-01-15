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
        // Change this to true or check for admin role
        return $this->user()->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');
        $series = $this->route('series'); // Get the series model from the route
        $seriesId = is_object($series) ? $series->id : $series;

        return [
            'name' => [
                'required', 
                'string', 
                'max:255',
                $isUpdate ? Rule::unique('series')->ignore($seriesId) : 'unique:series,name'
            ],
            'custom' => 'required',
            'image' => $isUpdate ? 'nullable|image|max:10240' : 'required|image|max:10240',
            'clippers' => [
                'array',
                $this->boolean('custom') ? 'max:100' : 'max:4',
                // This calls the custom function below
                fn ($attribute, $value, $fail) => $this->validateClipperSlots($value, $fail)
            ],
            'clippers.*.image' => 'nullable|image|max:10240',
            'deleted_ids' => 'nullable|array',
            'deleted_ids.*' => 'string|exists:clippers,id'
        ];
    }

    /**
     * Custom logic to validate the nested clipper array.
     */
    protected function validateClipperSlots($value, $fail)
    {
        $isCustom = $this->boolean('custom');
        $clippers = collect($value);
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        if ($isCustom) {
            if ($clippers->isEmpty()) {
                return $fail('Custom series must have at least one clipper.');
            }
            
            // For custom series, every slot needs an ID (existing) or an Image (new)
            $allValid = $clippers->every(fn($slot) => 
                (isset($slot['id']) && !empty($slot['id'])) || 
                (isset($slot['image']) && $slot['image'] instanceof UploadedFile)
            );

            if (!$allValid) {
                $fail('All custom slots must have an image.');
            }
        } else {
            // For standard series, we only force one image if it's a brand new series
            if (!$isUpdate) {
                $hasOne = $clippers->contains(fn($slot) => 
                    isset($slot['image']) && $slot['image'] instanceof UploadedFile
                );
                if (!$hasOne) {
                    $fail('Please upload at least one clipper image.');
                }
            }
        }
    }
}