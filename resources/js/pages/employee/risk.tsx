import { Head } from '@inertiajs/react';
import { cn } from '@/lib/utils';

// ─── Types ───────────────────────────────────────────────────────────────────

type IncidentRow = {
    id: number;
    flight: { number: string; route: string; plane: string };
    occurred_at: string;
    occurred_time: string;
    type: string;
    severity: { name: string; rank: number };
    others: string[];
};

type Props = {
    employee: {
        user_id: number;
        name: string;
        initials: string;
        role: string;
        email: string | null;
    };
    pause: {
        from: string | null;
        to: string | null;
        duration_days: number;
        reason: string;
    };
    stats: {
        recent_count: number;
        threshold: number;
        over_by: number;
        window_days: number;
        total_count: number;
    };
    incidents: IncidentRow[];
};

// ─── Severity styling (by rank) ────────────────────────────────────────────────

const SEVERITY_STYLES: Record<number, string> = {
    1: 'bg-[#e1f5ee] text-[#0f6e56]',
    2: 'bg-[#fef9e7] text-[#7d6608]',
    3: 'bg-[#faeeda] text-[#854f0b]',
    4: 'bg-[#fcebeb] text-[#a32d2d]',
};

function severityBadge(rank: number) {
    return SEVERITY_STYLES[rank] ?? SEVERITY_STYLES[1];
}

// ─── Page ─────────────────────────────────────────────────────────────────────

