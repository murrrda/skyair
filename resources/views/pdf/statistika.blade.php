@php
    /** @var array<string, mixed> $data */
    $period = $data['period'];
    $summary = $data['summary'];
    $utilization = $data['utilization'];
    $flightsPerPlane = $data['flights_per_plane'];
    $serviceIntervals = $data['service_intervals'];
    $changes = $data['changes'];
    $operationalHours = $data['operational_hours_per_day'];

    // sr-RS style formatting: comma decimals, dot thousands, no trailing ,0.
    $fmt = function ($n) {
        if ($n === null) {
            return '—';
        }
        $formatted = number_format((float) $n, 1, ',', '.');

        return str_ends_with($formatted, ',0') ? substr($formatted, 0, -2) : $formatted;
    };

    $date = fn ($value) => \Illuminate\Support\Carbon::parse($value)->format('d.m.Y.');

    $utilizationMax = max(1, (float) $utilization->max('utilization'));
    $flightsMax = max(1, (int) $flightsPerPlane->max('flights'));
    $monthsMax = max(1, ...array_map(fn ($m) => $m['plane'] + $m['route'], $changes['months']));
@endphp
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 28px 32px;
        }

        * {
            font-family: 'DejaVu Sans', sans-serif;
        }

        body {
            margin: 0;
            color: #111827;
            font-size: 11px;
            line-height: 1.4;
        }

        .header {
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }

        .header h1 {
            margin: 0;
            font-size: 18px;
            color: #111827;
        }

        .header .meta {
            margin-top: 4px;
            font-size: 10px;
            color: #6b7280;
        }

        .summary-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px 0;
            margin-bottom: 22px;
        }

        .summary-table td {
            width: 25%;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 10px 12px;
            vertical-align: top;
        }

        .summary-label {
            font-size: 8px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: #6b7280;
        }

        .summary-value {
            font-size: 17px;
            font-weight: bold;
            margin-top: 4px;
        }

        .summary-hint {
            font-size: 8px;
            color: #9ca3af;
            margin-top: 3px;
        }

        .section {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 14px 16px;
            margin-bottom: 14px;
        }

        .section h2 {
            margin: 0 0 2px 0;
            font-size: 12px;
        }

        .section .desc {
            margin: 0 0 12px 0;
            font-size: 9px;
            color: #6b7280;
        }

        .bar-table {
            width: 100%;
            border-collapse: collapse;
        }

        .bar-table td {
            padding: 3px 0;
            vertical-align: middle;
        }

        .bar-label {
            width: 130px;
            padding-right: 10px;
        }

        .bar-label .reg {
            font-weight: bold;
            font-size: 10px;
        }

        .bar-label .model {
            font-size: 8px;
            color: #6b7280;
        }

        .bar-track {
            background: #eef0f4;
            border-radius: 4px;
            height: 14px;
        }

        .bar-fill {
            background: #4f46e5;
            height: 14px;
            border-radius: 4px;
        }

        .bar-value {
            width: 58px;
            text-align: right;
            font-weight: bold;
            font-size: 10px;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
        }

        table.data th {
            text-align: left;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #6b7280;
            border-bottom: 1px solid #d1d5db;
            padding: 4px 6px;
        }

        table.data td {
            padding: 5px 6px;
            border-bottom: 1px solid #f0f0f2;
            font-size: 10px;
        }

        .num {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        .muted {
            color: #9ca3af;
            font-size: 9px;
        }

        .legend {
            font-size: 9px;
            color: #374151;
            margin-bottom: 8px;
        }

        .swatch {
            display: inline-block;
            width: 9px;
            height: 9px;
            border-radius: 2px;
            vertical-align: middle;
        }

        .empty {
            padding: 12px 0;
            text-align: center;
            color: #9ca3af;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Statistika iskorišćenosti flote</h1>
        <div class="meta">
            Period: {{ $date($period['from']) }} – {{ $date($period['to']) }}
            &nbsp;·&nbsp; Generisano: {{ $generatedAt->format('d.m.Y. H:i') }}
        </div>
    </div>

    {{-- Summary cards --}}
    <table class="summary-table">
        <tr>
            <td>
                <div class="summary-label">Iskorišćenost flote</div>
                <div class="summary-value">{{ $fmt($summary['fleet_utilization']) }}%</div>
                <div class="summary-hint">Prosek za {{ $summary['planes_count'] }} aviona</div>
            </td>
            <td>
                <div class="summary-label">Ukupno letova</div>
                <div class="summary-value">{{ $fmt($summary['total_flights']) }}</div>
                <div class="summary-hint">U izabranom periodu</div>
            </td>
            <td>
                <div class="summary-label">Prosek između servisa</div>
                <div class="summary-value">
                    @if ($summary['avg_days_between_services'] !== null)
                        {{ $fmt($summary['avg_days_between_services']) }} dana
                    @else
                        —
                    @endif
                </div>
                <div class="summary-hint">Prosek na nivou flote</div>
            </td>
            <td>
                <div class="summary-label">Izmene plana leta</div>
                <div class="summary-value">{{ $fmt($summary['total_plan_changes']) }}</div>
                <div class="summary-hint">
                    {{ $fmt($changes['plane_total']) }} aviona · {{ $fmt($changes['route_total']) }} ruta
                </div>
            </td>
        </tr>
    </table>

    {{-- Fleet utilization --}}
    <div class="section">
        <h2>Iskorišćenost flote</h2>
        <p class="desc">Udeo letnih sati u odnosu na {{ $operationalHours }} operativnih sati dnevno</p>
        @if ($utilization->isEmpty())
            <div class="empty">Nema aviona u floti.</div>
        @else
            <table class="bar-table">
                @foreach ($utilization as $row)
                    <tr>
                        <td class="bar-label">
                            <div class="reg">{{ $row['reg_number'] }}</div>
                            <div class="model">{{ $row['model'] }}</div>
                        </td>
                        <td>
                            <div class="bar-track">
                                <div class="bar-fill" style="width: {{ max(1, round($row['utilization'] / $utilizationMax * 100)) }}%;"></div>
                            </div>
                        </td>
                        <td class="bar-value">{{ $fmt($row['utilization']) }}%</td>
                    </tr>
                @endforeach
            </table>
        @endif
    </div>

    {{-- Flights per plane --}}
    <div class="section">
        <h2>Broj letova po avionu</h2>
        <p class="desc">Ukupan broj letova po avionu u periodu</p>
        @if ($flightsPerPlane->isEmpty() || $flightsPerPlane->sum('flights') === 0)
            <div class="empty">Nema letova u izabranom periodu.</div>
        @else
            <table class="bar-table">
                @foreach ($flightsPerPlane as $row)
                    <tr>
                        <td class="bar-label">
                            <div class="reg">{{ $row['reg_number'] }}</div>
                            <div class="model">{{ $row['model'] }}</div>
                        </td>
                        <td>
                            <div class="bar-track">
                                <div class="bar-fill" style="width: {{ $row['flights'] > 0 ? max(1, round($row['flights'] / $flightsMax * 100)) : 0 }}%;"></div>
                            </div>
                        </td>
                        <td class="bar-value">{{ $fmt($row['flights']) }}</td>
                    </tr>
                @endforeach
            </table>
        @endif
    </div>

    {{-- Average time between services --}}
    <div class="section">
        <h2>Prosečno vreme između servisa</h2>
        <p class="desc">Prosečan broj dana između uzastopnih servisa po avionu</p>
        <table class="data">
            <thead>
                <tr>
                    <th>Avion</th>
                    <th class="center">Servisa</th>
                    <th class="num">Prosek (dana)</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($serviceIntervals as $row)
                    <tr>
                        <td>
                            <strong>{{ $row['reg_number'] }}</strong>
                            <span class="muted">— {{ $row['model'] }}</span>
                        </td>
                        <td class="center">{{ $row['services'] }}</td>
                        <td class="num">
                            @if ($row['avg_days'] !== null)
                                <strong>{{ $fmt($row['avg_days']) }}</strong>
                            @else
                                <span class="muted">Nedovoljno podataka</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="empty">Nema aviona u floti.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Flight plan change frequency --}}
    <div class="section">
        <h2>Učestalost izmena u planu leta</h2>
        <p class="desc">Broj izmena aviona i ruta po mesecima</p>
        <div class="legend">
            <span class="swatch" style="background: #4f46e5;"></span> Izmena aviona
            &nbsp;&nbsp;
            <span class="swatch" style="background: #f59e0b;"></span> Izmena rute
        </div>
        @php $hasChanges = collect($changes['months'])->contains(fn ($m) => $m['plane'] + $m['route'] > 0); @endphp
        @if (! $hasChanges)
            <div class="empty">Nema izmena plana leta u izabranom periodu.</div>
        @else
            <table class="data">
                <thead>
                    <tr>
                        <th>Mesec</th>
                        <th class="num">Izmena aviona</th>
                        <th class="num">Izmena rute</th>
                        <th class="num">Ukupno</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($changes['months'] as $m)
                        <tr>
                            <td>{{ $m['label'] }}</td>
                            <td class="num">{{ $m['plane'] }}</td>
                            <td class="num">{{ $m['route'] }}</td>
                            <td class="num"><strong>{{ $m['plane'] + $m['route'] }}</strong></td>
                        </tr>
                    @endforeach
                    <tr>
                        <td><strong>Ukupno</strong></td>
                        <td class="num"><strong>{{ $changes['plane_total'] }}</strong></td>
                        <td class="num"><strong>{{ $changes['route_total'] }}</strong></td>
                        <td class="num"><strong>{{ $changes['total'] }}</strong></td>
                    </tr>
                </tbody>
            </table>
        @endif
    </div>
</body>
</html>
