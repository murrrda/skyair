import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    Activity,
    BarChart3,
    Plane as PlaneIcon,
    RefreshCw,
    Wrench,
} from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type UtilizationRow = {
    plane_id: number;
    reg_number: number;
    model: string;
    flights: number;
    flight_hours: number;
    utilization: number;
};

type FlightsRow = {
    plane_id: number;
    reg_number: number;
    model: string;
    flights: number;
};

type ServiceRow = {
    plane_id: number;
    reg_number: number;
    model: string;
    services: number;
    avg_days: number | null;
};

type MonthRow = {
    month: string;
    label: string;
    plane: number;
    route: number;
};

type PageProps = {
    period: { from: string; to: string };
    summary: {
        fleet_utilization: number;
        total_flights: number;
        avg_days_between_services: number | null;
        total_plan_changes: number;
        planes_count: number;
    };
    utilization: UtilizationRow[];
    flights_per_plane: FlightsRow[];
    service_intervals: ServiceRow[];
    changes: {
        total: number;
        plane_total: number;
        route_total: number;
        months: MonthRow[];
    };
    operational_hours_per_day: number;
};

function formatNumber(n: number): string {
    return n.toLocaleString('sr-RS');
}

function SectionCard({
    title,
    description,
    icon: Icon,
    children,
}: {
    title: string;
    description: string;
    icon: typeof BarChart3;
    children: React.ReactNode;
}) {
    return (
        <section className="rounded-xl border border-border bg-card p-6 shadow-sm">
            <div className="mb-5 flex items-start gap-3">
                <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary">
                    <Icon className="h-5 w-5" />
                </span>
                <div>
                    <h2 className="text-base font-semibold tracking-tight">
                        {title}
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        {description}
                    </p>
                </div>
            </div>
            {children}
        </section>
    );
}

/** Horizontal bar list, each bar scaled against the largest value in the set. */
function HorizontalBars({
    rows,
    emptyLabel,
}: {
    rows: {
        key: number;
        label: string;
        sublabel: string;
        value: number;
        display: string;
    }[];
    emptyLabel: string;
}) {
    const max = Math.max(1, ...rows.map((r) => r.value));

    if (rows.length === 0) {
        return (
            <p className="py-8 text-center text-sm text-muted-foreground">
                {emptyLabel}
            </p>
        );
    }

    return (
        <div className="flex flex-col gap-3">
            {rows.map((row) => (
                <div key={row.key} className="flex items-center gap-3">
                    <div className="w-32 shrink-0 text-right">
                        <div className="text-sm font-medium">{row.label}</div>
                        <div className="truncate text-xs text-muted-foreground">
                            {row.sublabel}
                        </div>
                    </div>
                    <div className="relative h-7 flex-1 overflow-hidden rounded-md bg-muted">
                        <div
                            className="h-full rounded-md bg-primary/80 transition-all"
                            style={{
                                width: `${(row.value / max) * 100}%`,
                                minWidth: row.value > 0 ? '2px' : '0',
                            }}
                        />
                    </div>
                    <div className="w-20 shrink-0 text-sm font-semibold tabular-nums">
                        {row.display}
                    </div>
                </div>
            ))}
        </div>
    );
}

