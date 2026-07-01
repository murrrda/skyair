<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 96px 42px 54px 42px; }

        body { font-family: "DejaVu Serif", serif; font-size: 11px; color: #000; line-height: 1.35; }

        header {
            position: fixed; top: -70px; left: 0; right: 0; height: 58px;
            border-bottom: 1px solid #000; padding-bottom: 4px;
        }
        header .company { font-size: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        header .doc-title { font-size: 11px; margin-top: 3px; }
        header .meta { position: absolute; top: 0; right: 0; font-size: 9px; text-align: right; }

        footer {
            position: fixed; bottom: -34px; left: 0; right: 0; height: 22px;
            border-top: 1px solid #000; padding-top: 4px;
            font-size: 8px; text-align: center;
        }

        h2 {
            font-size: 11px; font-weight: bold; text-transform: uppercase;
            margin: 16px 0 6px; border-bottom: 1px solid #000; padding-bottom: 2px;
        }

        table { width: 100%; border-collapse: collapse; }

        table.kpis td {
            border: 1px solid #000; padding: 5px 7px; width: 20%; vertical-align: top;
        }
        table.kpis .label { font-size: 8px; text-transform: uppercase; }
        table.kpis .value { font-size: 12px; font-weight: bold; margin-top: 2px; }
        table.kpis .sub { font-size: 8px; margin-top: 1px; }

        table.data th, table.data td {
            border: 1px solid #000; padding: 4px 6px; text-align: left; font-size: 10px;
        }
        table.data th { font-weight: bold; text-transform: uppercase; font-size: 9px; }
        table.data td.num { text-align: right; }
    </style>
</head>
<body>
    @php
        $kpis = $report['kpis'];
        $statusLabels = ['over' => 'Prekoračenje', 'near' => 'Blizu limita', 'normal' => 'Normalno'];
    @endphp

    <header>
        <div class="company">SkyAir</div>
        <div class="doc-title">{{ $title }}</div>
        <div class="meta">
            Period: {{ $period['from'] }} – {{ $period['to'] }}<br>
            Generisano: {{ $generated_at }}
        </div>
    </header>

    <footer>SkyAir — Izveštaj o performansama posade</footer>

    <h2>Zbirni pokazatelji</h2>
    <table class="kpis">
        <tr>
            <td><div class="label">Ukupno letova</div><div class="value">{{ number_format($kpis['total_flights'], 0, ',', '.') }}</div><div class="sub">u periodu</div></td>
            <td><div class="label">Ukupno sati leta</div><div class="value">{{ number_format($kpis['total_hours'], 0, ',', '.') }}</div><div class="sub">sati</div></td>
            <td><div class="label">Prosek sati / zaposleni</div><div class="value">{{ number_format($kpis['avg_hours_per_employee'], 1, ',', '.') }}</div><div class="sub">od {{ $kpis['active_employees'] }} zaposlenih</div></td>
            <td><div class="label">Prekoračenje limita</div><div class="value">{{ $kpis['over_limit'] }}</div><div class="sub">iznad {{ $report['cap'] }}h/ned</div></td>
            <td><div class="label">Prosek uzast. dana</div><div class="value">{{ number_format($kpis['avg_consecutive_days'], 1, ',', '.') }}</div><div class="sub">radnih dana</div></td>
        </tr>
    </table>

    <h2>Detalji po zaposlenom</h2>
    <table class="data">
        <thead>
            <tr>
                <th>Zaposleni</th>
                <th>Pozicija</th>
                <th>Letova</th>
                <th>Sati leta</th>
                <th>Prosek h/letu</th>
                <th>Uzast. dana</th>
                <th>Opterećenje</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report['employees'] as $e)
                @php
                    $h = (int) floor($e['avg_hours_per_flight']);
                    $m = (int) round(($e['avg_hours_per_flight'] - $h) * 60);
                @endphp
                <tr>
                    <td>{{ $e['name'] }}</td>
                    <td>{{ $e['position'] }}</td>
                    <td class="num">{{ $e['flights'] }}</td>
                    <td class="num">{{ $e['hours'] }}h</td>
                    <td class="num">{{ $h }}h {{ $m }}m</td>
                    <td class="num">{{ $e['max_consecutive_days'] }}</td>
                    <td class="num">{{ $e['peak_week_hours'] }} / {{ $report['cap'] }}h</td>
                    <td>{{ $statusLabels[$e['status']] ?? $e['status'] }}</td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center;padding:14px;">Nema podataka za izabrani period.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
