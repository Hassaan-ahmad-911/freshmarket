@if ($item->refunded_quantity > 0)
    <tr>
        <td colspan="2" class="px-4 pb-2 pt-0 text-xs text-sky-700">↩ {{ $item->refunded_quantity }} of {{ $item->quantity }} refunded</td>
        <td class="px-4 pb-2 pt-0 text-right text-xs text-sky-700">− {{ $item->refunded_amount }}</td>
    </tr>
@endif
