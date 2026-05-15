# Validation Guide

## Overview

Form Request validation pattern with centralized rules in `Modules/{Name}/Http/Requests/`.

---

## FormRequest Structure

```php
namespace Modules\RealEstate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePropertyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'property_type' => ['required', Rule::in(PropertyType::values())],
            'status' => ['nullable', Rule::in(PropertyStatus::values())],
            'country_id' => ['required', 'integer', Rule::exists('countries', 'id')],
            'city_id' => ['required', 'integer', Rule::exists('cities', 'id')],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'bedrooms' => ['nullable', 'integer', 'min:0'],
            'bathrooms' => ['nullable', 'integer', 'min:0'],
            'area' => ['nullable', 'numeric', 'min:0'],
            'main_image' => ['nullable', 'file', 'mimes:jpeg,png,webp', 'max:5120'],
            'gallery' => ['nullable', 'array'],
            'gallery.*' => ['nullable', 'file', 'mimes:jpeg,png,webp', 'max:5120'],
            'existing_gallery' => ['nullable', 'array'],
            'existing_gallery.*.id' => ['nullable', 'integer'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (!$this->has('currency')) {
            $this->merge(['currency' => 'USD']);
        }
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Property name is required.',
            'price.required' => 'Price is required.',
            'country_id.exists' => 'Selected country is invalid.',
        ];
    }
}
```

---

## Common Validation Rules

| Rule | Usage |
|------|-------|
| `required` | Field must be present and non-empty |
| `nullable` | Field can be null |
| `string` | Must be string |
| `integer` | Must be integer |
| `numeric` | Must be numeric |
| `boolean` | Must be true/false, 1/0 |
| `array` | Must be array |
| `email` | Valid email format |
| `max:value` | Max length/value |
| `min:value` | Min length/value |
| `size:value` | Exact size (string length, number value) |
| `in:val1,val2` | Must be one of allowed values |
| `exists:table,column` | Must exist in database |
| `unique:table,column` | Must be unique in database |
| `file` | Must be valid file |
| `mimes:jpeg,png` | Allowed file extensions |
| `date` | Valid date |
| `date_format:Y-m-d` | Specific date format |
| `uuid` | Valid UUID format |

---

## Rule Types

### 1. Basic Validation
```php
'name' => ['required', 'string', 'max:255'],
'email' => ['required', 'email'],
'age' => ['required', 'integer', 'min:18'],
```

### 2. Database Existence
```php
'country_id' => ['required', 'integer', Rule::exists('countries', 'id')],
'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
```

### 3. Enum Validation
```php
'status' => ['required', Rule::in(PropertyStatus::values())],
'property_type' => ['required', Rule::in(PropertyType::values())],
```

### 4. File Upload
```php
'image' => ['nullable', 'file', 'mimes:jpeg,png,webp', 'max:5120'],
'document' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
'gallery' => ['nullable', 'array', 'max:10'],
'gallery.*' => ['nullable', 'file', 'mimes:jpeg,png,webp', 'max:5120'],
```

### 5. Conditional Validation
```php
// Required when another field has value
'phone' => ['nullable', 'string', 'required_if:contact_method,phone'],

// Required unless another field has value
'description' => ['nullable', 'string', 'required_unless:type,standard'],

// Required with conditions
'price' => ['required', 'numeric', 'required_with:discount'],
```

### 6. Cross-Field Validation
```php
// Same value validation
'password' => ['required', 'string', 'min:8', 'confirmed'],
// Requires password_confirmation field

// After validation custom rules
public function withValidator($validator): void
{
    $validator->after(function ($validator) {
        if ($this->somethingIsWrong()) {
            $validator->addError('field', 'Error message');
        }
    });
}
```

---

## Update Request Pattern

```php
class UpdatePropertyRequest extends FormRequest
{
    public function rules(): array
    {
        return array_merge((new StorePropertyRequest())->rules(), [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
        ]);
    }
}
```

---

## Validation Error Response

Controller returns automatic 422 with validation errors via `ValidatesRequests` trait.

Manual override:
```php
return $this->validationErrorResponse($validator->errors());
```

---

## Checking Validation State

```php
if ($this->validate()) {
    // validation passed
}

// In form request
public function failedValidation(Validator $validator): void
{
    throw new ValidationException($validator);
}
```

---

## Custom Validation Rules

```php
// Create in app/Rules/
php artisan make:rule Uppercase

// Rule implementation
class Uppercase implements Rule
{
    public function passes($attribute, $value): bool
    {
        return strtoupper($value) === $value;
    }

    public function message(): string
    {
        return 'The :attribute must be uppercase.';
    }
}

// Usage
'status' => ['required', new Uppercase()],
```

---

## Checklist for Creating FormRequest

1. Create request class in `Modules/{Name}/Http/Requests/`
2. Define `rules()` with appropriate validators
3. Set `authorize()` to `true` (or implement permissions)
4. Add `prepareForValidation()` for defaults if needed
5. Add custom `messages()` for user-friendly errors
6. Use `Rule::exists()` for foreign keys
7. Use enum `values()` for status/type fields
8. Handle file validation with `mimes` and `max` rules