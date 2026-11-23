<?php

namespace App\Http\Requests\Shop;

use App\Domain\Orders\Services\DeliveryDetails;
use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route middleware already requires the "place orders" permission
    }

    public function rules(): array
    {
        return [
            'delivery_name' => ['required', 'string', 'max:255'],
            'delivery_phone' => ['required', 'string', 'max:40'],
            'delivery_address' => ['required', 'string', 'max:255'],
            'delivery_area' => ['required', 'string', 'max:100'],
            'delivery_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function deliveryDetails(): DeliveryDetails
    {
        return new DeliveryDetails(
            name: $this->string('delivery_name')->trim()->value(),
            phone: $this->string('delivery_phone')->trim()->value(),
            address: $this->string('delivery_address')->trim()->value(),
            area: $this->string('delivery_area')->trim()->value(),
            notes: $this->input('delivery_notes'),
        );
    }
}