export default function EmployeeRisk({
    employee,
    pause,
    stats,
    incidents,
}: Props) {
    return (
        <div className="mx-auto max-w-5xl px-6 py-8">
            <Head title="Moj status rizika" />

            {/* Employee header */}
            <div className="mb-6 flex items-center gap-5 border-b border-border pb-6">
                <div className="flex size-14 items-center justify-center rounded-full border-2 border-[#f0c5c5] bg-[#fcebeb] text-lg font-bold text-[#a32d2d]">
                    {employee.initials}
                </div>
                <div className="flex-1">
                    <h1 className="text-xl font-bold tracking-tight">
                        {employee.name}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {employee.role}
                        {employee.email && ` · ${employee.email}`}
                    </p>
                </div>
                <div className="flex flex-col items-end gap-1.5">
                    <span className="rounded-full bg-[#fcebeb] px-3.5 py-1.5 text-sm text-[#a32d2d]">
                        ⏸ Na pauzi
                    </span>
                    {pause.from && pause.to && (
                        <span className="text-[11px] text-muted-foreground">
                            od {pause.from} do {pause.to}
                        </span>
                    )}
                </div>
            </div>

            {/* KPI cards */}
            <div className="mb-6 grid gap-3 sm:grid-cols-3">
                <div className="rounded-[10px] border border-[#f0c5c5] bg-[#fff8f8] p-4">
                    <p className="text-[11px] font-bold tracking-wide text-muted-foreground uppercase">
                        Incidenti ({stats.window_days} dana)
                    </p>
                    <p className="pt-1 text-2xl font-bold text-[#a32d2d]">
                        {stats.recent_count}
                    </p>
                    <p className="text-[11px] text-muted-foreground">
                        Prag je {stats.threshold} · prekoračen za{' '}
                        {stats.over_by}
                    </p>
                </div>
                <div className="rounded-[10px] border border-border bg-[#f8f7f2] p-4">
                    <p className="text-[11px] font-bold tracking-wide text-muted-foreground uppercase">
                        Pauza traje
                    </p>
                    <p className="pt-1 text-2xl font-bold">
                        {pause.duration_days} dana
                    </p>
                    <p className="text-[11px] text-muted-foreground">
                        {pause.from} → {pause.to}
                    </p>
                </div>
                <div className="rounded-[10px] border border-border bg-[#f8f7f2] p-4">
                    <p className="text-[11px] font-bold tracking-wide text-muted-foreground uppercase">
                        Ukupno incidenata
                    </p>
                    <p className="pt-1 text-2xl font-bold">
                        {stats.total_count}
                    </p>
                    <p className="text-[11px] text-muted-foreground">
                        Od početka evidencije
                    </p>
                </div>
            </div>

            {/* Pause reason box */}
            <div className="mb-8 rounded-[10px] border border-[#f0c5c5] bg-[#fff8f8] p-5">
                <p className="mb-3 text-sm font-bold text-[#a32d2d]">
                    🤖 Automatski generisan razlog pauze
                </p>
                <ReasonRow label="Razlog" value={pause.reason} border />
                <ReasonRow label="Pauza od" value={pause.from ?? '—'} border />
                <ReasonRow label="Pauza do" value={pause.to ?? '—'} />
            </div>

            {/* Incidents table */}
            <h2 className="mb-3 text-sm font-bold">
                Incidenti u poslednjih {stats.window_days} dana
            </h2>
            <div className="overflow-hidden rounded-[10px] border border-border bg-card">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="bg-muted/40">
                            <th className="px-4 py-2.5 text-left text-[11px] font-bold tracking-wide text-muted-foreground uppercase">
                                Let
                            </th>
                            <th className="px-4 py-2.5 text-left text-[11px] font-bold tracking-wide text-muted-foreground uppercase">
                                Datum i vreme
                            </th>
                            <th className="px-4 py-2.5 text-left text-[11px] font-bold tracking-wide text-muted-foreground uppercase">
                                Tip incidenta
                            </th>
                            <th className="px-4 py-2.5 text-left text-[11px] font-bold tracking-wide text-muted-foreground uppercase">
                                Težina
                            </th>
                            <th className="px-4 py-2.5 text-left text-[11px] font-bold tracking-wide text-muted-foreground uppercase">
                                Ostali učesnici
                            </th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-border">
                        {incidents.length === 0 && (
                            <tr>
                                <td
                                    colSpan={5}
                                    className="px-4 py-8 text-center text-muted-foreground"
                                >
                                    Nema incidenata u posmatranom periodu.
                                </td>
                            </tr>
                        )}
                        {incidents.map((inc) => (
                            <tr key={inc.id} className="hover:bg-muted/30">
                                <td className="px-4 py-3">
                                    <div className="font-bold">
                                        {inc.flight.number} · {inc.flight.route}
                                    </div>
                                    <div className="text-[11px] text-muted-foreground">
                                        {inc.flight.plane}
                                    </div>
                                </td>
                                <td className="px-4 py-3 text-muted-foreground">
                                    {inc.occurred_at} · {inc.occurred_time}
                                </td>
                                <td className="px-4 py-3">
                                    <span className="inline-flex items-center rounded-full bg-[#f1efe8] px-2.5 py-0.5 text-[11px] text-[#5f5e5a]">
                                        {inc.type}
                                    </span>
                                </td>
                                <td className="px-4 py-3">
                                    <span
                                        className={cn(
                                            'inline-flex items-center rounded-md px-2.5 py-1 text-[11px] font-bold',
                                            severityBadge(inc.severity.rank),
                                        )}
                                    >
                                        {inc.severity.name}
                                    </span>
                                </td>
                                <td className="px-4 py-3">
                                    {inc.others.length === 0 ? (
                                        <span className="text-muted-foreground">
                                            —
                                        </span>
                                    ) : (
                                        <div className="flex flex-wrap gap-1.5">
                                            {inc.others.map((name, i) => (
                                                <span
                                                    key={i}
                                                    className="inline-flex items-center rounded-full bg-[#e6f1fb] px-2.5 py-0.5 text-[11px] text-[#185fa5]"
                                                >
                                                    {name}
                                                </span>
                                            ))}
                                        </div>
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

function ReasonRow({
    label,
    value,
    border,
}: {
    label: string;
    value: string;
    border?: boolean;
}) {
    return (
        <div
            className={cn(
                'flex items-start justify-between gap-8 py-2',
                border && 'border-b border-[#f9ecec]',
            )}
        >
            <span className="shrink-0 text-[11px] font-bold tracking-wide text-muted-foreground uppercase">
                {label}
            </span>
            <span className="text-right text-[13px] text-foreground">
                {value}
            </span>
        </div>
    );
}