/** Monthly stacked column chart (aircraft changes + route changes). */
function MonthlyChangesChart({ months }: { months: MonthRow[] }) {
    const max = Math.max(1, ...months.map((m) => m.plane + m.route));
    const hasData = months.some((m) => m.plane + m.route > 0);

    return (
        <div>
            <div className="mb-4 flex items-center gap-5 text-xs">
                <span className="flex items-center gap-1.5">
                    <span className="h-3 w-3 rounded-sm bg-primary/80" /> Izmena
                    aviona
                </span>
                <span className="flex items-center gap-1.5">
                    <span className="h-3 w-3 rounded-sm bg-[#f59e0b]" /> Izmena
                    rute
                </span>
            </div>
            <div className="flex h-56 items-stretch gap-2">
                {months.map((m) => {
                    const total = m.plane + m.route;

                    return (
                        <div
                            key={m.month}
                            className="flex flex-1 flex-col items-center"
                        >
                            <div className="flex w-full flex-1 flex-col justify-end gap-1">
                                <div className="text-center text-xs font-medium text-muted-foreground">
                                    {total > 0 ? total : ''}
                                </div>
                                <div
                                    className="mx-auto flex w-full max-w-10 flex-col-reverse overflow-hidden rounded-t-md"
                                    style={{
                                        height: `${(total / max) * 100}%`,
                                    }}
                                >
                                    <div
                                        className="w-full bg-primary/80"
                                        style={{
                                            flexGrow: m.plane,
                                            minHeight: m.plane > 0 ? 2 : 0,
                                        }}
                                    />
                                    <div
                                        className="w-full bg-[#f59e0b]"
                                        style={{
                                            flexGrow: m.route,
                                            minHeight: m.route > 0 ? 2 : 0,
                                        }}
                                    />
                                </div>
                            </div>
                            <div className="mt-2 text-[11px] text-muted-foreground">
                                {m.label}
                            </div>
                        </div>
                    );
                })}
            </div>
            {!hasData && (
                <p className="mt-4 text-center text-sm text-muted-foreground">
                    Nema izmena plana leta u izabranom periodu.
                </p>
            )}
        </div>
    );
}

function SummaryCard({
    label,
    value,
    hint,
}: {
    label: string;
    value: string;
    hint: string;
}) {
    return (
        <div className="rounded-xl border border-border bg-card p-5 shadow-sm">
            <div className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                {label}
            </div>
            <div className="mt-2 text-2xl font-bold tracking-tight">
                {value}
            </div>
            <div className="mt-1 text-xs text-muted-foreground">{hint}</div>
        </div>
    );
}

