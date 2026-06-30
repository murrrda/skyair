<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <title>Izveštaj - Prodaja karata</title>
    <style>
        @page {
            margin: 70px 56px 54px 56px;
        }

        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 10.5px;
            color: #2a2a2c;
            line-height: 1.5;
        }

        /* Running header / footer kept to hairlines so the sheet reads like a
           letter, not a colored dashboard banner. */
        header {
            position: fixed;
            top: -50px;
            left: 0;
            right: 0;
            height: 32px;
            border-bottom: 1px solid #e6e6e6;
        }

        header .brand {
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 1px;
            color: #3a3f47;
        }

        header .meta {
            position: absolute;
            top: 1px;
            right: 0;
            font-size: 8.5px;
            color: #9a9ea4;
            text-align: right;
            line-height: 1.4;
        }

        footer {
            position: fixed;
            bottom: -36px;
            left: 0;
            right: 0;
            font-size: 8.5px;
            color: #9a9ea4;
            border-top: 1px solid #e6e6e6;
            padding-top: 5px;
            text-align: right;
        }

        /* Brand sits left (absolute); the page counter stays in normal flow so
           DomPDF resolves counter(pages) — it returns 0 inside absolute boxes. */
        footer .brand-f {
            position: absolute;
            top: 5px;
            left: 0;
        }

        footer .pages:after {
            content: counter(page) " / " counter(pages);
        }

        /* Title block replaces the standalone cover page. */
        .report-title {
            margin: 2px 0 26px;
        }

        .report-title .t {
            font-size: 21px;
            font-weight: bold;
            color: #1c1c1e;
            letter-spacing: 0.2px;
        }

        .report-title .sub {
            font-size: 11px;
            color: #74787f;
            margin-top: 3px;
        }

        .report-title .rule {
            margin-top: 12px;
            border-bottom: 2px solid #3a3f47;
            width: 46px;
            height: 0;
            font-size: 0;
            line-height: 0;
        }

        h2 {
            font-size: 11px;
            font-weight: bold;
            color: #1c1c1e;
            letter-spacing: 0.3px;
            margin: 26px 0 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #e6e6e6;
        }

        /* KPIs as bare figures: no cards, fills or borders. table-layout:fixed
           keeps DomPDF from mis-measuring cell heights (which otherwise spilled
           the single figure row across several blank pages). */
        table.figures {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin: 4px 0 2px;
        }

        table.figures td {
            padding: 2px 14px 2px 0;
            vertical-align: top;
        }

        .fig-value {
            font-size: 19px;
            font-weight: bold;
            color: #1c1c1e;
        }

        .fig-label {
            font-size: 9.5px;
            color: #74787f;
            margin-top: 2px;
        }

        /* Data tables: ruled only, no header fill, no zebra. */
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin: 4px 0 2px;
        }

        table.data thead {
            display: table-header-group;
        }

        table.data th {
            text-align: left;
            font-size: 9px;
            font-weight: bold;
            color: #74787f;
            padding: 4px 8px 5px 0;
            border-bottom: 1px solid #bfc3c9;
        }

        table.data td {
            padding: 5px 8px 5px 0;
            border-bottom: 1px solid #efefef;
            font-size: 10px;
            color: #2a2a2c;
        }

        table.data tbody tr:last-child td {
            border-bottom: 1px solid #bfc3c9;
        }

        .num {
            text-align: right;
            padding-right: 0 !important;
        }

        .empty {
            padding: 8px 0;
            color: #9a9ea4;
            font-style: italic;
            font-size: 10px;
        }
    </style>
</head>
<body>

@php
    $kpis = $analytics['kpis'];
    $cancellation = $analytics['cancellation'];
    $extremes = $analytics['occupancy_extremes'];
    $highThreshold = (int) round($extremes['high_threshold']);
    $lowThreshold = (int) round($extremes['low_threshold']);
    $fmtDate = fn ($d) => \Illuminate\Support\Carbon::parse($d)->format('d.m.Y.');
    $n0 = fn ($v) => number_format($v, 0, ',', '.');
    $n1 = fn ($v) => number_format($v, 1, ',', '.');
    $n2 = fn ($v) => number_format($v, 2, ',', '.');
    $rising = $analytics['rising_cancellations'] ?? [];
@endphp

<header>
    <span class="brand">SKYAIR</span>
    <div class="meta">
        {{ $period['date_from_human'] }} – {{ $period['date_to_human'] }}<br>
        Generisano: {{ $generated_at }}
    </div>
</header>

<footer>
    <span class="brand-f">SkyAir - Prodaja karata</span>
    <span class="pages">Strana </span>
</footer>

<div class="report-title">
    <div class="t">Prodaja karata</div>
    <div class="sub">{{ $period['date_from_human'] }} – {{ $period['date_to_human'] }}</div>
    <div class="rule"></div>
</div>

