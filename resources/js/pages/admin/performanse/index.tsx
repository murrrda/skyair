import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

const MONTHS_SR = [
    'Januar',
    'Februar',
    'Mart',
    'April',
    'Maj',
    'Jun',
    'Jul',
    'Avgust',
    'Septembar',
    'Oktobar',
    'Novembar',
    'Decembar',
];

const monthLabel = (d: string) => {
    const dt = new Date(d);

    return `${MONTHS_SR[dt.getMonth()]} ${dt.getFullYear()}.`;
};

type Status = 'over' | 'near' | 'normal';

type Delta = { abs: number; pct: number | null };

type Kpis = {
    total_flights: number;
    total_hours: number;
    avg_hours_per_employee: number;
    over_limit: number;
    avg_consecutive_days: number;
    active_employees: number;
};

type EmployeeRow = {
    user_id: number;
    name: string;
    position: string;
    flights: number;
    hours: number;
    avg_hours_per_flight: number;
    max_consecutive_days: number;
    peak_week_hours: number;
    load_pct: number;
    status: Status;
};

type TrendPoint = {
    label: string;
    hours: number;
    flights: number;
    consecutive_days: number;
};

type Report = {
    kpis: Kpis;
    deltas: Record<
        | 'total_flights'
        | 'total_hours'
        | 'avg_hours_per_employee'
        | 'over_limit'
        | 'avg_consecutive_days',
        Delta
    >;
    hours_by_employee: { name: string; hours: number; status: Status }[];
    load_by_weekday: { day: string; flights: number; weekend: boolean }[];
    employees: EmployeeRow[];
    trends: { points: TrendPoint[] };
    cap: number;
    near_limit: number;
};

type Option = { value: number | string; label: string };

type PageProps = {
    report: Report;
    filters: {
        from: string;
        to: string;
        employee_id: number | null;
        role: string | null;
    };
    employeeOptions: Option[];
    positionOptions: Option[];
};

const STATUS = {
    over: {
        label: 'Prekoračenje',
        bar: 'bg-red-500',
        badge: 'bg-red-50 text-red-700 border-red-200',
        text: 'text-red-600',
    },
    near: {
        label: 'Blizu limita',
        bar: 'bg-amber-500',
        badge: 'bg-amber-50 text-amber-700 border-amber-200',
        text: 'text-amber-600',
    },
    normal: {
        label: 'Normalno',
        bar: 'bg-blue-600',
        badge: 'bg-blue-50 text-blue-700 border-blue-200',
        text: 'text-foreground',
    },
} as const;

const PER_PAGE = 6;

const fmt = (n: number) => new Intl.NumberFormat('sr-RS').format(n);

const fmtHm = (hours: number) => {
    const h = Math.floor(hours);
    const m = Math.round((hours - h) * 60);

    return `${h}h ${m}m`;
};

function DeltaBadge({
    delta,
    goodWhenUp,
    unit = '%',
}: {
    delta: Delta;
    goodWhenUp: boolean;
    unit?: '%' | 'abs';
}) {
    const value = unit === '%' ? delta.pct : delta.abs;

    if (value === null || value === 0) {
        return (
            <span className="text-xs text-muted-foreground">bez promene</span>
        );
    }

    const up = value > 0;
    const good = up === goodWhenUp;
    const text =
        unit === '%' ? `${up ? '+' : ''}${value}%` : `${up ? '+' : ''}${value}`;

    return (
        <span
            className={cn(
                'text-xs font-medium',
                good ? 'text-emerald-600' : 'text-red-600',
            )}
        >
            {up ? '↑' : '↓'} {text} vs. prethodni period
        </span>
    );
}

function KpiCard({
    title,
    value,
    subtitle,
    delta,
    goodWhenUp,
    unit,
}: {
    title: string;
    value: string;
    subtitle: string;
    delta: Delta;
    goodWhenUp: boolean;
    unit: '%' | 'abs';
}) {
    return (
        <div className="rounded-xl border border-border bg-card p-5">
            <div className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                {title}
            </div>
            <div className="mt-2 text-3xl font-bold tracking-tight">
                {value}
            </div>
            <div className="mt-1 text-xs text-muted-foreground">{subtitle}</div>
            <div className="mt-2">
                <DeltaBadge delta={delta} goodWhenUp={goodWhenUp} unit={unit} />
            </div>
        </div>
    );
}