export default function StatistikaIndex() {
    const { props } = usePage<PageProps>();
    const {
        period,
        summary,
        utilization,
        flights_per_plane,
        service_intervals,
        changes,
        operational_hours_per_day,
    } = props;

    const [from, setFrom] = useState(period.from);
    const [to, setTo] = useState(period.to);

    function applyPeriod(e: React.FormEvent) {
        e.preventDefault();
        router.get(
            '/admin/statistika',
            { from, to },
            { preserveState: true, preserveScroll: true },
        );
    }

    return (
        <>
            <Head title="Statistika flote" />
            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <header className="w-full border-b border-border/60">
                    <div className="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-4">
                        <div className="flex items-center gap-3">
                            <Link
                                href="/admin"
                                className="text-sm text-muted-foreground hover:text-foreground"
                            >
                                ← Admin panel
                            </Link>
                            <span className="text-muted-foreground">/</span>
                            <span className="text-sm font-medium">
                                Statistika flote
                            </span>
                        </div>
                    </div>
                </header>

                <main className="mx-auto w-full max-w-6xl flex-1 px-6 py-10">
                    <div className="mb-8">
                        <h1 className="text-2xl font-bold tracking-tight">
                            Statistika iskorišćenosti flote
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Pregled celokupne statistike flote za izabrani
                            period.
                        </p>
                    </div>

                    <form
                        onSubmit={applyPeriod}
                        className="mb-8 flex flex-wrap items-end gap-4 rounded-xl border border-border bg-card p-5 shadow-sm"
                    >
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="from">Od datuma</Label>
                            <Input
                                id="from"
                                type="date"
                                value={from}
                                max={to}
                                onChange={(e) => setFrom(e.target.value)}
                                className="w-44"
                            />
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="to">Do datuma</Label>
                            <Input
                                id="to"
                                type="date"
                                value={to}
                                min={from}
                                onChange={(e) => setTo(e.target.value)}
                                className="w-44"
                            />
                        </div>
                        <Button type="submit">
                            <RefreshCw className="mr-1 h-4 w-4" />
                            Primeni
                        </Button>
                    </form>

                    <div className="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <SummaryCard
                            label="Iskorišćenost flote"
                            value={`${formatNumber(summary.fleet_utilization)}%`}
                            hint={`Prosek za ${summary.planes_count} aviona`}
                        />
                        <SummaryCard
                            label="Ukupno letova"
                            value={formatNumber(summary.total_flights)}
                            hint="U izabranom periodu"
                        />
                        <SummaryCard
                            label="Prosek između servisa"
                            value={
                                summary.avg_days_between_services !== null
                                    ? `${formatNumber(summary.avg_days_between_services)} dana`
                                    : '—'
                            }
                            hint="Prosek na nivou flote"
                        />
                        <SummaryCard
                            label="Izmene plana leta"
                            value={formatNumber(summary.total_plan_changes)}
                            hint={`${formatNumber(changes.plane_total)} aviona · ${formatNumber(changes.route_total)} ruta`}
                        />
                    </div>

                    <div className="grid gap-6 lg:grid-cols-2">
                        <SectionCard
                            title="Iskorišćenost flote"
                            description={`Udeo letnih sati u odnosu na ${operational_hours_per_day} operativnih sati dnevno`}
                            icon={Activity}
                        >
                            <HorizontalBars
                                emptyLabel="Nema aviona u floti."
                                rows={utilization.map((row) => ({
                                    key: row.plane_id,
                                    label: String(row.reg_number),
                                    sublabel: row.model,
                                    value: row.utilization,
                                    display: `${formatNumber(row.utilization)}%`,
                                }))}
                            />
                        </SectionCard>

                        <SectionCard
                            title="Broj letova po avionu"
                            description="Ukupan broj letova po avionu u periodu"
                            icon={PlaneIcon}
                        >
                            <HorizontalBars
                                emptyLabel="Nema letova u izabranom periodu."
                                rows={flights_per_plane.map((row) => ({
                                    key: row.plane_id,
                                    label: String(row.reg_number),
                                    sublabel: row.model,
                                    value: row.flights,
                                    display: formatNumber(row.flights),
                                }))}
                            />
                        </SectionCard>

                        <SectionCard
                            title="Prosečno vreme između servisa"
                            description="Prosečan broj dana između uzastopnih servisa po avionu"
                            icon={Wrench}
                        >
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b border-border text-left text-xs tracking-wide text-muted-foreground uppercase">
                                            <th className="pb-2 font-medium">
                                                Avion
                                            </th>
                                            <th className="pb-2 text-center font-medium">
                                                Servisa
                                            </th>
                                            <th className="pb-2 text-right font-medium">
                                                Prosek (dana)
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {service_intervals.map((row) => (
                                            <tr
                                                key={row.plane_id}
                                                className="border-b border-border/60 last:border-0"
                                            >
                                                <td className="py-2.5">
                                                    <div className="font-medium">
                                                        {row.reg_number}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {row.model}
                                                    </div>
                                                </td>
                                                <td className="py-2.5 text-center tabular-nums">
                                                    {row.services}
                                                </td>
                                                <td className="py-2.5 text-right">
                                                    {row.avg_days !== null ? (
                                                        <span className="font-semibold tabular-nums">
                                                            {formatNumber(
                                                                row.avg_days,
                                                            )}
                                                        </span>
                                                    ) : (
                                                        <span className="text-xs text-muted-foreground">
                                                            Nedovoljno podataka
                                                        </span>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                        {service_intervals.length === 0 && (
                                            <tr>
                                                <td
                                                    colSpan={3}
                                                    className="py-8 text-center text-muted-foreground"
                                                >
                                                    Nema aviona u floti.
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </SectionCard>

                        <SectionCard
                            title="Učestalost izmena u planu leta"
                            description="Broj izmena aviona i ruta po mesecima"
                            icon={BarChart3}
                        >
                            <MonthlyChangesChart months={changes.months} />
                        </SectionCard>
                    </div>
                </main>
            </div>
        </>
    );
}