{{-- Sažetak --}}
<h2>Sažetak</h2>
<table class="figures">
    <tr>
        <td>
            <div class="fig-value">{{ $n0($kpis['tickets_sold']) }}</div>
            <div class="fig-label">Prodato karata</div>
        </td>
        <td>
            <div class="fig-value">{{ $n0($kpis['revenue']) }} RSD</div>
            <div class="fig-label">Prihod</div>
        </td>
        <td>
            <div class="fig-value">{{ $n1($kpis['cancellation_rate_pct']) }}%</div>
            <div class="fig-label">Stopa otkazivanja</div>
        </td>
        <td>
            <div class="fig-value">{{ $n1($kpis['avg_occupancy_pct']) }}%</div>
            <div class="fig-label">Prosečna popunjenost</div>
        </td>
    </tr>
</table>

{{-- Popunjenost po klasama --}}
<h2>Popunjenost po klasama</h2>
@if (count($analytics['occupancy_by_class']) === 0)
    <div class="empty">Nema podataka za izabrani period.</div>
@else
    <table class="data">
        <thead>
            <tr>
                <th>Klasa</th>
                <th class="num">Prodato</th>
                <th class="num">Ukupno sedišta</th>
                <th class="num">Popunjenost</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($analytics['occupancy_by_class'] as $row)
                <tr>
                    <td>{{ $row['class_name'] }}</td>
                    <td class="num">{{ $n0($row['sold']) }}</td>
                    <td class="num">{{ $n0($row['total_seats']) }}</td>
                    <td class="num">{{ $n1($row['occupancy_pct']) }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

{{-- Letovi visoke popunjenosti --}}
<h2>Letovi visoke popunjenosti (≥ {{ $highThreshold }}%)</h2>
@if (count($extremes['highest']) === 0)
    <div class="empty">Nema letova sa visokom popunjenošću za izabrani period.</div>
@else
    <table class="data">
        <thead>
            <tr>
                <th>Let</th>
                <th>Ruta</th>
                <th>Datum</th>
                <th class="num">Popunjenost</th>
                <th class="num">Prodato / kapacitet</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($extremes['highest'] as $row)
                <tr>
                    <td>{{ $row['flight_number'] }}</td>
                    <td>{{ $row['route_name'] ?? '-' }}</td>
                    <td>{{ $fmtDate($row['date']) }}</td>
                    <td class="num">{{ $n1($row['occupancy_pct']) }}%</td>
                    <td class="num">{{ $row['sold'] }} / {{ $row['capacity'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

{{-- Letovi niske popunjenosti --}}
<h2>Letovi niske popunjenosti (≤ {{ $lowThreshold }}%)</h2>
@if (count($extremes['lowest']) === 0)
    <div class="empty">Nema letova sa niskom popunjenošću za izabrani period.</div>
@else
    <table class="data">
        <thead>
            <tr>
                <th>Let</th>
                <th>Ruta</th>
                <th>Datum</th>
                <th class="num">Popunjenost</th>
                <th class="num">Prodato / kapacitet</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($extremes['lowest'] as $row)
                <tr>
                    <td>{{ $row['flight_number'] }}</td>
                    <td>{{ $row['route_name'] ?? '-' }}</td>
                    <td>{{ $fmtDate($row['date']) }}</td>
                    <td class="num">{{ $n1($row['occupancy_pct']) }}%</td>
                    <td class="num">{{ $row['sold'] }} / {{ $row['capacity'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

{{-- Otkazivanja --}}
<h2>Otkazivanja</h2>
<table class="figures">
    <tr>
        <td>
            <div class="fig-value">{{ $n0($cancellation['total_reservations']) }}</div>
            <div class="fig-label">Ukupno rezervacija</div>
        </td>
        <td>
            <div class="fig-value">{{ $n0($cancellation['cancelled_reservations']) }}</div>
            <div class="fig-label">Otkazane rezervacije</div>
        </td>
        <td>
            <div class="fig-value">{{ $n1($cancellation['rate_pct']) }}%</div>
            <div class="fig-label">Stopa otkazivanja</div>
        </td>
    </tr>
</table>

{{-- Trend otkazivanja --}}
<h2>Trend otkazivanja ({{ $trend['granularity_label'] }})</h2>
@if (count($trend['rows']) === 0)
    <div class="empty">Nema podataka za izabrani period.</div>
@else
    <table class="data">
        <thead>
            <tr>
                <th>Period</th>
                <th class="num">Broj otkazivanja</th>
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

{{-- Rastući trend otkazivanja po rutama --}}
<h2>Rastući trend otkazivanja po rutama</h2>
@if (count($rising) === 0)
    <div class="empty">Nema ruta sa rastućim trendom otkazivanja za izabrani period.</div>
@else
    <table class="data">
        <thead>
            <tr>
                <th>Ruta</th>
                <th>Nedavna otkazivanja</th>
                <th class="num">Ukupno</th>
                <th class="num">Trend</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rising as $row)
                <tr>
                    <td>{{ $row['route_name'] ?? '-' }}</td>
                    <td>{{ collect($row['points'])->pluck('count')->implode(' → ') }}</td>
                    <td class="num">{{ $n0($row['total_cancelled']) }}</td>
                    <td class="num">▲ {{ $n2($row['slope']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

</body>
</html>
