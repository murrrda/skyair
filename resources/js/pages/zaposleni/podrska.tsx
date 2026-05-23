import { Head, Link, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type TicketStatus = 'open' | 'in_progress' | 'requires_info' | 'transferred' | 'closed';
type TicketPriority = 'low' | 'medium' | 'high';

type WorkLog = {
    id: number;
    employee_id: number;
    employee_name: string;
    started_at: string | null;
    ended_at: string | null;
    action: string | null;
    note: string | null;
};

type Ticket = {
    id: number;
    number: string;
    category: string | null;
    description: string;
    status: TicketStatus;
    priority: TicketPriority;
    outcome: string | null;
    created_at: string | null;
    closed_at: string | null;
    customer_name: string;
    current_owner: { employee_id: number; name: string; started_at: string | null } | null;
    work_logs: WorkLog[];
};

type Colleague = {
    id: number;
    name: string;
    role: string | null;
    open_tickets: number;
};

type Me = {
    id: number | null;
    employee_id: number | null;
    name: string;
    open_tickets: number;
    capacity: number;
};

type PageProps = {
    tickets: Ticket[];
    colleagues: Colleague[];
    me: Me;
    auth: { user: { first_name?: string; last_name?: string } | null };
    flash?: { success?: string };
};

const priorityMeta: Record<TicketPriority, { label: string; dot: string }> = {
    high: { label: 'Visok', dot: 'bg-[#dc2626]' },
    medium: { label: 'Srednji', dot: 'bg-[#d97706]' },
    low: { label: 'Nizak', dot: 'bg-[#059669]' },
};

const actionLabels: Record<string, string> = {
    taken_over: 'Preuzeo tiket',
    requested_info: 'Zatražene informacije',
    transferred: 'Prosledio kolegi',
    received_transfer: 'Primio prosleđen tiket',
    closed_success: 'Zatvorio (uspešno)',
    closed_partial: 'Zatvorio (parcijalno)',
    closed_fail: 'Zatvorio (neuspešno)',
};

const outcomeLabels: Record<string, { label: string; className: string }> = {
    success: {
        label: 'Uspešno',
        className:
            'bg-[#ecfdf5] text-[#059669] border-[#a7f3d0] dark:bg-[#059669]/15 dark:text-[#6ee7b7] dark:border-[#059669]/40',
    },
    partial: {
        label: 'Parcijalno',
        className:
            'bg-[#fffbeb] text-[#d97706] border-[#fde68a] dark:bg-[#d97706]/15 dark:text-[#fbbf24] dark:border-[#d97706]/40',
    },
    fail: {
        label: 'Neuspešno',
        className:
            'bg-[#fef2f2] text-[#dc2626] border-[#fecaca] dark:bg-[#dc2626]/15 dark:text-[#fca5a5] dark:border-[#dc2626]/40',
    },
};

function formatRelative(iso: string | null): string {
    if (!iso) {
return '—';
}

    const date = new Date(iso);
    const diffMin = Math.floor((Date.now() - date.getTime()) / 60000);

    if (diffMin < 1) {
return 'sada';
}

    if (diffMin < 60) {
return `pre ${diffMin} min`;
}

    const diffH = Math.floor(diffMin / 60);

    if (diffH < 24) {
return `pre ${diffH}h`;
}

    const diffD = Math.floor(diffH / 24);

    if (diffD < 30) {
return `pre ${diffD} dana`;
}

    return date.toLocaleDateString('sr-RS', { day: '2-digit', month: 'short' });
}

function formatDateTime(iso: string | null): string {
    if (!iso) {
return '—';
}

    try {
        return new Date(iso).toLocaleString('sr-RS', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return iso;
    }
}

function durationBetween(start: string | null, end: string | null): string {
    if (!start) {
return '—';
}

    const endMs = end ? new Date(end).getTime() : Date.now();
    const sec = Math.floor((endMs - new Date(start).getTime()) / 1000);

    if (sec < 60) {
return `${sec}s`;
}

    const m = Math.floor(sec / 60);

    if (m < 60) {
return `${m} min`;
}

    const h = Math.floor(m / 60);
    const rem = m % 60;

    return `${h}h ${rem}m`;
}

const columns: Array<{
    key: 'novi' | 'u_resavanju' | 'zahteva_info' | 'zavrseni';
    title: string;
    border: string;
    statuses: TicketStatus[];
}> = [
    { key: 'novi', title: 'Novi', border: 'border-[#1a56db]', statuses: ['open'] },
    { key: 'u_resavanju', title: 'U rešavanju', border: 'border-[#d97706]', statuses: ['in_progress', 'transferred'] },
    { key: 'zahteva_info', title: 'Zahteva info', border: 'border-[#8b5cf6]', statuses: ['requires_info'] },
    { key: 'zavrseni', title: 'Završeni', border: 'border-[#059669]', statuses: ['closed'] },
];

export default function ZaposleniPodrska() {
    const { tickets, colleagues, me, auth, flash } = usePage<PageProps>().props;
    const [selectedId, setSelectedId] = useState<number | null>(tickets[0]?.id ?? null);
    const [transferTarget, setTransferTarget] = useState<string>('');

    const grouped = useMemo(() => {
        const map: Record<string, Ticket[]> = { novi: [], u_resavanju: [], zahteva_info: [], zavrseni: [] };

        for (const t of tickets) {
            const col = columns.find((c) => c.statuses.includes(t.status));

            if (col) {
map[col.key].push(t);
}
        }

        return map;
    }, [tickets]);

    const selected = useMemo(
        () => tickets.find((t) => t.id === selectedId) ?? null,
        [tickets, selectedId],
    );

    const workloadDots = Array.from({ length: me.capacity }, (_, i) => i < me.open_tickets);

    const initials =
        (auth.user?.first_name?.charAt(0) ?? '') + (auth.user?.last_name?.charAt(0) ?? '');

    const post = (url: string, data: Record<string, string | number> = {}) => {
        router.post(url, data, { preserveScroll: true });
    };

    const onTakeOver = (t: Ticket) => post(`/zaposleni/podrska/${t.id}/take`);
    const onRequestInfo = (t: Ticket) => post(`/zaposleni/podrska/${t.id}/request-info`);
    const onTransfer = (t: Ticket) => {
        if (!transferTarget) {
return;
}

        post(`/zaposleni/podrska/${t.id}/transfer`, { to_employee_id: Number(transferTarget) });
        setTransferTarget('');
    };
    const onComplete = (t: Ticket, outcome: 'success' | 'partial' | 'fail') =>
        post(`/zaposleni/podrska/${t.id}/complete`, { outcome });

    return (
        <>
            <Head title="Panel zaposlenog — podrška" />
            <div className="min-h-screen bg-background text-foreground">
                <nav className="flex h-14 items-center border-b border-border bg-card px-8">
                    <Link href="/" className="mr-10 flex items-center gap-2.5">
                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-[#059669] text-sm font-bold text-white">
                            S
                        </div>
                        <span className="text-[15px] font-semibold tracking-tight">SkyAir</span>
                    </Link>
                    <span className="text-[13px] font-medium text-muted-foreground">Panel zaposlenog</span>
                    <div className="ml-auto flex items-center gap-3">
                        {auth.user && (
                            <div className="flex items-center gap-2 rounded-full border border-border bg-muted py-1 pr-3 pl-1">
                                <div className="flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-[#059669] to-[#047857] text-[11px] font-semibold text-white">
                                    {initials || 'Z'}
                                </div>
                                <span className="text-[13px] font-medium">
                                    {auth.user.first_name} {auth.user.last_name?.charAt(0)}.
                                </span>
                            </div>
                        )}
                    </div>
                </nav>

                <div className="flex flex-col gap-6 px-8 py-7">
                    {flash?.success && (
                        <div className="rounded-lg border border-[#a7f3d0] bg-[#ecfdf5] px-4 py-2.5 text-sm text-[#065f46] dark:border-[#059669]/40 dark:bg-[#059669]/10 dark:text-[#6ee7b7]">
                            {flash.success}
                        </div>
                    )}

                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight">Kanban tabla</h1>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Tiketi zaposlenog i tok obrade — kliknite na karticu za detalje
                            </p>
                        </div>
                        <div className="flex items-center gap-2 rounded-lg border border-border bg-card px-3.5 py-2 text-[13px]">
                            <span>Opterećenje:</span>
                            <div className="flex gap-1">
                                {workloadDots.map((on, i) => (
                                    <span
                                        key={i}
                                        className={
                                            'h-2 w-2 rounded-full ' +
                                            (on ? 'bg-[#d97706] dark:bg-[#fbbf24]' : 'bg-border')
                                        }
                                    />
                                ))}
                            </div>
                            <b>
                                {me.open_tickets}/{me.capacity}
                            </b>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-3.5 md:grid-cols-2 xl:grid-cols-4">
                        {columns.map((col) => {
                            const items = grouped[col.key];

                            return (
                                <div
                                    key={col.key}
                                    className="min-h-[380px] rounded-xl border border-border bg-muted/40 p-3.5"
                                >
                                    <div
                                        className={
                                            'mb-3 flex items-center justify-between border-b-2 pb-2.5 ' +
                                            col.border
                                        }
                                    >
                                        <span className="text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                            {col.title}
                                        </span>
                                        <span className="rounded-full border border-border bg-card px-2 py-0.5 text-[11px] font-semibold text-muted-foreground">
                                            {items.length}
                                        </span>
                                    </div>
                                    {items.length === 0 ? (
                                        <p className="px-2 py-6 text-center text-xs text-muted-foreground">
                                            Nema tiketa
                                        </p>
                                    ) : (
                                        items.map((t) => (
                                            <KanbanCard
                                                key={t.id}
                                                ticket={t}
                                                selected={t.id === selectedId}
                                                onSelect={() => setSelectedId(t.id)}
                                            />
                                        ))
                                    )}
                                </div>
                            );
                        })}
                    </div>

                    {selected ? (
                        <div className="grid grid-cols-1 gap-5 lg:grid-cols-2">
                            <TicketDetailsCard
                                ticket={selected}
                                me={me}
                                onTakeOver={() => onTakeOver(selected)}
                                onRequestInfo={() => onRequestInfo(selected)}
                                onComplete={(outcome) => onComplete(selected, outcome)}
                            />
                            <ColleaguesCard
                                colleagues={colleagues}
                                me={me}
                                ticket={selected}
                                transferTarget={transferTarget}
                                onTransferTargetChange={setTransferTarget}
                                onTransfer={() => onTransfer(selected)}
                            />
                            <div className="lg:col-span-2">
                                <WorkLogCard ticket={selected} />
                            </div>
                        </div>
                    ) : (
                        <div className="rounded-xl border border-dashed border-border bg-card px-6 py-12 text-center text-sm text-muted-foreground">
                            Kliknite na karticu u kanbanu za prikaz detalja.
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}

function KanbanCard({
    ticket,
    selected,
    onSelect,
}: {
    ticket: Ticket;
    selected: boolean;
    onSelect: () => void;
}) {
    const prio = priorityMeta[ticket.priority] ?? priorityMeta.medium;
    const isClosed = ticket.status === 'closed';
    const outcomeTag = ticket.outcome ? outcomeLabels[ticket.outcome] : null;

    return (
        <button
            type="button"
            onClick={onSelect}
            className={
                'mb-2 block w-full rounded-lg border bg-card p-3 text-left shadow-sm transition ' +
                (selected
                    ? 'border-[#1a56db] ring-2 ring-[#1a56db]/30 dark:border-[#7eb1f5] dark:ring-[#7eb1f5]/30'
                    : 'border-border hover:border-[#1a56db] dark:hover:border-[#7eb1f5]') +
                (isClosed ? ' opacity-70' : '')
            }
        >
            <div className="mb-1.5 flex items-center justify-between">
                <span className="font-mono text-[11px] font-medium text-[#1a56db] dark:text-[#7eb1f5]">
                    #{ticket.number}
                </span>
                {!isClosed && <span className={'h-2 w-2 rounded-full ' + prio.dot} />}
            </div>
            <div className="mb-1 line-clamp-2 text-[13px] leading-tight font-medium">
                {ticket.description}
            </div>
            <div className="flex items-center gap-2 text-[11px] text-muted-foreground">
                {ticket.category && (
                    <span className="rounded border border-border bg-muted px-1.5 py-0.5 text-muted-foreground">
                        {ticket.category}
                    </span>
                )}
                {outcomeTag && (
                    <span className={'rounded border px-1.5 py-0.5 ' + outcomeTag.className}>
                        {outcomeTag.label}
                    </span>
                )}
            </div>
            <div className="mt-2 flex items-center justify-between border-t border-border pt-2 text-[11px] text-muted-foreground">
                <span>{ticket.current_owner ? ticket.current_owner.name : ticket.customer_name}</span>
                <span>{formatRelative(ticket.created_at)}</span>
            </div>
        </button>
    );
}

function TicketDetailsCard({
    ticket,
    me,
    onTakeOver,
    onRequestInfo,
    onComplete,
}: {
    ticket: Ticket;
    me: Me;
    onTakeOver: () => void;
    onRequestInfo: () => void;
    onComplete: (outcome: 'success' | 'partial' | 'fail') => void;
}) {
    const prio = priorityMeta[ticket.priority] ?? priorityMeta.medium;
    const isClosed = ticket.status === 'closed';
    const owner = ticket.current_owner;
    const ownedByMe = !!owner && owner.employee_id === me.employee_id;

    return (
        <div className="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
            <div className="flex items-center justify-between border-b border-border px-5 py-4">
                <h3 className="text-[15px] font-semibold tracking-tight">
                    Detalji — #{ticket.number}
                </h3>
                {owner && (
                    <span className="text-xs text-muted-foreground">
                        Trenutno: <b className="text-foreground">{owner.name}</b>
                    </span>
                )}
            </div>
            <div className="px-5 py-4">
                <DetailRow label="korisnik">
                    <span className="font-medium">{ticket.customer_name}</span>
                </DetailRow>
                <DetailRow label="kategorija">
                    <span className="font-medium">{ticket.category ?? '—'}</span>
                </DetailRow>
                <DetailRow label="opis_problema">
                    <span className="max-w-[280px] text-right font-medium">
                        {ticket.description}
                    </span>
                </DetailRow>
                <DetailRow label="prioritet">
                    <span className="inline-flex items-center gap-1.5 font-medium">
                        <span className={'h-2 w-2 rounded-full ' + prio.dot} />
                        {prio.label}
                    </span>
                </DetailRow>
                <DetailRow label="datum_kreiranja">
                    <span className="font-mono text-xs">{formatDateTime(ticket.created_at)}</span>
                </DetailRow>
                {ticket.closed_at && (
                    <DetailRow label="datum_zatvaranja">
                        <span className="font-mono text-xs">{formatDateTime(ticket.closed_at)}</span>
                    </DetailRow>
                )}
                {ticket.outcome && (
                    <DetailRow label="ishod">
                        <span
                            className={
                                'rounded border px-2 py-0.5 text-xs font-medium ' +
                                (outcomeLabels[ticket.outcome]?.className ?? '')
                            }
                        >
                            {outcomeLabels[ticket.outcome]?.label ?? ticket.outcome}
                        </span>
                    </DetailRow>
                )}

                {!isClosed && (
                    <>
                        <div className="mt-5 mb-2 text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
                            Akcije
                        </div>
                        <div className="flex flex-wrap gap-2">
                            {!ownedByMe ? (
                                <Button
                                    size="sm"
                                    onClick={onTakeOver}
                                    className="bg-[#1a56db] text-white hover:bg-[#1648b8]"
                                >
                                    Preuzmi
                                </Button>
                            ) : (
                                <span className="rounded-md border border-[#a7f3d0] bg-[#ecfdf5] px-2.5 py-1 text-xs font-medium text-[#059669] dark:border-[#059669]/40 dark:bg-[#059669]/15 dark:text-[#6ee7b7]">
                                    Radite na ovom tiketu
                                </span>
                            )}
                            <Button size="sm" variant="outline" onClick={onRequestInfo}>
                                Zatraži info
                            </Button>
                        </div>

                        <div className="mt-5 mb-2 text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
                            Završi tiket
                        </div>
                        <div className="flex gap-2">
                            {(['success', 'partial', 'fail'] as const).map((o) => {
                                const meta = outcomeLabels[o];

                                return (
                                    <button
                                        key={o}
                                        type="button"
                                        onClick={() => onComplete(o)}
                                        className={
                                            'flex-1 rounded-lg border px-2 py-2.5 text-xs font-medium transition hover:opacity-80 ' +
                                            meta.className
                                        }
                                    >
                                        {meta.label}
                                    </button>
                                );
                            })}
                        </div>
                    </>
                )}
            </div>
        </div>
    );
}

function ColleaguesCard({
    colleagues,
    me,
    ticket,
    transferTarget,
    onTransferTargetChange,
    onTransfer,
}: {
    colleagues: Colleague[];
    me: Me;
    ticket: Ticket;
    transferTarget: string;
    onTransferTargetChange: (v: string) => void;
    onTransfer: () => void;
}) {
    const others = colleagues.filter((c) => c.id !== me.employee_id);

    return (
        <div className="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
            <div className="flex items-center justify-between border-b border-border px-5 py-4">
                <h3 className="text-[15px] font-semibold tracking-tight">
                    Kolege — prosleđivanje
                </h3>
                <span className="text-xs text-muted-foreground">Po opterećenju</span>
            </div>
            <div className="px-5 py-4">
                {colleagues.length === 0 ? (
                    <p className="py-6 text-center text-sm text-muted-foreground">
                        Nema drugih zaposlenih.
                    </p>
                ) : (
                    <div className="space-y-1">
                        {colleagues.map((c) => {
                            const isMe = c.id === me.employee_id;
                            const pct = Math.min(100, (c.open_tickets / 5) * 100);
                            const bar =
                                c.open_tickets >= 4
                                    ? 'bg-[#dc2626] dark:bg-[#fca5a5]'
                                    : c.open_tickets >= 3
                                      ? 'bg-[#d97706] dark:bg-[#fbbf24]'
                                      : 'bg-[#059669] dark:bg-[#6ee7b7]';

                            return (
                                <div
                                    key={c.id}
                                    className="flex items-center gap-3 border-b border-border py-2.5 last:border-0"
                                >
                                    <div className="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-[#1a56db] to-[#1648b8] text-[11px] font-semibold text-white">
                                        {c.name
                                            .split(' ')
                                            .map((p) => p.charAt(0))
                                            .slice(0, 2)
                                            .join('')}
                                    </div>
                                    <div className="flex-1">
                                        <div className="text-[13px] font-medium">
                                            {c.name} {isMe && '(ti)'}
                                        </div>
                                        <div className="text-[11px] text-muted-foreground">
                                            {c.role ?? 'agent podrške'}
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-2 text-[11px] text-muted-foreground">
                                        <b className="text-foreground">{c.open_tickets}</b>
                                        <span>tik.</span>
                                        <div className="h-1 w-14 overflow-hidden rounded-full bg-border">
                                            <div className={'h-full ' + bar} style={{ width: pct + '%' }} />
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}

                {ticket.status !== 'closed' && others.length > 0 && (
                    <div className="mt-4 flex items-center gap-2 border-t border-border pt-4">
                        <Select value={transferTarget} onValueChange={onTransferTargetChange}>
                            <SelectTrigger className="flex-1">
                                <SelectValue placeholder="Izaberi kolegu…" />
                            </SelectTrigger>
                            <SelectContent>
                                {others.map((c) => (
                                    <SelectItem key={c.id} value={String(c.id)}>
                                        {c.name} ({c.open_tickets} tiketa)
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Button
                            size="sm"
                            disabled={!transferTarget}
                            onClick={onTransfer}
                            className="bg-[#1a56db] text-white hover:bg-[#1648b8]"
                        >
                            Prosledi
                        </Button>
                    </div>
                )}
            </div>
        </div>
    );
}

function WorkLogCard({ ticket }: { ticket: Ticket }) {
    return (
        <div className="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
            <div className="flex items-center justify-between border-b border-border px-5 py-4">
                <h3 className="text-[15px] font-semibold tracking-tight">
                    Logger — ko radi na tiketu #{ticket.number}
                </h3>
                <span className="text-xs text-muted-foreground">
                    {ticket.work_logs.length} {ticket.work_logs.length === 1 ? 'sesija' : 'sesija'}
                </span>
            </div>
            <div className="px-5 py-4">
                {ticket.work_logs.length === 0 ? (
                    <p className="py-4 text-center text-sm text-muted-foreground">
                        Niko još nije preuzeo ovaj tiket.
                    </p>
                ) : (
                    <table className="w-full text-[13px]">
                        <thead>
                            <tr className="bg-muted/40">
                                <Th>Zaposleni</Th>
                                <Th>Akcija</Th>
                                <Th>Početak</Th>
                                <Th>Kraj</Th>
                                <Th>Trajanje</Th>
                            </tr>
                        </thead>
                        <tbody>
                            {ticket.work_logs.map((log) => {
                                const open = !log.ended_at;
                                const action = log.action
                                    ? actionLabels[log.action] ?? log.action
                                    : 'Preuzeo';

                                return (
                                    <tr key={log.id} className="border-b border-border last:border-0">
                                        <td className="px-3 py-2.5 font-medium">{log.employee_name}</td>
                                        <td className="px-3 py-2.5 text-muted-foreground">{action}</td>
                                        <td className="px-3 py-2.5 font-mono text-xs text-muted-foreground">
                                            {formatDateTime(log.started_at)}
                                        </td>
                                        <td className="px-3 py-2.5 font-mono text-xs text-muted-foreground">
                                            {log.ended_at ? (
                                                formatDateTime(log.ended_at)
                                            ) : (
                                                <span className="rounded-full bg-[#ecfdf5] px-2 py-0.5 text-[11px] font-medium text-[#059669] dark:bg-[#059669]/20 dark:text-[#6ee7b7]">
                                                    u toku
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-3 py-2.5 font-mono text-xs text-muted-foreground">
                                            {durationBetween(log.started_at, log.ended_at)}
                                            {open && (
                                                <span className="ml-1 text-muted-foreground/70">(aktivno)</span>
                                            )}
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                )}
            </div>
        </div>
    );
}

function Th({ children }: { children: React.ReactNode }) {
    return (
        <th className="border-b border-border px-3 py-2 text-left text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
            {children}
        </th>
    );
}

function DetailRow({
    label,
    children,
}: {
    label: string;
    children: React.ReactNode;
}) {
    return (
        <div className="flex items-start justify-between border-b border-border py-2 text-[13px] last:border-b-0">
            <span className="text-muted-foreground">{label}</span>
            <div className="text-right">{children}</div>
        </div>
    );
}