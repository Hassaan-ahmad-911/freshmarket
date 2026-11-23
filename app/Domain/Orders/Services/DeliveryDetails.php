<?php

namespace App\Domain\Orders\Services;

final readonly class DeliveryDetails
{
    public function __construct(
        public string $name,
        public string $phone,
        public string $address,
        public string $area,
        public ?string $notes = null,
    ) {}

    public function toOrderAttributes(): array
    {
        return [
            'delivery_name' => $this->name,
            'delivery_phone' => $this->phone,
            'delivery_address' => $this->address,
            'delivery_area' => $this->area,
            'delivery_notes' => $this->notes,
        ];
    }
}
