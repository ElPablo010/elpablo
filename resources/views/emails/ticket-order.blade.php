<!DOCTYPE html>
<html lang="{{ $order->locale }}">
<head><meta charset="utf-8"></head>
<body style="font-family: Arial, Helvetica, sans-serif; color: #1f2937; line-height: 1.5; margin: 0; padding: 24px; background: #f9fafb;">
    <div style="max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e5e7eb;">
        <div style="background: #100d0e; padding: 24px; text-align: center;">
            <p style="margin: 0; font-size: 20px; font-weight: bold; color: #ffffff; letter-spacing: 0.05em; text-transform: uppercase;">
                {{ config('app.name') }}
            </p>
        </div>

        <div style="padding: 28px 24px;">
            <h1 style="margin: 0 0 0.5rem; font-size: 22px;">{{ __('Je tickets zijn binnen!') }}</h1>
            <p style="margin: 0 0 1.25rem; color: #4b5563;">
                {{ __('Hey :name, bedankt voor je bestelling. Je tickets zitten als PDF met QR-code in de bijlage — toon ze aan de deur op papier of op je telefoon.', ['name' => explode(' ', $order->buyer_name)[0]]) }}
            </p>

            <table cellpadding="0" cellspacing="0" style="width: 100%; font-size: 14px; margin-bottom: 1.25rem;">
                <tr>
                    <td style="padding: 4px 0; color: #6b7280; width: 96px;">{{ __('Event') }}</td>
                    <td style="padding: 4px 0; font-weight: bold;">{{ $order->event->translated('name', $order->locale) }}</td>
                </tr>
                <tr>
                    <td style="padding: 4px 0; color: #6b7280;">{{ __('Datum') }}</td>
                    <td style="padding: 4px 0;">
                        {{ $order->event->dateLabel() }}@if ($order->event->startTimeFormatted()) · {{ $order->event->startTimeFormatted() }}@endif
                    </td>
                </tr>
                @if ($order->event->venue_name || $order->event->venue_city)
                    <tr>
                        <td style="padding: 4px 0; color: #6b7280;">{{ __('Locatie') }}</td>
                        <td style="padding: 4px 0;">{{ collect([$order->event->venue_name, $order->event->venue_address, $order->event->venue_city])->filter()->join(', ') }}</td>
                    </tr>
                @endif
            </table>

            <table cellpadding="6" cellspacing="0" style="width: 100%; border-collapse: collapse; font-size: 14px;">
                @foreach ($order->items as $item)
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td>
                            {{ $item->quantity }} × {{ $item->description }}
                            @if ($item->free_quantity > 0)
                                <span style="color: #e01b4b;">({{ __(':count gratis', ['count' => $item->free_quantity]) }})</span>
                            @endif
                        </td>
                        <td align="right">€ {{ number_format((float) $item->line_total_inc_vat, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
                @if ((float) $order->discount_amount > 0)
                    <tr style="border-bottom: 1px solid #e5e7eb; color: #059669;">
                        <td>{{ __('Korting') }}</td>
                        <td align="right">− € {{ number_format((float) $order->discount_amount, 2, ',', '.') }}</td>
                    </tr>
                @endif
                <tr>
                    <td style="font-weight: bold; padding-top: 10px;">{{ __('Totaal') }}</td>
                    <td align="right" style="font-weight: bold; padding-top: 10px;">€ {{ number_format((float) $order->total_inc_vat, 2, ',', '.') }}</td>
                </tr>
                <tr>
                    <td colspan="2" style="color: #9ca3af; font-size: 12px;">{{ __('Inclusief btw') }} (€ {{ number_format($order->vatAmount(), 2, ',', '.') }})</td>
                </tr>
            </table>

            <p style="margin: 1.5rem 0 0; color: #6b7280; font-size: 13px;">
                {{ __('Vragen over je bestelling? Antwoord gewoon op deze mail.') }}
            </p>
        </div>
    </div>
</body>
</html>
