<x-filament-panels::page>
    {{-- Layout-kritische styling staat inline: de app-Tailwind wordt niet in de
         Filament-bundle geladen (project-conventie). --}}
    @php
        $money = fn (?float $amount): string => \App\Support\EventResult::money($amount);
        $cell = 'padding: .625rem .75rem; border-bottom: 1px solid rgba(120,120,120,.2);';
        $num = $cell.' text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums;';
        $resultColor = fn (float $value): string => $value < 0 ? 'color: #dc2626;' : 'color: #16a34a;';
        $totals = $this->totals;
    @endphp

    <div style="display: grid; gap: 1.5rem;">
        <div style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: end; justify-content: space-between;">
            <div style="min-width: 140px;">
                <label for="results-year" style="display: block; font-size: .875rem; font-weight: 500; margin-bottom: .375rem;">Jaar</label>
                <select id="results-year" wire:model.live="year"
                        style="width: 100%; border-radius: .5rem; border: 1px solid rgba(120,120,120,.4); padding: .5rem .75rem; background: transparent; cursor: pointer;">
                    @foreach ($this->yearOptions as $option)
                        <option value="{{ $option }}">{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 2rem; font-weight: 700; line-height: 1; {{ $resultColor($totals->result()) }}">{{ $money($totals->result()) }}</div>
                <div style="font-size: .8rem; opacity: .7;">resultaat {{ $year }} (excl. btw)</div>
            </div>
        </div>

        @if ($this->rows->isEmpty())
            <p style="opacity: .7;">Geen events in {{ $year }}.</p>
        @else
            <div style="overflow-x: auto; border: 1px solid rgba(120,120,120,.25); border-radius: .75rem;">
                <table style="width: 100%; border-collapse: collapse; font-size: .875rem;">
                    <thead>
                        <tr style="text-align: left; font-weight: 600;">
                            <th style="{{ $cell }}">Event</th>
                            <th style="{{ $cell }}">Datum</th>
                            <th style="{{ $num }}">Bezoekers</th>
                            <th style="{{ $num }}">Online tickets</th>
                            <th style="{{ $num }}">Andere opbrengsten</th>
                            <th style="{{ $num }}">Kosten</th>
                            <th style="{{ $num }}">Resultaat</th>
                            <th style="{{ $num }}">Per bezoeker</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->rows as $row)
                            @php($result = $row['result'])
                            <tr>
                                <td style="{{ $cell }}">
                                    <a href="{{ $row['url'] }}" style="font-weight: 600; text-decoration: underline; text-underline-offset: 2px;">{{ $row['event']->name }}</a>
                                    @if ($row['event']->isCancelled())
                                        <span style="font-size: .75rem; color: #dc2626; margin-left: .25rem;">afgelast</span>
                                    @endif
                                </td>
                                <td style="{{ $cell }} white-space: nowrap;">{{ $row['event']->start_date->format('d/m/Y') }}</td>
                                <td style="{{ $num }}">{{ $result->visitors }}</td>
                                <td style="{{ $num }}">{{ $money($result->ticketRevenue) }}</td>
                                <td style="{{ $num }}">{{ $money($result->otherRevenue) }}</td>
                                <td style="{{ $num }}">{{ $money($result->costs) }}</td>
                                <td style="{{ $num }} font-weight: 700; {{ $resultColor($result->result()) }}">{{ $money($result->result()) }}</td>
                                <td style="{{ $num }}">{{ $money($result->resultPerVisitor()) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="font-weight: 700;">
                            <td style="{{ $cell }}" colspan="2">Totaal {{ $year }}</td>
                            <td style="{{ $num }}">{{ $totals->visitors }}</td>
                            <td style="{{ $num }}">{{ $money($totals->ticketRevenue) }}</td>
                            <td style="{{ $num }}">{{ $money($totals->otherRevenue) }}</td>
                            <td style="{{ $num }}">{{ $money($totals->costs) }}</td>
                            <td style="{{ $num }} {{ $resultColor($totals->result()) }}">{{ $money($totals->result()) }}</td>
                            <td style="{{ $num }}">{{ $money($totals->resultPerVisitor()) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <p style="font-size: .8rem; opacity: .7;">Alle bedragen excl. btw. Kosten en andere opbrengsten (kassa, bar, …) geef je in op de tab <strong>Resultaat</strong> van elk event.</p>
        @endif
    </div>
</x-filament-panels::page>