function Sparkline({ values }: { values: number[] }) {
    const max = Math.max(1, ...values);

    return (
        <div className="flex h-12 items-end gap-1">
            {values.map((v, i) => (
                <div
                    key={i}
                    className={cn(
                        'flex-1 rounded-sm',
                        i === values.length - 1 ? 'bg-blue-600' : 'bg-blue-200',
                    )}
                    style={{ height: `${Math.max(6, (v / max) * 100)}%` }}
                />
            ))}
        </div>
    );
}

export default function Performanse() {
    const { report, filters, employeeOptions, positionOptions } =
        usePage<PageProps>().props;
    const { kpis, deltas } = report;

    const [from, setFrom] = useState(filters.from);
    const [to, setTo] = useState(filters.to);
    const [employeeId, setEmployeeId] = useState(
        filters.employee_id ? String(filters.employee_id) : '',
    );
    const [role, setRole] = useState(filters.role ?? '');
    const [page, setPage] = useState(1);

    // Export dialog state.
    const [exportOpen, setExportOpen] = useState(false);
    const [exportTitle, setExportTitle] = useState('');
    const [exportFrom, setExportFrom] = useState(from);
    const [exportTo, setExportTo] = useState(to);

    function apply(
        next: Partial<{
            from: string;
            to: string;
            employee_id: string;
            role: string;
        }> = {},
    ) {
        router.get(
            '/admin/performanse',
            {
                from: next.from ?? from,
                to: next.to ?? to,
                employee_id: (next.employee_id ?? employeeId) || undefined,
                role: (next.role ?? role) || undefined,
            },
            { preserveScroll: true, preserveState: true },
        );
    }

    function openExport() {
        setExportTitle(`Performanse posade — ${monthLabel(to)}`);
        setExportFrom(from);
        setExportTo(to);
        setExportOpen(true);
    }

    function downloadReport() {
        const params = new URLSearchParams({
            title: exportTitle,
            from: exportFrom,
            to: exportTo,
        });
        setExportOpen(false);
        window.location.href = `/admin/performanse/pdf?${params.toString()}`;
    }

    const maxHours = Math.max(
        1,
        ...report.hours_by_employee.map((e) => e.hours),
    );
    const maxDay = Math.max(1, ...report.load_by_weekday.map((d) => d.flights));
    const months = report.trends.points;
    const monthRange = months.length
        ? `${months[0].label} — ${months[months.length - 1].label} 2026.`
        : '';

    const pageCount = Math.max(
        1,
        Math.ceil(report.employees.length / PER_PAGE),
    );
    const current = Math.min(page, pageCount);
    const rows = report.employees.slice(
        (current - 1) * PER_PAGE,
        current * PER_PAGE,
    );

    return (
        <>
            <Head title="Performanse" />
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">
                        Performanse
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Opterećenje i produktivnost posade po izabranom periodu
                    </p>
                </div>

                {/* Filters */}
                <div className="grid items-end gap-4 rounded-xl border border-border bg-card p-4 md:grid-cols-[1fr_1fr_1fr_1fr_auto]">
                    <div>
                        <Label
                            htmlFor="from"
                            className="text-xs text-muted-foreground uppercase"
                        >
                            Period — od
                        </Label>
                        <Input
                            id="from"
                            type="date"
                            value={from}
                            onChange={(e) => {
                                setFrom(e.target.value);
                                apply({ from: e.target.value });
                            }}
                            className="mt-1"
                        />
                    </div>
                    <div>
                        <Label
                            htmlFor="to"
                            className="text-xs text-muted-foreground uppercase"
                        >
                            Period — do
                        </Label>
                        <Input
                            id="to"
                            type="date"
                            value={to}
                            onChange={(e) => {
                                setTo(e.target.value);
                                apply({ to: e.target.value });
                            }}
                            className="mt-1"
                        />
                    </div>
                    <div>
                        <Label className="text-xs text-muted-foreground uppercase">
                            Zaposleni
                        </Label>
                        <select
                            value={employeeId}
                            onChange={(e) => {
                                setEmployeeId(e.target.value);
                                apply({ employee_id: e.target.value });
                            }}
                            className="mt-1 h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option value="">Svi zaposleni</option>
                            {employeeOptions.map((o) => (
                                <option key={o.value} value={o.value}>
                                    {o.label}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <Label className="text-xs text-muted-foreground uppercase">
                            Pozicija
                        </Label>
                        <select
                            value={role}
                            onChange={(e) => {
                                setRole(e.target.value);
                                apply({ role: e.target.value });
                            }}
                            className="mt-1 h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option value="">Sve pozicije</option>
                            {positionOptions.map((o) => (
                                <option key={o.value} value={o.value}>
                                    {o.label}
                                </option>
                            ))}
                        </select>
                    </div>
                    <Button onClick={openExport}>Generiši izveštaj</Button>
                </div>

                {/* KPI cards */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <KpiCard
                        title="Ukupno letova"
                        value={fmt(kpis.total_flights)}
                        subtitle="u izabranom periodu"
                        delta={deltas.total_flights}
                        goodWhenUp
                        unit="%"
                    />
                    <KpiCard
                        title="Ukupno sati leta"
                        value={fmt(Math.round(kpis.total_hours))}
                        subtitle="sati u periodu"
                        delta={deltas.total_hours}
                        goodWhenUp
                        unit="%"
                    />
                    <KpiCard
                        title="Prosek sati / zaposleni"
                        value={kpis.avg_hours_per_employee.toFixed(1)}
                        subtitle={`od ${kpis.active_employees} aktivna zaposlena`}
                        delta={deltas.avg_hours_per_employee}
                        goodWhenUp
                        unit="%"
                    />
                    <KpiCard
                        title="Prekoračenje limita"
                        value={fmt(kpis.over_limit)}
                        subtitle={`zaposlenih iznad ${report.cap}h/ned`}
                        delta={deltas.over_limit}
                        goodWhenUp={false}
                        unit="abs"
                    />
                    <KpiCard
                        title="Prosek uzast. dana"
                        value={kpis.avg_consecutive_days.toFixed(1)}
                        subtitle="uzastopnih radnih dana"
                        delta={deltas.avg_consecutive_days}
                        goodWhenUp={false}
                        unit="abs"
                    />
                </div>

                {/* Charts */}
                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Hours per employee */}
                    <div className="rounded-xl border border-border bg-card p-6">
                        <h2 className="mb-4 text-sm font-semibold">
                            Sati leta po zaposlenom
                        </h2>
                        <div className="space-y-3">
                            {report.hours_by_employee.map((e) => (
                                <div
                                    key={e.name}
                                    className="flex items-center gap-3"
                                >
                                    <span className="w-28 shrink-0 text-right text-xs text-muted-foreground">
                                        {e.name}
                                    </span>
                                    <div className="relative h-6 flex-1 overflow-hidden rounded-md bg-muted">
                                        <div
                                            className={cn(
                                                'flex h-full items-center rounded-md px-2 text-[11px] font-semibold text-white',
                                                STATUS[e.status].bar,
                                            )}
                                            style={{
                                                width: `${Math.max(8, (e.hours / maxHours) * 100)}%`,
                                            }}
                                        >
                                            {e.hours}h
                                        </div>
                                    </div>
                                </div>
                            ))}
                            {report.hours_by_employee.length === 0 && (
                                <p className="text-sm text-muted-foreground">
                                    Nema podataka za izabrani period.
                                </p>
                            )}
                        </div>
                        <div className="mt-4 flex flex-wrap gap-4 text-xs text-muted-foreground">
                            <Legend
                                className="bg-red-500"
                                label={`Prekoračenje (>${report.cap}h/ned)`}
                            />
                            <Legend
                                className="bg-amber-500"
                                label={`Blizu limita (${report.near_limit}–${report.cap}h)`}
                            />
                            <Legend className="bg-blue-600" label="Normalno" />
                        </div>
                    </div>

                    {/* Load by weekday */}
                    <div className="rounded-xl border border-border bg-card p-6">
                        <h2 className="mb-1 text-sm font-semibold">
                            Opterećenje po danu{' '}
                            <span className="font-normal text-muted-foreground">
                                broj letova
                            </span>
                        </h2>
                        <div className="mt-6 flex h-48 items-end justify-between gap-2">
                            {report.load_by_weekday.map((d) => (
                                <div
                                    key={d.day}
                                    className="flex flex-1 flex-col items-center gap-1"
                                >
                                    <span
                                        className={cn(
                                            'text-xs font-semibold',
                                            d.weekend
                                                ? 'text-amber-600'
                                                : 'text-muted-foreground',
                                        )}
                                    >
                                        {d.flights}
                                    </span>
                                    <div
                                        className={cn(
                                            'w-full rounded-t-md',
                                            d.weekend
                                                ? 'bg-amber-500'
                                                : 'bg-blue-600',
                                        )}
                                        style={{
                                            height: `${Math.max(4, (d.flights / maxDay) * 100)}%`,
                                        }}
                                    />
                                    <span
                                        className={cn(
                                            'text-xs',
                                            d.weekend
                                                ? 'font-medium text-amber-600'
                                                : 'text-muted-foreground',
                                        )}
                                    >
                                        {d.day}
                                    </span>
                                </div>
                            ))}
                        </div>
                        <div className="mt-4 flex flex-wrap gap-4 text-xs text-muted-foreground">
                            <Legend
                                className="bg-blue-600"
                                label="Radni dani"
                            />
                            <Legend
                                className="bg-amber-500"
                                label="Vikend (veće opterećenje)"
                            />
                        </div>
                    </div>
                </div>

                {/* Trends */}
                <div className="grid gap-6 lg:grid-cols-3">
                    <TrendCard
                        title="Sati leta — mesečni trend"
                        value={`${fmt(Math.round(kpis.total_hours))}h`}
                        delta={
                            <DeltaBadge
                                delta={deltas.total_hours}
                                goodWhenUp
                                unit="%"
                            />
                        }
                        range={monthRange}
                        values={months.map((m) => m.hours)}
                    />
                    <TrendCard
                        title="Broj letova — mesečni trend"
                        value={fmt(kpis.total_flights)}
                        delta={
                            <DeltaBadge
                                delta={deltas.total_flights}
                                goodWhenUp
                                unit="%"
                            />
                        }
                        range={monthRange}
                        values={months.map((m) => m.flights)}
                    />
                    <TrendCard
                        title="Prosečni uzastopni radni dani"
                        value={`${kpis.avg_consecutive_days.toFixed(1)} dana`}
                        delta={
                            <DeltaBadge
                                delta={deltas.avg_consecutive_days}
                                goodWhenUp={false}
                                unit="abs"
                            />
                        }
                        range={monthRange}
                        values={months.map((m) => m.consecutive_days)}
                    />
                </div>

                {/* Detail table */}
                <div className="rounded-xl border border-border bg-card">
                    <div className="flex items-center justify-between border-b border-border px-6 py-4">
                        <h2 className="text-sm font-semibold">
                            Detalji po zaposlenom
                        </h2>
                        <span className="text-xs text-muted-foreground">
                            {report.employees.length} zaposlenih
                        </span>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-border text-left text-xs tracking-wide text-muted-foreground uppercase">
                                    <th className="px-6 py-3 font-medium">
                                        Zaposleni
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Letova
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Sati leta
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Prosek h/letu
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Uzast. radnih dana
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Opterećenje
                                    </th>
                                    <th className="px-4 py-3 font-medium">
                                        Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {rows.map((e) => (
                                    <tr
                                        key={e.user_id}
                                        className="border-b border-border/60 last:border-0"
                                    >
                                        <td className="px-6 py-3">
                                            <div className="flex items-center gap-3">
                                                <span className="flex h-8 w-8 items-center justify-center rounded-full bg-[#E6F1FB] text-xs font-semibold text-[#185FA5]">
                                                    {e.name
                                                        .split(' ')
                                                        .map((p) => p[0])
                                                        .slice(0, 2)
                                                        .join('')
                                                        .toUpperCase()}
                                                </span>
                                                <div>
                                                    <div className="font-medium">
                                                        {e.name}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {e.position}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 font-medium">
                                            {e.flights}
                                        </td>
                                        <td
                                            className={cn(
                                                'px-4 py-3 font-semibold',
                                                STATUS[e.status].text,
                                            )}
                                        >
                                            {e.hours}h
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {fmtHm(e.avg_hours_per_flight)}
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="flex items-center gap-0.5">
                                                {Array.from({ length: 8 }).map(
                                                    (_, i) => (
                                                        <span
                                                            key={i}
                                                            className={cn(
                                                                'h-2.5 w-2.5 rounded-[2px]',
                                                                i <
                                                                    e.max_consecutive_days
                                                                    ? 'bg-blue-600'
                                                                    : 'bg-muted',
                                                            )}
                                                        />
                                                    ),
                                                )}
                                            </div>
                                            <div className="mt-1 text-xs text-muted-foreground">
                                                {e.max_consecutive_days} dana
                                            </div>
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="h-1.5 w-28 overflow-hidden rounded-full bg-muted">
                                                <div
                                                    className={cn(
                                                        'h-full rounded-full',
                                                        STATUS[e.status].bar,
                                                    )}
                                                    style={{
                                                        width: `${Math.min(100, e.load_pct)}%`,
                                                    }}
                                                />
                                            </div>
                                            <div className="mt-1 text-xs text-muted-foreground">
                                                {e.peak_week_hours} /{' '}
                                                {report.cap}h max
                                            </div>
                                        </td>
                                        <td className="px-4 py-3">
                                            <span
                                                className={cn(
                                                    'inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-medium',
                                                    STATUS[e.status].badge,
                                                )}
                                            >
                                                {STATUS[e.status].label}
                                            </span>
                                        </td>
                                    </tr>
                                ))}
                                {rows.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={7}
                                            className="px-6 py-8 text-center text-sm text-muted-foreground"
                                        >
                                            Nema podataka za izabrani period.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                    {report.employees.length > PER_PAGE && (
                        <div className="flex items-center justify-between border-t border-border px-6 py-3 text-sm">
                            <span className="text-xs text-muted-foreground">
                                Prikazano {(current - 1) * PER_PAGE + 1}–
                                {Math.min(
                                    current * PER_PAGE,
                                    report.employees.length,
                                )}{' '}
                                od {report.employees.length} zaposlenih
                            </span>
                            <div className="flex items-center gap-1">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={current <= 1}
                                    onClick={() => setPage(current - 1)}
                                >
                                    « Prethodna
                                </Button>
                                <span className="px-2 text-xs text-muted-foreground">
                                    {current} / {pageCount}
                                </span>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={current >= pageCount}
                                    onClick={() => setPage(current + 1)}
                                >
                                    Sledeća »
                                </Button>
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Export dialog (Figma 214-4512) */}
            <Dialog open={exportOpen} onOpenChange={setExportOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Generiši izveštaj</DialogTitle>
                        <p className="text-sm text-muted-foreground">
                            Podesite parametre i format izvoza
                        </p>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div>
                            <Label
                                htmlFor="export-title"
                                className="text-xs text-muted-foreground uppercase"
                            >
                                Naslov izveštaja
                            </Label>
                            <Input
                                id="export-title"
                                value={exportTitle}
                                onChange={(e) => setExportTitle(e.target.value)}
                                className="mt-1"
                            />
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label
                                    htmlFor="export-from"
                                    className="text-xs text-muted-foreground uppercase"
                                >
                                    Period — od
                                </Label>
                                <Input
                                    id="export-from"
                                    type="date"
                                    value={exportFrom}
                                    onChange={(e) =>
                                        setExportFrom(e.target.value)
                                    }
                                    className="mt-1"
                                />
                            </div>
                            <div>
                                <Label
                                    htmlFor="export-to"
                                    className="text-xs text-muted-foreground uppercase"
                                >
                                    Period — do
                                </Label>
                                <Input
                                    id="export-to"
                                    type="date"
                                    value={exportTo}
                                    onChange={(e) =>
                                        setExportTo(e.target.value)
                                    }
                                    className="mt-1"
                                />
                            </div>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setExportOpen(false)}
                        >
                            Otkaži
                        </Button>
                        <Button onClick={downloadReport}>
                            Generiši i preuzmi
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

function Legend({ className, label }: { className: string; label: string }) {
    return (
        <span className="flex items-center gap-1.5">
            <span className={cn('h-2.5 w-2.5 rounded-[2px]', className)} />
            {label}
        </span>
    );
}

function TrendCard({
    title,
    value,
    delta,
    range,
    values,
}: {
    title: string;
    value: string;
    delta: React.ReactNode;
    range: string;
    values: number[];
}) {
    return (
        <div className="rounded-xl border border-border bg-card p-5">
            <div className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                {title}
            </div>
            <div className="mt-2 flex items-baseline gap-2">
                <span className="text-2xl font-bold tracking-tight">
                    {value}
                </span>
                {delta}
            </div>
            <div className="mt-3">
                <Sparkline values={values} />
            </div>
            <div className="mt-2 text-xs text-muted-foreground">{range}</div>
        </div>
    );
}
