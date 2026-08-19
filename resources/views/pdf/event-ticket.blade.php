<!DOCTYPE html>
<html lang="{{ $ticket->order->locale }}">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 32px 36px; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 13px; }
        .ticket { border: 2px solid #100d0e; border-radius: 14px; overflow: hidden; margin-top: 24px; }
        .head { background: #100d0e; color: #ffffff; padding: 20px 28px; }
        .head .brand { font-size: 12px; letter-spacing: 3px; text-transform: uppercase; color: #e01b4b; font-weight: bold; }
        .head h1 { font-size: 26px; margin-top: 6px; text-transform: uppercase; }
        .body { padding: 24px 28px; }
        .row { width: 100%; }
        .meta { display: inline-block; vertical-align: top; width: 58%; }
        .qr { display: inline-block; vertical-align: top; width: 38%; text-align: right; }
        .label { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #9ca3af; margin-top: 12px; }
        .value { font-size: 14px; font-weight: bold; margin-top: 2px; }
        /* line-height 1: dompdf centreert de tekst anders te hoog in de pill. */
        .tickettype { display: inline-block; background: #e01b4b; color: #ffffff; font-weight: bold; padding: 7px 14px; border-radius: 999px; font-size: 13px; line-height: 1; margin-top: 4px; }
        .token { font-size: 10px; color: #9ca3af; margin-top: 10px; letter-spacing: 1px; }
        .foot { border-top: 1px dashed #d1d5db; padding: 14px 28px; font-size: 10px; color: #6b7280; }
        img.logo { height: 34px; }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="head">
            @if ($logoData)
                <img class="logo" src="{{ $logoData }}" alt="">
            @endif
            <div class="brand">{{ config('app.name') }}</div>
            <h1>{{ $event->translated('name', $ticket->order->locale) }}</h1>
        </div>

        <div class="body">
            <div class="row">
                <div class="meta">
                    <span class="tickettype">{{ $ticket->ticketType->nameFor($ticket->order->locale) }}</span>

                    <div class="label">{{ __('Datum') }}</div>
                    <div class="value">
                        {{ $event->dateLabel() }}@if ($event->startTimeFormatted()) · {{ $event->startTimeFormatted() }}@if ($event->endTimeFormatted()) – {{ $event->endTimeFormatted() }}@endif @endif
                    </div>

                    @if ($event->venue_name || $event->venue_city)
                        <div class="label">{{ __('Locatie') }}</div>
                        <div class="value">{{ collect([$event->venue_name, $event->venue_address, trim($event->venue_postal_code.' '.$event->venue_city)])->filter()->join(', ') }}</div>
                    @endif

                    <div class="label">{{ __('Naam') }}</div>
                    <div class="value">{{ $ticket->order->buyer_name }}</div>

                    <div class="token">{{ $ticket->token }}</div>
                </div>
                <div class="qr">
                    <img src="{{ $qrData }}" width="170" height="170" alt="QR">
                </div>
            </div>
        </div>

        <div class="foot">
            {{ __('Toon dit ticket (op papier of op je telefoon) aan de ingang. De QR-code wordt gescand en is één keer geldig.') }}
        </div>
    </div>
</body>
</html>
