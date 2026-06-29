import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    CalendarDays,
    CheckCircle2,
    Clock,
    RefreshCw,
    TicketCheck,
    TicketX,
} from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

// ─── Types ────────────────────────────────────────────────────────────────────

type OutcomeSummary = {
    success_count: number;
    partial_count: number;
    fail_count: number;
    success_pct: number;
    partial_pct: number;
    fail_pct: number;
};

type Analytics = {
    total_tickets: number;
    open_tickets: number;
    avg_resolution_minutes: number | null;
    tickets_by_category: { category_id: number; category_name: string; count: number; percentage: number }[];
    resolution_time_by_category: { category_id: number; category_name: string; avg_minutes: number; min_minutes: number; max_minutes: number }[];
    outcome_summary: OutcomeSummary;
    previous_period_outcome_summary: OutcomeSummary;
    top_flights_by_issues: { flight_id: number; flight_number: string; route_name: string | null; total: number; success: number; partial: number; fail: number }[];
    daily_counts: { date: string; count: number }[];
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

type Preset = '7d' | '30d' | 'quarter' | 'year' | 'custom';

function presetRange(p: Preset): [string, string] {
    const t = today();
    switch (p) {
        case '7d':      return [daysAgo(6), t];
        case '30d':     return [daysAgo(29), t];
        case 'quarter': return [startOfQuarter(), t];
        case 'year':    return [startOfYear(), t];
        default:        return [t, t];
    }
}

function detectPreset(from: string, to: string): Preset {
    const t = today();
    if (to !== t) return 'custom';
    if (from === daysAgo(6)) return '7d';
    if (from === daysAgo(29)) return '30d';
    if (from === startOfQuarter()) return 'quarter';
    if (from === startOfYear()) return 'year';
    return 'custom';
}

// ─── Format helpers ───────────────────────────────────────────────────────────

function formatMinutes(minutes: number | null): string {
    if (minutes === null || minutes <= 0) return '—';
    const h = Math.floor(minutes / 60);
    const m = Math.round(minutes % 60);
    if (h === 0) return `${m}m`;
    if (m === 0) return `${h}h`;
    return `${h}h ${m}m`;
}

function successRateBadgeClass(pct: number): string {
    if (pct >= 70) return 'text-emerald-600 bg-emerald-50';
    if (pct >= 40) return 'text-amber-600 bg-amber-50';
    return 'text-red-600 bg-red-50';
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
            <div className={`mt-2 text-2xl font-bold tracking-tight ${valueCls ?? ''}`}>
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

export default function PodrskaStatistike() {
    const { props } = usePage<PageProps>();
    const { analytics, period } = props;

    const [preset, setPreset] = useState<Preset>(() =>
        detectPreset(period.date_from, period.date_to),
    );
    const [customFrom, setCustomFrom] = useState(period.date_from);
    const [customTo, setCustomTo] = useState(period.date_to);

    function applyRange(from: string, to: string) {
        router.get(
            '/admin/podrska/statistike',
            { date_from: from, date_to: to },
            { preserveState: true, preserveScroll: true },
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
        router.get(
            '/admin/podrska/statistike',
            { date_from: period.date_from, date_to: period.date_to },
            { preserveScroll: true },
        );
    }

    const { total_tickets, open_tickets, avg_resolution_minutes, outcome_summary } = analytics;
    const successPct = outcome_summary.success_pct;

    return (
        <>
            <Head title="Praćenje podrške" />

            {/* Breadcrumb */}
            <div className="mb-6 flex items-center gap-2 text-sm text-muted-foreground">
                <Link href="/admin" className="hover:text-foreground">
                    Admin
                </Link>
                <span>/</span>
                <span>Korisnička podrška</span>
                <span>/</span>
                <span className="font-medium text-foreground">Statistike</span>
            </div>

            {/* Title row */}
            <div className="mb-8 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">
                        Praćenje uspješnosti korisničke podrške
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Analitika tiketa za izabrani vremenski period.
                    </p>
                </div>
                <Button variant="outline" size="sm" onClick={refresh}>
                    <RefreshCw className="mr-1.5 h-3.5 w-3.5" />
                    Osveži podatke
                </Button>
            </div>

            {/* Date filter */}
            <div className="mb-8 rounded-xl border border-border bg-card p-5 shadow-sm">
                <div className="flex flex-wrap items-end gap-3">
                    <div className="flex flex-wrap gap-2">
                        <PresetBtn active={preset === '7d'} onClick={() => selectPreset('7d')}>
                            Zadnjih 7 dana
                        </PresetBtn>
                        <PresetBtn active={preset === '30d'} onClick={() => selectPreset('30d')}>
                            Zadnjih 30 dana
                        </PresetBtn>
                        <PresetBtn active={preset === 'quarter'} onClick={() => selectPreset('quarter')}>
                            Ovaj kvartal
                        </PresetBtn>
                        <PresetBtn active={preset === 'year'} onClick={() => selectPreset('year')}>
                            Ova godina
                        </PresetBtn>
                        <PresetBtn active={preset === 'custom'} onClick={() => setPreset('custom')}>
                            <CalendarDays className="mr-1 inline h-3.5 w-3.5" />
                            Prilagođeno
                        </PresetBtn>
                    </div>

                    {preset === 'custom' && (
                        <form onSubmit={applyCustom} className="flex flex-wrap items-end gap-3">
                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="date_from">Od datuma</Label>
                                <Input
                                    id="date_from"
                                    type="date"
                                    value={customFrom}
                                    max={customTo}
                                    onChange={(e) => setCustomFrom(e.target.value)}
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
                                    onChange={(e) => setCustomTo(e.target.value)}
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
                    label="Ukupno tiketa"
                    value={total_tickets.toLocaleString('sr-RS')}
                    hint="Svi tiketi u periodu"
                />
                <KpiCard
                    icon={TicketX}
                    label="Otvoreni tiketi"
                    value={open_tickets.toLocaleString('sr-RS')}
                    hint="Aktivni (open / in_progress)"
                />
                <KpiCard
                    icon={Clock}
                    label="Prosečno vreme rešavanja"
                    value={formatMinutes(avg_resolution_minutes)}
                    hint="Zatvoreni tiketi"
                />
                <KpiCard
                    icon={CheckCircle2}
                    label="Stopa uspešnosti"
                    value={`${successPct.toFixed(1)}%`}
                    hint={`${outcome_summary.success_count} uspešno od ${outcome_summary.success_count + outcome_summary.partial_count + outcome_summary.fail_count} zatvorenih`}
                    valueCls={successRateBadgeClass(successPct).split(' ')[0]}
                />
            </div>

            {/* Chart area — filled by subsequent stories */}
            <div id="support-charts-area" />
        </>
    );
}
