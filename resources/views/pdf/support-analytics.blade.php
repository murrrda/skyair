<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <title>Izveštaj — Korisnička podrška</title>
    <style>
        @page {
            margin: 100px 36px 60px 36px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1f2937;
            line-height: 1.4;
        }

        header {
            position: fixed;
            top: -80px;
            left: 0;
            right: 0;
            height: 60px;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 6px;
        }

        header .brand {
            font-size: 14px;
            font-weight: bold;
            color: #185FA5;
        }

        header .subtitle {
            font-size: 10px;
            color: #6b7280;
        }

        header .period {
            position: absolute;
            top: 0;
            right: 0;
            font-size: 10px;
            color: #6b7280;
            text-align: right;
        }

        footer {
            position: fixed;
            bottom: -40px;
            left: 0;
            right: 0;
            font-size: 9px;
            color: #6b7280;
            text-align: center;
            border-top: 1px solid #e5e7eb;
            padding-top: 6px;
        }

        footer .pages:after {
            content: counter(page) " / " counter(pages);
        }

        h1 {
            font-size: 22px;
            margin: 0 0 4px 0;
            color: #111827;
        }

        h2 {
            font-size: 14px;
            margin: 18px 0 6px 0;
            color: #185FA5;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 3px;
        }

        .cover {
            text-align: center;
            padding-top: 160px;
            page-break-after: always;
        }

        .cover .title {
            font-size: 28px;
            font-weight: bold;
            color: #185FA5;
            margin-bottom: 16px;
        }

        .cover .subtitle {
            font-size: 14px;
            color: #4b5563;
            margin-bottom: 40px;
        }

        .cover .period-line {
            font-size: 16px;
            color: #111827;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .cover .meta {
            font-size: 11px;
            color: #6b7280;
            margin-top: 30px;
        }

        .kpi-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px 0;
            margin-bottom: 14px;
        }

        .kpi-grid td {
            width: 25%;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 10px;
            background: #f9fafb;
            vertical-align: top;
        }

        .kpi-label {
            font-size: 9px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .kpi-value {
            font-size: 18px;
            font-weight: bold;
            color: #111827;
        }

        .kpi-hint {
            font-size: 9px;
            color: #6b7280;
            margin-top: 3px;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            margin-bottom: 6px;
        }

        table.data thead {
            display: table-header-group;
        }

        table.data th {
            background: #185FA5;
            color: #ffffff;
            text-align: left;
            padding: 6px 8px;
            font-size: 10px;
            font-weight: bold;
        }

        table.data td {
            padding: 5px 8px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 10px;
        }

        table.data tr:nth-child(even) td {
            background: #f9fafb;
        }

        .num {
            text-align: right;
        }

        .pill-success {
            color: #047857;
            font-weight: bold;
        }

        .pill-partial {
            color: #b45309;
            font-weight: bold;
        }

        .pill-fail {
            color: #b91c1c;
            font-weight: bold;
        }

        .empty {
            padding: 12px;
            text-align: center;
            color: #6b7280;
            font-style: italic;
        }
    </style>
</head>
<body>

<header>
    <div class="brand">SkyAir — Korisnička podrška</div>
    <div class="subtitle">Izveštaj analitike tiketa</div>
    <div class="period">
        Period: {{ $period['date_from_human'] }} – {{ $period['date_to_human'] }}<br>
        Generisano: {{ $generated_at }}
    </div>
</header>

<footer>
    <span class="pages">Stranica </span>
</footer>

{{-- Naslovna stranica --}}
<div class="cover">
    <div class="title">Izveštaj — Korisnička podrška</div>
    <div class="period-line">
        {{ $period['date_from_human'] }} – {{ $period['date_to_human'] }}
    </div>
    <div class="meta">
        Datum generisanja: {{ $generated_at }}<br>
        SkyAir d.o.o. — Sistem korisničke podrške
    </div>
</div>

{{-- Sažetak (KPI kartice) --}}
<h2>Sažetak</h2>
@php
    $outcome = $analytics['outcome_summary'];
    $closedTotal = $outcome['success_count'] + $outcome['partial_count'] + $outcome['fail_count'];
    $avgMin = $analytics['avg_resolution_minutes'];

    if ($avgMin === null || $avgMin <= 0) {
        $avgFmt = '—';
    } else {
        $h = (int) floor($avgMin / 60);
        $m = (int) round($avgMin % 60);
        $avgFmt = $h === 0 ? $m.'m' : ($m === 0 ? $h.'h' : $h.'h '.$m.'m');
    }
@endphp

<table class="kpi-grid">
    <tr>
        <td>
            <div class="kpi-label">Ukupno tiketa</div>
            <div class="kpi-value">{{ number_format($analytics['total_tickets'], 0, ',', '.') }}</div>
            <div class="kpi-hint">Svi tiketi u periodu</div>
        </td>
        <td>
            <div class="kpi-label">Otvoreni tiketi</div>
            <div class="kpi-value">{{ number_format($analytics['open_tickets'], 0, ',', '.') }}</div>
            <div class="kpi-hint">Aktivni (open / in_progress)</div>
        </td>
        <td>
            <div class="kpi-label">Prosečno vreme rešavanja</div>
            <div class="kpi-value">{{ $avgFmt }}</div>
            <div class="kpi-hint">Zatvoreni tiketi</div>
        </td>
        <td>
            <div class="kpi-label">Stopa uspešnosti</div>
            <div class="kpi-value">{{ number_format($outcome['success_pct'], 1, ',', '.') }}%</div>
            <div class="kpi-hint">{{ $outcome['success_count'] }} od {{ $closedTotal }} zatvorenih</div>
        </td>
    </tr>
</table>

{{-- Broj prijava po tipu problema --}}
<h2>Broj prijava po tipu problema</h2>
@if (count($analytics['tickets_by_category']) === 0)
    <div class="empty">Nema podataka za izabrani period.</div>
@else
    <table class="data">
        <thead>
            <tr>
                <th>Kategorija</th>
                <th class="num">Broj tiketa</th>
                <th class="num">Procenat</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($analytics['tickets_by_category'] as $row)
                <tr>
                    <td>{{ $row['category_name'] }}</td>
                    <td class="num">{{ $row['count'] }}</td>
                    <td class="num">{{ number_format($row['percentage'], 1, ',', '.') }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

{{-- Vreme rešavanja po tipu problema --}}
<h2>Vreme rešavanja po tipu problema</h2>
@php
    $formatHours = function ($minutes) {
        if ($minutes === null || $minutes <= 0) return '—';
        $h = (int) floor($minutes / 60);
        $m = (int) round($minutes % 60);
        if ($h === 0) return $m.'m';
        if ($m === 0) return $h.'h';
        return $h.'h '.$m.'m';
    };
@endphp
@if (count($analytics['resolution_time_by_category']) === 0)
    <div class="empty">Nema rešenih tiketa za izabrani period.</div>
@else
    <table class="data">
        <thead>
            <tr>
                <th>Kategorija</th>
                <th class="num">Min</th>
                <th class="num">Prosek</th>
                <th class="num">Max</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($analytics['resolution_time_by_category'] as $row)
                <tr>
                    <td>{{ $row['category_name'] }}</td>
                    <td class="num">{{ $formatHours($row['min_minutes']) }}</td>
                    <td class="num">{{ $formatHours($row['avg_minutes']) }}</td>
                    <td class="num">{{ $formatHours($row['max_minutes']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

{{-- Ishod tiketa --}}
<h2>Ishod tiketa</h2>
@if ($closedTotal === 0)
    <div class="empty">Nema zatvorenih tiketa za izabrani period.</div>
@else
    <table class="data">
        <thead>
            <tr>
                <th>Ishod</th>
                <th class="num">Broj</th>
                <th class="num">Procenat</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><span class="pill-success">Uspešno</span></td>
                <td class="num">{{ $outcome['success_count'] }}</td>
                <td class="num">{{ number_format($outcome['success_pct'], 1, ',', '.') }}%</td>
            </tr>
            <tr>
                <td><span class="pill-partial">Delimično</span></td>
                <td class="num">{{ $outcome['partial_count'] }}</td>
                <td class="num">{{ number_format($outcome['partial_pct'], 1, ',', '.') }}%</td>
            </tr>
            <tr>
                <td><span class="pill-fail">Neuspešno</span></td>
                <td class="num">{{ $outcome['fail_count'] }}</td>
                <td class="num">{{ number_format($outcome['fail_pct'], 1, ',', '.') }}%</td>
            </tr>
            <tr>
                <td style="font-weight:bold;">Ukupno zatvorenih</td>
                <td class="num" style="font-weight:bold;">{{ $closedTotal }}</td>
                <td class="num" style="font-weight:bold;">100%</td>
            </tr>
        </tbody>
    </table>
@endif

{{-- Top 3 leta po broju prijava --}}
<h2>Top 3 leta po broju prijava</h2>
@if (count($analytics['top_flights_by_issues']) === 0)
    <div class="empty">Nema letova sa prijavama u izabranom periodu.</div>
@else
    <table class="data">
        <thead>
            <tr>
                <th>Let</th>
                <th>Ruta</th>
                <th class="num">Ukupno</th>
                <th class="num">Uspešno</th>
                <th class="num">Delimično</th>
                <th class="num">Neuspešno</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($analytics['top_flights_by_issues'] as $row)
                <tr>
                    <td>{{ $row['flight_number'] }}</td>
                    <td>{{ $row['route_name'] ?? '—' }}</td>
                    <td class="num"><strong>{{ $row['total'] }}</strong></td>
                    <td class="num pill-success">{{ $row['success'] }}</td>
                    <td class="num pill-partial">{{ $row['partial'] }}</td>
                    <td class="num pill-fail">{{ $row['fail'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

{{-- Trend prijava tiketa --}}
<h2>Trend prijava tiketa ({{ $trend['granularity_label'] }})</h2>
@if (count($trend['rows']) === 0)
    <div class="empty">Nema podataka za izabrani period.</div>
@else
    <table class="data">
        <thead>
            <tr>
                <th>Period</th>
                <th class="num">Broj tiketa</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($trend['rows'] as $row)
                <tr>
                    <td>{{ $row['label'] }}</td>
                    <td class="num">{{ $row['count'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

</body>
</html>
