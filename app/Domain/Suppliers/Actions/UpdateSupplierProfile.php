<?php

namespace App\Domain\Suppliers\Actions;

use App\Domain\Suppliers\Models\DeliveryArea;
use App\Domain\Suppliers\Models\Supplier;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UpdateSupplierProfile
{
    /**
     * @param  array{name: string, description: ?string, min_order_amount: int}  $data
     * @param  list<string>  $deliveryAreas
     */
    public function handle(
        Supplier $supplier,
        array $data,
        array $deliveryAreas,
        ?UploadedFile $logo = null,
        bool $removeLogo = false,
    ): Supplier {
        $oldLogo = $supplier->logo_path;

        if ($logo) {
            $data['logo_path'] = $logo->store('suppliers/logos', 'public');
        } elseif ($removeLogo) {
            $data['logo_path'] = null;
        }

        DB::transaction(function () use ($supplier, $data, $deliveryAreas) {
            $supplier->update($data);
            $this->syncDeliveryAreas($supplier, $deliveryAreas);
        });

        // Delete the old file only after the database change succeeded.
        if ($oldLogo && $supplier->logo_path !== $oldLogo) {
            Storage::disk('public')->delete($oldLogo);
        }

        return $supplier->refresh();
    }

    /** Keep areas that are still listed, add new ones, delete the rest. */
    private function syncDeliveryAreas(Supplier $supplier, array $areas): void
    {
        $wanted = collect($areas)
            ->map(fn (string $name) => trim($name))
            ->filter()
            ->keyBy(fn (string $name) => DeliveryArea::normalize($name));

        $supplier->deliveryAreas()->whereNotIn('normalized', $wanted->keys())->delete();

        $existing = $supplier->deliveryAreas()->pluck('normalized');

        foreach ($wanted->except($existing) as $normalized => $name) {
            $supplier->deliveryAreas()->create(['name' => $name, 'normalized' => $normalized]);
        }
    }
}
