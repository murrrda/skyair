import { BarChart3 } from 'lucide-react';
import { useState } from 'react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Legend,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

// ─── Types (mirror occupancy_by_class rows from SalesAnalyticsService) ──────────

export type OccupancyByClassRow = {
    class_id: number;
    class_name: string;
    sold: number;
    total_seats: number;
    occupancy_pct: number;
};

type SeasonKey = 'leto' | 'zima' | 'van_sezone';

type Props = {
    /** Whole-period breakdown (the "Sve sezone" view). */
    all: OccupancyByClassRow[];
    /** Same breakdown sliced by calendar season of flight departure. */
    bySeason: Record<SeasonKey, OccupancyByClassRow[]>;
};

type Filter = 'sve' | SeasonKey;

const FILTERS: { key: Filter; label: string }[] = [
    { key: 'sve', label: 'Sve sezone' },
    { key: 'leto', label: 'Leto' },
    { key: 'zima', label: 'Zima' },
    { key: 'van_sezone', label: 'Van sezone' },
];

const COLORS = {
    sold: '#3b82f6',
    total: '#cbd5e1',
} as const;

// ─── Format helpers ─────────────────────────────────────────────────────────────

function formatNum(n: number): string {
    return n.toLocaleString('sr-RS');
}

function formatPct(n: number): string {
    return `${n.toFixed(1)}%`;
}

function loadFactorCls(pct: number): string {
    if (pct >= 80) {
        return 'text-emerald-600';
    }

    if (pct >= 40) {
        return 'text-amber-600';
    }

    return 'text-red-600';
}

// ─── Tooltip ─────────────────────────────────────────────────────────────────────

function CustomTooltip({
    active,
    payload,
}: {
    active?: boolean;
    payload?: { payload: OccupancyByClassRow }[];
}) {
    if (!active || !payload?.length) {
        return null;
    }

    const row = payload[0].payload;

    return (
        <div className="rounded-lg border border-border bg-popover px-3 py-2 text-sm shadow-md">
            <p className="mb-1.5 font-medium">{row.class_name}</p>
            <div className="space-y-1">
                <div className="flex items-center justify-between gap-4">
                    <span className="flex items-center gap-1.5 text-muted-foreground">
                        <span
                            className="h-2 w-2 rounded-sm"
                            style={{ backgroundColor: COLORS.sold }}
                        />
                        Prodato:
                    </span>
                    <span className="font-medium">{formatNum(row.sold)}</span>
                </div>
                <div className="flex items-center justify-between gap-4">
                    <span className="flex items-center gap-1.5 text-muted-foreground">
                        <span
                            className="h-2 w-2 rounded-sm"
                            style={{ backgroundColor: COLORS.total }}
                        />
                        Ukupno sedišta:
                    </span>
                    <span className="font-medium">
                        {formatNum(row.total_seats)}
                    </span>
                </div>
                <div className="flex items-center justify-between gap-4 border-t border-border pt-1">
                    <span className="text-muted-foreground">Popunjenost:</span>
                    <span
                        className={`font-semibold ${loadFactorCls(row.occupancy_pct)}`}
                    >
                        {formatPct(row.occupancy_pct)}
                    </span>
                </div>
            </div>
        </div>
    );
}

// ─── Section ───────────────────────────────────────────────────────────────────

export default function OccupancyByClassSection({ all, bySeason }: Props) {
    const [filter, setFilter] = useState<Filter>('sve');

    const rows = filter === 'sve' ? all : bySeason[filter];
    const hasData = rows.some((r) => r.sold > 0 || r.total_seats > 0);

    return (
        <section className="rounded-xl border border-border bg-card p-6 shadow-sm">
            <div className="mb-5 flex flex-wrap items-start justify-between gap-3">
                <div className="flex items-start gap-3">
                    <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary">
                        <BarChart3 className="h-5 w-5" />
                    </span>
                    <div>
                        <h2 className="text-base font-semibold tracking-tight">
                            Popunjenost po klasama
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            Odnos prodatih i ukupnih sedišta po klasi
                        </p>
                    </div>
                </div>

                {/* Season toggle */}
                <div className="flex flex-wrap gap-1.5">
                    {FILTERS.map((f) => (
                        <button
                            key={f.key}
                            type="button"
                            onClick={() => setFilter(f.key)}
                            className={`rounded-md px-2.5 py-1 text-xs font-medium transition-colors ${
                                filter === f.key
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-muted text-muted-foreground hover:bg-muted/80 hover:text-foreground'
                            }`}
                        >
                            {f.label}
                        </button>
                    ))}
                </div>
            </div>

            {!hasData ? (
                <p className="py-12 text-center text-sm text-muted-foreground">
                    Nema podataka o prodaji za izabranu sezonu.
                </p>
            ) : (
                <>
                    <ResponsiveContainer width="100%" height={300}>
                        <BarChart
                            data={rows}
                            margin={{ top: 4, right: 8, left: -10, bottom: 8 }}
                            aria-label="Grupisani stubičasti grafikon: prodato u odnosu na ukupna sedišta po klasi"
                        >
                            <CartesianGrid
                                strokeDasharray="3 3"
                                vertical={false}
                                stroke="hsl(var(--border))"
                            />
                            <XAxis
                                dataKey="class_name"
                                tick={{ fontSize: 12 }}
                                interval={0}
                            />
                            <YAxis
                                tick={{ fontSize: 12 }}
                                width={40}
                                allowDecimals={false}
                            />
                            <Tooltip
                                content={<CustomTooltip />}
                                cursor={{ fill: 'hsl(var(--muted))' }}
                            />
                            <Legend
                                wrapperStyle={{ fontSize: 12, paddingTop: 8 }}
                                iconType="square"
                            />
                            <Bar
                                dataKey="sold"
                                name="Prodato"
                                fill={COLORS.sold}
                                radius={[3, 3, 0, 0]}
                            />
                            <Bar
                                dataKey="total_seats"
                                name="Ukupno sedišta"
                                fill={COLORS.total}
                                radius={[3, 3, 0, 0]}
                            />
                        </BarChart>
                    </ResponsiveContainer>

                    {/* Backing table */}
                    <div className="mt-4 overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-border text-left text-xs tracking-wide text-muted-foreground uppercase">
                                    <th className="py-2 pr-4 font-medium">
                                        Klasa
                                    </th>
                                    <th className="py-2 pr-4 text-right font-medium">
                                        Prodato
                                    </th>
                                    <th className="py-2 pr-4 text-right font-medium">
                                        Ukupno sedišta
                                    </th>
                                    <th className="py-2 text-right font-medium">
                                        Popunjenost
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {rows.map((row) => (
                                    <tr
                                        key={row.class_id}
                                        className="border-b border-border/60 last:border-0"
                                    >
                                        <td className="py-2 pr-4 font-medium">
                                            {row.class_name}
                                        </td>
                                        <td className="py-2 pr-4 text-right tabular-nums">
                                            {formatNum(row.sold)}
                                        </td>
                                        <td className="py-2 pr-4 text-right text-muted-foreground tabular-nums">
                                            {formatNum(row.total_seats)}
                                        </td>
                                        <td
                                            className={`py-2 text-right font-semibold tabular-nums ${loadFactorCls(row.occupancy_pct)}`}
                                        >
                                            {formatPct(row.occupancy_pct)}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </>
            )}
        </section>
    );
}
