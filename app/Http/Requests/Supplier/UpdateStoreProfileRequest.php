<?php

namespace App\Http\Requests\Supplier;

use App\Http\Requests\Concerns\ParsesMoney;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStoreProfileRequest extends FormRequest
{
    use ParsesMoney;

    public const MAX_AREAS = 50;

    /** Form Requests can authorize too: this runs the SupplierPolicy::update rule. */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->user()->supplier);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_logo' => ['boolean'],
            'min_order_amount' => ['nullable', $this->moneyRule()],
            'delivery_areas' => ['nullable', 'string', 'max:3000'],
        ];
    }

    public function messages(): array
    {
        return ['min_order_amount.regex' => 'Enter an amount like 15 or 15.50.'];
    }

    public function after(): array
    {
        return [function ($validator) {
            if (count($this->deliveryAreas()) > self::MAX_AREAS) {
                $validator->errors()->add('delivery_areas', 'You can list at most '.self::MAX_AREAS.' delivery areas.');
            }
        }];
    }

    /** @return array{name: string, description: ?string, min_order_amount: int} */
    public function profileData(): array
    {
        return [
            'name' => $this->string('name')->trim()->value(),
            'description' => $this->input('description'),
            'min_order_amount' => $this->cents('min_order_amount') ?? 0,
        ];
    }

    /** The textarea accepts one area per line or comma-separated: "Camden, Islington". */
    public function deliveryAreas(): array
    {
        return collect(preg_split('/[\r\n,]+/', (string) $this->input('delivery_areas')))
            ->map(fn (string $area) => mb_substr(trim($area), 0, 100))
            ->filter()
            ->unique(fn (string $area) => mb_strtolower($area))
            ->values()
            ->all();
    }
}
