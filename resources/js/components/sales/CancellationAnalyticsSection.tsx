import { AlertTriangle, TrendingDown, TrendingUp } from 'lucide-react';
import CancellationRateDonut from '@/components/sales/CancellationRateDonut';
import type { CancellationSummary } from '@/components/sales/CancellationRateDonut';
import CancellationTrendAreaChart from '@/components/sales/CancellationTrendAreaChart';
import type { DailyCount } from '@/components/sales/CancellationTrendAreaChart';

export type CancellationByFlightRow = {
    flight_id: number;
    flight_number: string;
    route_name: string | null;
    cancelled: number;
    total: number;
    rate_pct: number;
};

export type RisingRoute = {
    route_id: number;
    route_name: string | null;
    points: { date: string; count: number }[];
    total_cancelled: number;
    slope: number;
};

type Props = {
    cancellation: CancellationSummary;
    trend: DailyCount[];
    byFlight: CancellationByFlightRow[];
    rising: RisingRoute[];
};

function rateCls(pct: number): string {
    if (pct >= 30) {
        return 'text-red-600';
    }

    if (pct >= 15) {
        return 'text-amber-600';
    }

    return 'text-emerald-600';
}

export default function CancellationAnalyticsSection({
    cancellation,
    trend,
    byFlight,
    rising,
}: Props) {
    return (
        <section className="rounded-xl border border-border bg-card p-6 shadow-sm">
            <div className="mb-5 flex items-start gap-3">
                <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary">
                    <TrendingDown className="h-5 w-5" />
                </span>
                <div>
                    <h2 className="text-base font-semibold tracking-tight">
                        Analitika otkazivanja
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        Stopa, trend i letovi sa rastućim otkazivanjima
                    </p>
                </div>
            </div>

            {/* Rate donut + time-series trend */}
            <div className="grid gap-6 md:grid-cols-2">
                <div>
                    <h3 className="mb-2 text-sm font-medium text-muted-foreground">
                        Stopa otkazivanja
                    </h3>
                    <CancellationRateDonut data={cancellation} />
                </div>
                <div>
                    <h3 className="mb-2 text-sm font-medium text-muted-foreground">
                        Trend otkazivanja
                    </h3>
                    <CancellationTrendAreaChart data={trend} />
                </div>
            </div>

            {/* Rising-cancellation flags */}
            <div className="mt-8">
                <h3 className="mb-3 flex items-center gap-2 text-sm font-semibold tracking-tight">
                    <AlertTriangle className="h-4 w-4 text-amber-500" />
                    Trend rasta otkazivanja
                </h3>

                {rising.length === 0 ? (
                    <p className="rounded-lg border border-dashed border-border px-4 py-6 text-center text-sm text-muted-foreground">
                        Nema ruta sa rastućim trendom otkazivanja.
                    </p>
                ) : (
                    <div className="overflow-x-auto rounded-lg border border-amber-300 bg-amber-50/60 dark:border-amber-500/40 dark:bg-amber-500/10">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-amber-200 text-left text-xs tracking-wide text-amber-800 uppercase dark:border-amber-500/30 dark:text-amber-300">
                                    <th className="px-4 py-2 font-medium">
                                        Ruta
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Nedavna otkazivanja
                                    </th>
                                    <th className="px-4 py-2 text-right font-medium">
                                        Ukupno
                                    </th>
                                    <th className="px-4 py-2 text-right font-medium">
                                        Trend
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {rising.map((r) => (
                                    <tr
                                        key={r.route_id}
                                        className="border-b border-amber-200/60 last:border-0 dark:border-amber-500/20"
                                    >
                                        <td className="px-4 py-2 font-medium">
                                            {r.route_name ?? '—'}
                                        </td>
                                        <td className="px-4 py-2 text-muted-foreground tabular-nums">
                                            {r.points
                                                .map((p) => p.count)
                                                .join(' → ')}
                                        </td>
                                        <td className="px-4 py-2 text-right font-semibold tabular-nums">
                                            {r.total_cancelled}
                                        </td>
                                        <td className="px-4 py-2 text-right">
                                            <span className="inline-flex items-center gap-1 font-semibold text-red-600">
                                                <TrendingUp className="h-3.5 w-3.5" />
                                                {r.slope.toFixed(2)}
                                            </span>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>

            {/* Per-flight backing table */}
            <div className="mt-8">
                <h3 className="mb-3 text-sm font-semibold tracking-tight">
                    Otkazivanja po letovima
                </h3>

                {byFlight.length === 0 ? (
                    <p className="rounded-lg border border-dashed border-border px-4 py-6 text-center text-sm text-muted-foreground">
                        Nema rezervacija na letovima u izabranom periodu.
                    </p>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-border text-left text-xs tracking-wide text-muted-foreground uppercase">
                                    <th className="py-2 pr-4 font-medium">
                                        Let
                                    </th>
                                    <th className="py-2 pr-4 font-medium">
                                        Ruta
                                    </th>
                                    <th className="py-2 pr-4 text-right font-medium">
                                        Otkazano
                                    </th>
                                    <th className="py-2 pr-4 text-right font-medium">
                                        Ukupno
                                    </th>
                                    <th className="py-2 text-right font-medium">
                                        Stopa
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {byFlight.map((row) => (
                                    <tr
                                        key={row.flight_id}
                                        className="border-b border-border/60 last:border-0"
                                    >
                                        <td className="py-2 pr-4 font-medium">
                                            {row.flight_number}
                                        </td>
                                        <td className="py-2 pr-4 text-muted-foreground">
                                            {row.route_name ?? '—'}
                                        </td>
                                        <td className="py-2 pr-4 text-right tabular-nums">
                                            {row.cancelled}
                                        </td>
                                        <td className="py-2 pr-4 text-right text-muted-foreground tabular-nums">
                                            {row.total}
                                        </td>
                                        <td
                                            className={`py-2 text-right font-semibold tabular-nums ${rateCls(row.rate_pct)}`}
                                        >
                                            {row.rate_pct.toFixed(1)}%
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </section>
    );
}
