<!DOCTYPE html>
<html lang="nl">
<head><meta charset="utf-8"></head>
<body style="font-family: Arial, Helvetica, sans-serif; color: #1f2937; line-height: 1.5;">
    <h2 style="margin: 0 0 0.5rem;">Nieuwe ticketbestelling</h2>
    <table cellpadding="6" cellspacing="0" style="border-collapse: collapse; font-size: 14px;">
        <tr style="border-bottom: 1px solid #e5e7eb;">
            <th align="left" style="padding-right: 1rem;">Event</th>
            <td>{{ $order->event->name }} ({{ $order->event->dateLabel() }})</td>
        </tr>
        <tr style="border-bottom: 1px solid #e5e7eb;">
            <th align="left" style="padding-right: 1rem;">Koper</th>
            <td>{{ $order->buyer_name }} — {{ $order->buyer_email }}</td>
        </tr>
        @foreach ($order->items as $item)
            <tr style="border-bottom: 1px solid #e5e7eb;">
                <th align="left" style="padding-right: 1rem;">Tickets</th>
                <td>{{ $item->quantity }} × {{ $item->description }}</td>
            </tr>
        @endforeach
        @foreach ($order->extras as $extra)
            <tr style="border-bottom: 1px solid #e5e7eb;">
                <th align="left" style="padding-right: 1rem;">Extra</th>
                <td>{{ $extra->quantity }} × {{ $extra->description }}</td>
            </tr>
        @endforeach
        @if ((float) $order->discount_amount > 0)
            <tr style="border-bottom: 1px solid #e5e7eb;">
                <th align="left" style="padding-right: 1rem;">Korting</th>
                <td>− € {{ number_format((float) $order->discount_amount, 2, ',', '.') }}</td>
            </tr>
        @endif
        <tr>
            <th align="left" style="padding-right: 1rem;">Totaal</th>
            <td style="font-weight: bold;">€ {{ number_format((float) $order->total_inc_vat, 2, ',', '.') }} incl. btw</td>
        </tr>
    </table>
    <p style="margin: 1rem 0 0; font-size: 13px; color: #6b7280;">
        Alle bestellingen: admin → Tickets → Bestellingen.
    </p>
</body>
</html>
