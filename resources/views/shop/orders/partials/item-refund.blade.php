@if ($item->refunded_quantity > 0)
    <tr class="bg-sky-50/50">
        <td colspan="2" class="px-5 pb-3 pt-0 text-xs text-sky-700"><span class="inline-flex items-center gap-1"><x-icon name="refund" class="h-3.5 w-3.5" /> {{ $item->refunded_quantity }} of {{ $item->quantity }} refunded</span></td>
        <td class="px-5 pb-3 pt-0 text-right text-xs font-medium text-sky-700">− {{ $item->refunded_amount }}</td>
    </tr>
@endif
