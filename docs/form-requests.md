# Form Requests Documentation

This document describes the implementation of Form Requests in the Sunopanel application.

## Overview

Form Requests are custom request classes that encapsulate validation logic for specific actions. They help keep controllers clean by moving validation rules out of controller methods.

## Implementation

The application uses FormRequest classes for the Track model:

1. `TrackStoreRequest` - Handles validation for creating new tracks
2. `TrackUpdateRequest` - Handles validation for updating existing tracks
3. `TrackDeleteRequest` - Handles validation for deleting tracks
4. `BulkTrackRequest` - Handles validation for bulk upload of tracks

## How It Works

The Track model includes helper methods that define validation rules:

- `getStoreFields()` - Rules for creating tracks
- `getUpdateFields()` - Rules for updating tracks
- `getDeleteFields()` - Rules for deleting tracks

This approach centralizes all validation logic related to the Track model and makes it easier to maintain.

## How to Create New FormRequest Classes

You can create new FormRequest classes for other models following these steps:

1. Create a new class that extends `Illuminate\Foundation\Http\FormRequest`
2. Define the `authorize()` method (usually returns `true`)
3. Define the `rules()` method with appropriate validation rules

Example:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MyModelRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'field1' => ['required', 'string', 'max:255'],
            'field2' => ['required', 'numeric'],
            // Add other rules as needed
        ];
    }
}
```

## Using FormRequest in Controllers

To use a FormRequest in a controller, simply type-hint it in the method signature:

```php
public function store(MyModelRequest $request)
{
    // Validation has already been performed by the FormRequest
    // If validation fails, a redirect response is automatically generated
    
    // Proceed with creating the model
    $model = MyModel::create($request->validated());
    
    return redirect()->route('my_models.index')
        ->with('success', 'MyModel created successfully!');
}
```

## Benefits

- Clean controllers with less code
- Centralized validation logic
- Reusable validation rules
- Easy to test
- Improved code organization

## Testing

The FormRequest classes are tested in the `TrackRequestTest` class. This ensures that validation rules work as expected. 