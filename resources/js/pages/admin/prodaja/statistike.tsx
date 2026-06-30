import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    Banknote,
    CalendarDays,
    FileDown,
    Gauge,
    Inbox,
    Loader2,
    RefreshCw,
    TicketCheck,
    TicketX,
} from 'lucide-react';
import { useState } from 'react';
import CancellationAnalyticsSection from '@/components/sales/CancellationAnalyticsSection';
import type {
    CancellationByFlightRow,
    RisingRoute,
} from '@/components/sales/CancellationAnalyticsSection';
import OccupancyByClassSection from '@/components/sales/OccupancyByClassSection';
import OccupancyExtremesSection from '@/components/sales/OccupancyExtremesSection';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

// ─── Types (mirror App\Services\SalesAnalyticsService::analytics) ───────────────

type Kpis = {
    tickets_sold: number;
    revenue: number;
    cancellation_rate_pct: number;
    avg_occupancy_pct: number;
};

type OccupancyByClass = {
    class_id: number;
    class_name: string;
    sold: number;
    total_seats: number;
    occupancy_pct: number;
};

type Cancellation = {
    total_reservations: number;
    cancelled_reservations: number;
    rate_pct: number;
};

type FlightOccupancy = {
    flight_id: number;
    flight_number: string;
    route_name: string | null;
    date: string;
    capacity: number;
    sold: number;
    occupancy_pct: number;
};

type Analytics = {
    kpis: Kpis;
    occupancy_by_class: OccupancyByClass[];
    occupancy_by_class_by_season: Record<
        'leto' | 'zima' | 'van_sezone',
        OccupancyByClass[]
    >;
    cancellation: Cancellation;
    cancellation_trend: { date: string; count: number }[];
    cancellation_by_flight: CancellationByFlightRow[];
    rising_cancellations: RisingRoute[];
    occupancy_extremes: {
        high_threshold: number;
        low_threshold: number;
        highest: FlightOccupancy[];
        lowest: FlightOccupancy[];
    };
};

type PageProps = {
    analytics: Analytics;
    period: { date_from: string; date_to: string };
};

// ─── Date helpers ─────────────────────────────────────────────────────────────

function toDateStr(d: Date): string {
    return d.toISOString().split('T')[0];
}

function today(): string {
    return toDateStr(new Date());
}

function daysAgo(n: number): string {
    const d = new Date();
    d.setDate(d.getDate() - n);

    return toDateStr(d);
}

function startOfQuarter(): string {
    const d = new Date();
    const m = Math.floor(d.getMonth() / 3) * 3;

    return toDateStr(new Date(d.getFullYear(), m, 1));
}

function startOfYear(): string {
    return toDateStr(new Date(new Date().getFullYear(), 0, 1));
}

type Preset = 'today' | 'week' | 'month' | 'quarter' | 'year' | 'custom';

function presetRange(p: Preset): [string, string] {
    const t = today();

    switch (p) {
        case 'today':
            return [t, t];
        case 'week':
            return [daysAgo(6), t];
        case 'month':
            return [daysAgo(29), t];
        case 'quarter':
            return [startOfQuarter(), t];
        case 'year':
            return [startOfYear(), t];
        default:
            return [t, t];
    }
}

function detectPreset(from: string, to: string): Preset {
    const t = today();

    if (to !== t) {
        return 'custom';
    }

    if (from === t) {
        return 'today';
    }

    if (from === daysAgo(6)) {
        return 'week';
    }

    if (from === daysAgo(29)) {
        return 'month';
    }

    if (from === startOfQuarter()) {
        return 'quarter';
    }

    if (from === startOfYear()) {
        return 'year';
    }

    return 'custom';
}

// ─── Format helpers ───────────────────────────────────────────────────────────

function formatCurrency(n: number): string {
    return n.toLocaleString('sr-RS') + ' RSD';
}

function formatPct(n: number): string {
    return n.toFixed(1) + '%';
}

function cancellationRateCls(pct: number): string {
    if (pct >= 30) {
        return 'text-red-600';
    }

    if (pct >= 15) {
        return 'text-amber-600';
    }

    return 'text-emerald-600';
}

// ─── KPI Card ─────────────────────────────────────────────────────────────────

function KpiCard({
    label,
    value,
    hint,
    icon: Icon,
    valueCls,
}: {
    label: string;
    value: string;
    hint?: string;
    icon: typeof TicketCheck;
    valueCls?: string;
}) {
    return (
        <div className="rounded-xl border border-border bg-card p-5 shadow-sm">
            <div className="flex items-center gap-2 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                <Icon className="h-3.5 w-3.5" />
                {label}
            </div>
            <div
                className={`mt-2 text-2xl font-bold tracking-tight ${valueCls ?? ''}`}
            >
                {value}
            </div>
            {hint && (
                <div className="mt-1 text-xs text-muted-foreground">{hint}</div>
            )}
        </div>
    );
}

