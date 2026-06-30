<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <title>Izveštaj — Prodaja karata</title>
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

        .pill-high {
            color: #047857;
            font-weight: bold;
        }

        .pill-low {
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

@php
    $kpis = $analytics['kpis'];
    $cancellation = $analytics['cancellation'];
    $extremes = $analytics['occupancy_extremes'];
    $highThreshold = (int) round($extremes['high_threshold']);
    $lowThreshold = (int) round($extremes['low_threshold']);
    $fmtDate = fn ($d) => \Illuminate\Support\Carbon::parse($d)->format('d.m.Y.');
@endphp

<header>
    <div class="brand">SkyAir — Prodaja karata</div>
    <div class="subtitle">Izveštaj analitike prodaje</div>
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
    <div class="title">Izveštaj — Prodaja karata</div>
    <div class="period-line">
        {{ $period['date_from_human'] }} – {{ $period['date_to_human'] }}
    </div>
    <div class="meta">
        Datum generisanja: {{ $generated_at }}<br>
        SkyAir d.o.o. — Sistem prodaje karata
    </div>
</div>

{{-- Sažetak (KPI kartice) --}}
<h2>Sažetak</h2>
<table class="kpi-grid">
    <tr>
        <td>
            <div class="kpi-label">Prodato karata</div>
            <div class="kpi-value">{{ number_format($kpis['tickets_sold'], 0, ',', '.') }}</div>
            <div class="kpi-hint">Sedišta na letovima (bez otkazanih)</div>
        </td>
        <td>
            <div class="kpi-label">Prihod</div>
            <div class="kpi-value">{{ number_format($kpis['revenue'], 0, ',', '.') }} RSD</div>
            <div class="kpi-hint">Plaćene i iskorišćene rezervacije</div>
        </td>
        <td>
            <div class="kpi-label">Stopa otkazivanja</div>
            <div class="kpi-value">{{ number_format($kpis['cancellation_rate_pct'], 1, ',', '.') }}%</div>
            <div class="kpi-hint">{{ $cancellation['cancelled_reservations'] }} od {{ $cancellation['total_reservations'] }} rezervacija</div>
        </td>
        <td>
            <div class="kpi-label">Prosečna popunjenost</div>
            <div class="kpi-value">{{ number_format($kpis['avg_occupancy_pct'], 1, ',', '.') }}%</div>
            <div class="kpi-hint">Prosek po letovima u periodu</div>
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
                    <td class="num">{{ number_format($row['sold'], 0, ',', '.') }}</td>
                    <td class="num">{{ number_format($row['total_seats'], 0, ',', '.') }}</td>
                    <td class="num">{{ number_format($row['occupancy_pct'], 1, ',', '.') }}%</td>
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
                    <td>{{ $row['route_name'] ?? '—' }}</td>
                    <td>{{ $fmtDate($row['date']) }}</td>
                    <td class="num pill-high">{{ number_format($row['occupancy_pct'], 1, ',', '.') }}%</td>
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
                    <td>{{ $row['route_name'] ?? '—' }}</td>
                    <td>{{ $fmtDate($row['date']) }}</td>
                    <td class="num pill-low">{{ number_format($row['occupancy_pct'], 1, ',', '.') }}%</td>
                    <td class="num">{{ $row['sold'] }} / {{ $row['capacity'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

{{-- Stopa otkazivanja --}}
<h2>Stopa otkazivanja</h2>
<table class="data">
    <thead>
        <tr>
            <th>Pokazatelj</th>
            <th class="num">Vrednost</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Ukupno rezervacija</td>
            <td class="num">{{ number_format($cancellation['total_reservations'], 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Otkazane rezervacije</td>
            <td class="num">{{ number_format($cancellation['cancelled_reservations'], 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td style="font-weight:bold;">Stopa otkazivanja</td>
            <td class="num" style="font-weight:bold;">{{ number_format($cancellation['rate_pct'], 1, ',', '.') }}%</td>
        </tr>
    </tbody>
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

</body>
</html>