// ─── Preset button ────────────────────────────────────────────────────────────

function PresetBtn({
    active,
    onClick,
    children,
}: {
    active: boolean;
    onClick: () => void;
    children: React.ReactNode;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={`rounded-md px-3 py-1.5 text-sm font-medium transition-colors ${
                active
                    ? 'bg-primary text-primary-foreground'
                    : 'bg-muted text-muted-foreground hover:bg-muted/80 hover:text-foreground'
            }`}
        >
            {children}
        </button>
    );
}

// ─── Page ─────────────────────────────────────────────────────────────────────

export default function ProdajaStatistike() {
    const { props } = usePage<PageProps>();
    const { analytics, period } = props;

    const [preset, setPreset] = useState<Preset>(() =>
        detectPreset(period.date_from, period.date_to),
    );
    const [customFrom, setCustomFrom] = useState(period.date_from);
    const [customTo, setCustomTo] = useState(period.date_to);
    const [isRefreshing, setIsRefreshing] = useState(false);

    function applyRange(from: string, to: string) {
        router.get(
            '/admin/prodaja/statistike',
            { date_from: from, date_to: to },
            {
                preserveState: true,
                preserveScroll: true,
                onStart: () => setIsRefreshing(true),
                onFinish: () => setIsRefreshing(false),
            },
        );
    }

    function selectPreset(p: Preset) {
        setPreset(p);

        if (p !== 'custom') {
            const [from, to] = presetRange(p);
            applyRange(from, to);
        }
    }

    function applyCustom(e: React.FormEvent) {
        e.preventDefault();
        applyRange(customFrom, customTo);
    }

    function refresh() {
        applyRange(period.date_from, period.date_to);
    }

    const [isGenerating, setIsGenerating] = useState(false);

    function downloadPdf() {
        if (isGenerating) {
            return;
        }

        setIsGenerating(true);

        const url =
            '/admin/prodaja/statistike/pdf?' +
            new URLSearchParams({
                date_from: period.date_from,
                date_to: period.date_to,
            }).toString();

        const a = document.createElement('a');
        a.href = url;
        a.rel = 'noopener';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);

        window.setTimeout(() => setIsGenerating(false), 1500);
    }

    const { kpis, cancellation } = analytics;
    const isEmpty =
        kpis.tickets_sold === 0 &&
        cancellation.total_reservations === 0 &&
        analytics.occupancy_extremes.highest.length === 0 &&
        analytics.occupancy_extremes.lowest.length === 0;

    return (
        <>
            <Head title="Analitika prodaje" />

            {/* Breadcrumb */}
            <div className="mb-6 flex items-center gap-2 text-sm text-muted-foreground">
                <Link href="/admin" className="hover:text-foreground">
                    Admin
                </Link>
                <span>/</span>
                <span>Prodaja</span>
                <span>/</span>
                <span className="font-medium text-foreground">Analitika</span>
            </div>

            {/* Title row */}
            <div className="mb-8 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">
                        Analitika prodaje
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Pregled prodaje karata za izabrani vremenski period.
                    </p>
                </div>
                <div className="flex flex-wrap gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={refresh}
                        disabled={isRefreshing}
                    >
                        {isRefreshing ? (
                            <Loader2 className="mr-1.5 h-3.5 w-3.5 animate-spin" />
                        ) : (
                            <RefreshCw className="mr-1.5 h-3.5 w-3.5" />
                        )}
                        Osveži podatke
                    </Button>
                    <Button
                        size="sm"
                        onClick={downloadPdf}
                        disabled={isGenerating}
                    >
                        {isGenerating ? (
                            <>
                                <Loader2 className="mr-1.5 h-3.5 w-3.5 animate-spin" />
                                Generisanje...
                            </>
                        ) : (
                            <>
                                <FileDown className="mr-1.5 h-3.5 w-3.5" />
                                Generiši PDF izveštaj
                            </>
                        )}
                    </Button>
                </div>
            </div>

            {/* Date filter */}
            <div className="mb-8 rounded-xl border border-border bg-card p-5 shadow-sm">
                <div className="flex flex-wrap items-end gap-3">
                    <div className="flex flex-wrap gap-2">
                        <PresetBtn
                            active={preset === 'today'}
                            onClick={() => selectPreset('today')}
                        >
                            Danas
                        </PresetBtn>
                        <PresetBtn
                            active={preset === 'week'}
                            onClick={() => selectPreset('week')}
                        >
                            Sedmica
                        </PresetBtn>
                        <PresetBtn
                            active={preset === 'month'}
                            onClick={() => selectPreset('month')}
                        >
                            Mesec
                        </PresetBtn>
                        <PresetBtn
                            active={preset === 'quarter'}
                            onClick={() => selectPreset('quarter')}
                        >
                            Kvartal
                        </PresetBtn>
                        <PresetBtn
                            active={preset === 'year'}
                            onClick={() => selectPreset('year')}
                        >
                            Godina
                        </PresetBtn>
                        <PresetBtn
                            active={preset === 'custom'}
                            onClick={() => setPreset('custom')}
                        >
                            <CalendarDays className="mr-1 inline h-3.5 w-3.5" />
                            Prilagođeno
                        </PresetBtn>
                    </div>

                    {preset === 'custom' && (
                        <form
                            onSubmit={applyCustom}
                            className="flex flex-wrap items-end gap-3"
                        >
                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="date_from">Od datuma</Label>
                                <Input
                                    id="date_from"
                                    type="date"
                                    value={customFrom}
                                    max={customTo}
                                    onChange={(e) =>
                                        setCustomFrom(e.target.value)
                                    }
                                    className="w-40"
                                />
                            </div>
                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="date_to">Do datuma</Label>
                                <Input
                                    id="date_to"
                                    type="date"
                                    value={customTo}
                                    min={customFrom}
                                    onChange={(e) =>
                                        setCustomTo(e.target.value)
                                    }
                                    className="w-40"
                                />
                            </div>
                            <Button type="submit" size="sm">
                                Primeni
                            </Button>
                        </form>
                    )}
                </div>

                <p className="mt-3 text-xs text-muted-foreground">
                    Period: {period.date_from} – {period.date_to}
                </p>
            </div>

            {/* KPI cards */}
            <div className="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <KpiCard
                    icon={TicketCheck}
                    label="Prodato karata"
                    value={kpis.tickets_sold.toLocaleString('sr-RS')}
                    hint="Sedišta na letovima u periodu (bez otkazanih)"
                />
                <KpiCard
                    icon={Banknote}
                    label="Prihod"
                    value={formatCurrency(kpis.revenue)}
                    hint="Plaćene i iskorišćene rezervacije"
                />
                <KpiCard
                    icon={TicketX}
                    label="Stopa otkazivanja"
                    value={formatPct(kpis.cancellation_rate_pct)}
                    valueCls={cancellationRateCls(kpis.cancellation_rate_pct)}
                    hint={`${cancellation.cancelled_reservations} od ${cancellation.total_reservations} rezervacija`}
                />
                <KpiCard
                    icon={Gauge}
                    label="Prosečna popunjenost"
                    value={formatPct(kpis.avg_occupancy_pct)}
                    hint="Prosek po letovima u periodu"
                />
            </div>

            {/* Empty / zero-data state */}
            {isEmpty && (
                <div className="mb-8 flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-border bg-card px-6 py-12 text-center">
                    <Inbox className="h-8 w-8 text-muted-foreground" />
                    <p className="text-sm font-medium">
                        Nema podataka o prodaji za izabrani period.
                    </p>
                    <p className="text-xs text-muted-foreground">
                        Promenite vremenski raspon ili osvežite podatke.
                    </p>
                </div>
            )}

            {/* Chart sections — mount points for S3–S5 child components */}
            {!isEmpty && (
                <div className="space-y-6">
                    {/* S3 — popunjenost po klasama (SCRUM-178) */}
                    <OccupancyByClassSection
                        all={analytics.occupancy_by_class}
                        bySeason={analytics.occupancy_by_class_by_season}
                    />

                    {/* S4 — analitika otkazivanja (SCRUM-179) */}
                    <CancellationAnalyticsSection
                        cancellation={analytics.cancellation}
                        trend={analytics.cancellation_trend}
                        byFlight={analytics.cancellation_by_flight}
                        rising={analytics.rising_cancellations}
                    />

                    {/* S5 — letovi visoke i niske popunjenosti (SCRUM-180) */}
                    <OccupancyExtremesSection
                        highest={analytics.occupancy_extremes.highest}
                        lowest={analytics.occupancy_extremes.lowest}
                        highThreshold={
                            analytics.occupancy_extremes.high_threshold
                        }
                        lowThreshold={
                            analytics.occupancy_extremes.low_threshold
                        }
                    />
                </div>
            )}
        </>
    );
}
