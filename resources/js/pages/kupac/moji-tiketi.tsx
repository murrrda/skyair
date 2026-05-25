import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import InputError from '@/components/input-error';
import { NotificationBell } from '@/components/notification-bell';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
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

type AgentSummary = {
    employee_id: number;
    name: string;
};

type RatingContext = {
    has_additional_contact: boolean;
    is_partial_resolution: boolean;
};

type AgentRating = {
    employee_id: number;
    rating: number;
};

type TicketRating = {
    resolution_speed: number;
    communication_quality: number | null;
    degree_of_resolution: number | null;
    created_at: string | null;
    agents: AgentRating[];
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
    work_logs: WorkLog[];
    agents: AgentSummary[];
    rating_context: RatingContext;
    rating: TicketRating | null;
};

type Category = { id: number; name: string };

type PageProps = {
    tickets: Ticket[];
    categories: Category[];
    auth: { user: { first_name?: string; last_name?: string } | null };
    flash?: { success?: string };
};

const statusMeta: Record<TicketStatus, { label: string; className: string }> = {
    open: { label: 'Otvoren', className: 'bg-[#e8f0fe] text-[#1a56db] dark:bg-[#1a56db]/20 dark:text-[#7eb1f5]' },
    in_progress: { label: 'U rešavanju', className: 'bg-[#fffbeb] text-[#d97706] dark:bg-[#d97706]/20 dark:text-[#fbbf24]' },
    requires_info: { label: 'Zahteva info', className: 'bg-[#ede9fe] text-[#7c3aed] dark:bg-[#7c3aed]/20 dark:text-[#c4b5fd]' },
    transferred: { label: 'Prosleđen', className: 'bg-[#e0f2fe] text-[#0369a1] dark:bg-[#0369a1]/20 dark:text-[#7dd3fc]' },
    closed: { label: 'Završen', className: 'bg-[#ecfdf5] text-[#059669] dark:bg-[#059669]/20 dark:text-[#6ee7b7]' },
};

const priorityMeta: Record<TicketPriority, { label: string; className: string }> = {
    high: { label: 'Visok', className: 'bg-[#fef2f2] text-[#dc2626] dark:bg-[#dc2626]/20 dark:text-[#fca5a5]' },
    medium: { label: 'Srednji', className: 'bg-[#fffbeb] text-[#d97706] dark:bg-[#d97706]/20 dark:text-[#fbbf24]' },
    low: { label: 'Nizak', className: 'bg-[#ecfdf5] text-[#059669] dark:bg-[#059669]/20 dark:text-[#6ee7b7]' },
};

const filterOptions: Array<{ key: 'all' | TicketStatus; label: string }> = [
    { key: 'all', label: 'Svi' },
    { key: 'open', label: 'Otvoreni' },
    { key: 'in_progress', label: 'U rešavanju' },
    { key: 'requires_info', label: 'Zahteva info' },
    { key: 'closed', label: 'Završeni' },
];

const actionLabels: Record<string, string> = {
    taken_over: 'Preuzeo tiket',
    requested_info: 'Zatražene informacije',
    transferred: 'Prosledio kolegi',
    received_transfer: 'Primio prosleđen tiket',
    closed_success: 'Zatvorio (uspešno)',
    closed_partial: 'Zatvorio (parcijalno)',
    closed_fail: 'Zatvorio (neuspešno)',
};

function formatDate(iso: string | null): string {
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

function formatShortDate(iso: string | null): string {
    if (!iso) {
return '—';
}

    try {
        return new Date(iso).toLocaleDateString('sr-RS', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        });
    } catch {
        return iso;
    }
}

export default function MojiTiketi() {
    const { tickets, categories, auth, flash } = usePage<PageProps>().props;
    const [filter, setFilter] = useState<'all' | TicketStatus>('all');
    const [selectedId, setSelectedId] = useState<number | null>(tickets[0]?.id ?? null);
    const [createOpen, setCreateOpen] = useState(false);

    const filtered = useMemo(
        () => (filter === 'all' ? tickets : tickets.filter((t) => t.status === filter)),
        [tickets, filter],
    );

    const selected = useMemo(
        () => tickets.find((t) => t.id === selectedId) ?? null,
        [tickets, selectedId],
    );

    const stats = useMemo(() => {
        const total = tickets.length;
        const inProgress = tickets.filter((t) => t.status === 'in_progress').length;
        const resolved = tickets.filter((t) => t.status === 'closed').length;
        const awaiting = tickets.filter((t) => t.status === 'requires_info').length;

        return { total, inProgress, resolved, awaiting };
    }, [tickets]);

    const initials =
        (auth.user?.first_name?.charAt(0) ?? '') + (auth.user?.last_name?.charAt(0) ?? '');

    return (
        <>
            <Head title="Moji tiketi" />
            <div className="min-h-screen bg-background text-foreground">
                <nav className="flex h-14 items-center border-b border-border bg-card px-8">
                    <Link href="/" className="mr-10 flex items-center gap-2.5">
                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-[#1a56db] text-sm font-bold text-white">
                            S
                        </div>
                        <span className="text-[15px] font-semibold tracking-tight">SkyAir</span>
                    </Link>
                    <span className="text-[13px] font-medium text-muted-foreground">Korisnički portal</span>
                    <div className="ml-auto flex items-center gap-3">
                        <Link
                            href="/kupac/pretraga-letova"
                            className="text-[13px] text-muted-foreground hover:text-[#1a56db] dark:hover:text-[#7eb1f5]"
                        >
                            Letovi
                        </Link>
                        <Link
                            href="/zaposleni/podrska"
                            className="text-[13px] text-muted-foreground hover:text-[#1a56db] dark:hover:text-[#7eb1f5]"
                        >
                            Panel zaposlenog
                        </Link>
                        {auth.user && <NotificationBell />}
                        {auth.user && (
                            <div className="flex items-center gap-2 rounded-full border border-border bg-muted py-1 pr-3 pl-1">
                                <div className="flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-[#1a56db] to-[#3b7ddd] text-[11px] font-semibold text-white">
                                    {initials || 'K'}
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
                            <h1 className="text-2xl font-bold tracking-tight">Moji tiketi</h1>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Pregled prijava problema i praćenje statusa
                            </p>
                        </div>
                        <Button
                            onClick={() => setCreateOpen(true)}
                            className="bg-[#1a56db] text-white hover:bg-[#1648b8]"
                        >
                            + Prijavi problem
                        </Button>
                    </div>

                    <StatsRow stats={stats} />

                    <div className="grid grid-cols-1 gap-5 lg:grid-cols-[1fr_380px]">
                        <div className="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
                            <div className="flex items-center justify-between border-b border-border px-5 py-4">
                                <h2 className="text-[15px] font-semibold tracking-tight">Aktivni tiketi</h2>
                                <div className="flex gap-1.5">
                                    {filterOptions.map((opt) => (
                                        <button
                                            key={opt.key}
                                            type="button"
                                            onClick={() => setFilter(opt.key)}
                                            className={
                                                'rounded-full border px-3.5 py-1 text-xs font-medium transition ' +
                                                (filter === opt.key
                                                    ? 'border-foreground bg-foreground text-background'
                                                    : 'border-border bg-card text-muted-foreground hover:text-foreground')
                                            }
                                        >
                                            {opt.label}
                                        </button>
                                    ))}
                                </div>
                            </div>
                            {filtered.length === 0 ? (
                                <div className="px-6 py-16 text-center">
                                    <p className="text-sm font-medium text-foreground">
                                        Nema tiketa za ovaj filter.
                                    </p>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        Kreirajte novi klikom na "Prijavi problem".
                                    </p>
                                </div>
                            ) : (
                                <table className="w-full">
                                    <thead>
                                        <tr className="bg-muted/40">
                                            <Th>ID</Th>
                                            <Th>Kategorija</Th>
                                            <Th>Status</Th>
                                            <Th>Prioritet</Th>
                                            <Th>Datum</Th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {filtered.map((t) => {
                                            const stat = statusMeta[t.status];
                                            const prio = priorityMeta[t.priority] ?? priorityMeta.medium;
                                            const isSelected = t.id === selectedId;

                                            return (
                                                <tr
                                                    key={t.id}
                                                    onClick={() => setSelectedId(t.id)}
                                                    className={
                                                        'cursor-pointer border-b border-border transition ' +
                                                        (isSelected
                                                            ? 'bg-accent'
                                                            : 'hover:bg-muted/40')
                                                    }
                                                >
                                                    <Td>
                                                        <span className="font-mono text-[12px] font-medium text-[#1a56db] dark:text-[#7eb1f5]">
                                                            #{t.number}
                                                        </span>
                                                    </Td>
                                                    <Td>
                                                        <Tag>{t.category ?? '—'}</Tag>
                                                    </Td>
                                                    <Td>
                                                        <PillBadge className={stat.className}>
                                                            <Dot />
                                                            {stat.label}
                                                        </PillBadge>
                                                    </Td>
                                                    <Td>
                                                        <span
                                                            className={
                                                                'rounded px-2 py-0.5 text-xs font-medium ' +
                                                                prio.className
                                                            }
                                                        >
                                                            {prio.label}
                                                        </span>
                                                    </Td>
                                                    <Td className="text-muted-foreground">
                                                        {formatShortDate(t.created_at)}
                                                    </Td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            )}
                        </div>

                        <DetailPanel ticket={selected} />
                    </div>

                    <div className="rounded-xl border border-dashed border-[#1a56db]/30 bg-[#1a56db]/5 px-5 py-4 text-sm text-muted-foreground dark:border-[#7eb1f5]/30 dark:bg-[#7eb1f5]/5">
                        <span className="font-semibold text-[#1a56db] dark:text-[#7eb1f5]">U izradi:</span>{' '}
                        NLP validacija prijave.
                    </div>
                </div>
            </div>

            <CreateTicketDialog
                open={createOpen}
                onOpenChange={setCreateOpen}
                categories={categories}
            />
        </>
    );
}

function Th({ children }: { children: React.ReactNode }) {
    return (
        <th className="border-b border-border px-4 py-2.5 text-left text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
            {children}
        </th>
    );
}

function Td({ children, className = '' }: { children: React.ReactNode; className?: string }) {
    return <td className={'px-4 py-3 text-[13px] ' + className}>{children}</td>;
}

function Tag({ children }: { children: React.ReactNode }) {
    return (
        <span className="rounded border border-border bg-muted px-2 py-0.5 text-[11px] text-muted-foreground">
            {children}
        </span>
    );
}

function Dot() {
    return <span className="h-1.5 w-1.5 rounded-full bg-current" />;
}

function PillBadge({
    children,
    className = '',
}: {
    children: React.ReactNode;
    className?: string;
}) {
    return (
        <span
            className={
                'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[12px] font-medium ' +
                className
            }
        >
            {children}
        </span>
    );
}

function StatsRow({
    stats,
}: {
    stats: { total: number; inProgress: number; resolved: number; awaiting: number };
}) {
    const items = [
        { label: 'Ukupno tiketa', value: stats.total, valueClass: '', sub: 'U izradi' },
        {
            label: 'U obradi',
            value: stats.inProgress,
            valueClass: 'text-[#d97706] dark:text-[#fbbf24]',
            sub: 'aktivni',
        },
        {
            label: 'Rešeni',
            value: stats.resolved,
            valueClass: 'text-[#059669] dark:text-[#6ee7b7]',
            sub: 'završeni',
        },
        {
            label: 'Čeka informacije',
            value: stats.awaiting,
            valueClass: 'text-[#1a56db] dark:text-[#7eb1f5]',
            sub: 'potrebna akcija',
        },
    ];

    return (
        <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
            {items.map((it) => (
                <div
                    key={it.label}
                    className="rounded-xl border border-border bg-card p-5 shadow-sm"
                >
                    <div className="text-xs font-medium text-muted-foreground">{it.label}</div>
                    <div className={'mt-1.5 text-2xl font-bold tracking-tight ' + it.valueClass}>
                        {it.value}
                    </div>
                    <div className="mt-1 text-xs text-muted-foreground">{it.sub}</div>
                </div>
            ))}
        </div>
    );
}

function DetailPanel({ ticket }: { ticket: Ticket | null }) {
    if (!ticket) {
        return (
            <div className="flex h-full min-h-[280px] flex-col items-center justify-center rounded-xl border border-dashed border-border bg-card px-6 text-center text-sm text-muted-foreground">
                <p className="font-medium text-foreground">Izaberite tiket</p>
                <p className="mt-1 text-xs">Klikom na red tabele otvorićete detalje.</p>
            </div>
        );
    }

    const stat = statusMeta[ticket.status];
    const prio = priorityMeta[ticket.priority] ?? priorityMeta.medium;

    return (
        <div className="flex flex-col gap-4">
            <div className="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
                <div className="flex items-center justify-between border-b border-border px-5 py-4">
                    <h3 className="text-[15px] font-semibold tracking-tight">
                        Tiket #{ticket.number}
                    </h3>
                </div>
                <div className="px-5 py-4">
                    <DetailRow label="opis_problema">
                        <span className="max-w-[220px] text-right">{ticket.description}</span>
                    </DetailRow>
                    <DetailRow label="kategorija">
                        <span className="font-medium">{ticket.category ?? '—'}</span>
                    </DetailRow>
                    <DetailRow label="prioritet">
                        <span className={'rounded px-2 py-0.5 text-xs font-medium ' + prio.className}>
                            {prio.label}
                        </span>
                    </DetailRow>
                    <DetailRow label="status">
                        <PillBadge className={stat.className}>
                            <Dot />
                            {stat.label}
                        </PillBadge>
                    </DetailRow>
                    <DetailRow label="datum_kreiranja">
                        <span className="font-mono text-xs font-medium">
                            {formatShortDate(ticket.created_at)}
                        </span>
                    </DetailRow>
                    {ticket.closed_at && (
                        <DetailRow label="datum_zatvaranja">
                            <span className="font-mono text-xs font-medium">
                                {formatShortDate(ticket.closed_at)}
                            </span>
                        </DetailRow>
                    )}

                    <div className="mt-5">
                        <div className="mb-3 text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
                            Tok obrade
                        </div>
                        <Timeline ticket={ticket} />
                    </div>
                </div>
            </div>

            {ticket.status === 'closed' && <RatingPanel ticket={ticket} />}
        </div>
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

function Timeline({ ticket }: { ticket: Ticket }) {
    const events: Array<{ time: string | null; text: React.ReactNode; done: boolean }> = [];

    events.push({
        time: ticket.created_at,
        text: (
            <span>
                <b className="font-medium text-foreground">Tiket kreiran</b>
            </span>
        ),
        done: true,
    });

    ticket.work_logs.forEach((log) => {
        const startedLabel = actionLabels[log.action ?? ''] ?? 'Preuzeo tiket';

        events.push({
            time: log.started_at,
            text: (
                <span>
                    <b className="font-medium text-foreground">{startedLabel}</b>:{' '}
                    {log.employee_name}
                </span>
            ),
            done: true,
        });

        if (log.ended_at) {
            const endedLabel = actionLabels[log.action ?? ''] ?? 'Završio rad';

            events.push({
                time: log.ended_at,
                text: (
                    <span>
                        <b className="font-medium text-foreground">{endedLabel}</b>:{' '}
                        {log.employee_name}
                    </span>
                ),
                done: true,
            });
        }
    });

    if (ticket.status !== 'closed') {
        events.push({
            time: null,
            text: <b className="font-medium text-foreground">Obrada u toku…</b>,
            done: false,
        });
    }

    return (
        <div className="relative pl-5">
            <div className="absolute top-1 bottom-1 left-[5px] w-0.5 bg-border" />
            {events.map((ev, i) => (
                <div key={i} className="relative mb-4 last:mb-0">
                    <div
                        className={
                            'absolute -left-[18px] top-1 h-2.5 w-2.5 rounded-full border-2 border-[#1a56db] dark:border-[#7eb1f5] ' +
                            (ev.done ? 'bg-[#1a56db] dark:bg-[#7eb1f5]' : 'bg-card')
                        }
                    />
                    <div className="text-[11px] text-muted-foreground">{formatDate(ev.time)}</div>
                    <div className="text-[13px] text-muted-foreground">{ev.text}</div>
                </div>
            ))}
        </div>
    );
}

function CreateTicketDialog({
    open,
    onOpenChange,
    categories,
}: {
    open: boolean;
    onOpenChange: (v: boolean) => void;
    categories: Category[];
}) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        category_id: string;
        description: string;
        priority: TicketPriority;
    }>({
        category_id: '',
        description: '',
        priority: 'medium',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/support-tickets', {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        });
    };

    return (
        <Dialog
            open={open}
            onOpenChange={(v) => {
                if (!v) {
reset();
}

                onOpenChange(v);
            }}
        >
            <DialogContent className="sm:max-w-[520px]">
                <DialogHeader>
                    <DialogTitle>Prijavi problem</DialogTitle>
                    <DialogDescription>Popunite kategoriju i opišite vaš problem</DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-1.5">
                        <Label>
                            Kategorija problema <span className="text-[#dc2626]">*</span>
                        </Label>
                        <Select
                            value={data.category_id}
                            onValueChange={(v) => setData('category_id', v)}
                        >
                            <SelectTrigger className="w-full">
                                <SelectValue placeholder="Izaberite kategoriju…" />
                            </SelectTrigger>
                            <SelectContent>
                                {categories.map((c) => (
                                    <SelectItem key={c.id} value={String(c.id)}>
                                        {c.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.category_id} />
                    </div>

                    <div className="space-y-1.5">
                        <Label>Prioritet</Label>
                        <Select
                            value={data.priority}
                            onValueChange={(v) => setData('priority', v as TicketPriority)}
                        >
                            <SelectTrigger className="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="low">Nizak</SelectItem>
                                <SelectItem value="medium">Srednji</SelectItem>
                                <SelectItem value="high">Visok</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={errors.priority} />
                    </div>

                    <div className="space-y-1.5">
                        <Label>
                            Opis problema <span className="text-[#dc2626]">*</span>
                        </Label>
                        <textarea
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            placeholder="Opišite vaš problem što detaljnije — broj leta, broj prtljaga, datum putovanja…"
                            className="min-h-[130px] w-full rounded-lg border border-border bg-background px-3.5 py-2.5 text-sm leading-relaxed text-foreground placeholder:text-muted-foreground focus:border-[#1a56db] focus:outline-none dark:focus:border-[#7eb1f5]"
                        />
                        <p className="text-xs text-muted-foreground">
                            <span className="font-medium text-[#1a56db] dark:text-[#7eb1f5]">U izradi:</span>{' '}
                            NLP će automatski izvući obavezna polja iz opisa.
                        </p>
                        <InputError message={errors.description} />
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                        >
                            Otkaži
                        </Button>
                        <Button
                            type="submit"
                            disabled={processing}
                            className="bg-[#1a56db] text-white hover:bg-[#1648b8]"
                        >
                            Pošalji prijavu
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function StarRating({
    value,
    onChange,
    readOnly = false,
}: {
    value: number;
    onChange?: (v: number) => void;
    readOnly?: boolean;
}) {
    const [hover, setHover] = useState(0);
    const display = hover || value;

    return (
        <div className="inline-flex items-center gap-1">
            {[1, 2, 3, 4, 5].map((n) => {
                const active = n <= display;

                return (
                    <button
                        key={n}
                        type="button"
                        disabled={readOnly}
                        onMouseEnter={() => !readOnly && setHover(n)}
                        onMouseLeave={() => !readOnly && setHover(0)}
                        onClick={() => !readOnly && onChange?.(n)}
                        aria-label={`${n} ${n === 1 ? 'zvezdica' : 'zvezdica'}`}
                        className={
                            'text-lg leading-none transition ' +
                            (readOnly ? 'cursor-default ' : 'cursor-pointer ') +
                            (active
                                ? 'text-[#f59e0b]'
                                : 'text-muted-foreground hover:text-[#f59e0b]')
                        }
                    >
                        {active ? '★' : '☆'}
                    </button>
                );
            })}
            {value > 0 && (
                <span className="ml-1 text-xs text-muted-foreground">{value}/5</span>
            )}
        </div>
    );
}

type RatingFormState = {
    resolution_speed: number;
    communication_quality: number;
    degree_of_resolution: number;
    agents: Record<number, number>;
};

function RatingPanel({ ticket }: { ticket: Ticket }) {
    const showCommunication = ticket.rating_context.has_additional_contact;
    const showDegree = ticket.rating_context.is_partial_resolution;

    if (ticket.rating) {
        return <SubmittedRating ticket={ticket} showCommunication={showCommunication} showDegree={showDegree} />;
    }

    if (ticket.agents.length === 0) {
        return (
            <div className="overflow-hidden rounded-xl border border-dashed border-border bg-card px-5 py-4 shadow-sm">
                <div className="text-[13px] font-semibold">Recenzija tiketa</div>
                <p className="mt-1 text-xs text-muted-foreground">
                    Nema agenata koji su radili na ovom tiketu.
                </p>
            </div>
        );
    }

    return (
        <RatingForm
            ticket={ticket}
            showCommunication={showCommunication}
            showDegree={showDegree}
        />
    );
}

function SubmittedRating({
    ticket,
    showCommunication,
    showDegree,
}: {
    ticket: Ticket;
    showCommunication: boolean;
    showDegree: boolean;
}) {
    const r = ticket.rating!;
    const agentsById = new Map(ticket.agents.map((a) => [a.employee_id, a.name]));

    return (
        <div className="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
            <div className="flex items-center justify-between border-b border-border px-5 py-4">
                <h3 className="text-[15px] font-semibold tracking-tight">Vaša recenzija</h3>
                <span className="text-[11px] text-muted-foreground">
                    {formatShortDate(r.created_at)}
                </span>
            </div>
            <div className="space-y-3 px-5 py-4 text-[13px]">
                <div>
                    <div className="mb-1 text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
                        Zadovoljstvo po agentu
                    </div>
                    {r.agents.map((ar) => (
                        <div key={ar.employee_id} className="flex items-center justify-between py-1">
                            <span className="text-muted-foreground">
                                {agentsById.get(ar.employee_id) ?? 'Zaposleni'}
                            </span>
                            <StarRating value={ar.rating} readOnly />
                        </div>
                    ))}
                </div>
                <div className="flex items-center justify-between border-t border-border pt-3">
                    <span className="text-muted-foreground">Brzina rešavanja</span>
                    <StarRating value={r.resolution_speed} readOnly />
                </div>
                {showCommunication && r.communication_quality !== null && (
                    <div className="flex items-center justify-between">
                        <span className="text-muted-foreground">Kvalitet komunikacije</span>
                        <StarRating value={r.communication_quality} readOnly />
                    </div>
                )}
                {showDegree && r.degree_of_resolution !== null && (
                    <div className="flex items-center justify-between">
                        <span className="text-muted-foreground">Stepen rešenja</span>
                        <StarRating value={r.degree_of_resolution} readOnly />
                    </div>
                )}
            </div>
        </div>
    );
}

function RatingForm({
    ticket,
    showCommunication,
    showDegree,
}: {
    ticket: Ticket;
    showCommunication: boolean;
    showDegree: boolean;
}) {
    const initialAgents = useMemo<Record<number, number>>(
        () => Object.fromEntries(ticket.agents.map((a) => [a.employee_id, 0])),
        [ticket.agents],
    );

    const form = useForm<RatingFormState>({
        resolution_speed: 0,
        communication_quality: 0,
        degree_of_resolution: 0,
        agents: initialAgents,
    });
    const { data, setData, processing, errors, reset, transform } = form;

    const allAgentsRated = ticket.agents.every((a) => (data.agents[a.employee_id] ?? 0) > 0);
    const canSubmit =
        allAgentsRated &&
        data.resolution_speed > 0 &&
        (!showCommunication || data.communication_quality > 0) &&
        (!showDegree || data.degree_of_resolution > 0);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        if (!canSubmit) {
return;
}

        transform((d) => ({
            resolution_speed: d.resolution_speed,
            communication_quality: showCommunication ? d.communication_quality : null,
            degree_of_resolution: showDegree ? d.degree_of_resolution : null,
            agents: ticket.agents.map((a) => ({
                employee_id: a.employee_id,
                rating: d.agents[a.employee_id],
            })),
        }) as unknown as RatingFormState);

        form.post(`/support-tickets/${ticket.id}/rate`, {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    return (
        <form
            onSubmit={submit}
            className="overflow-hidden rounded-xl border border-border bg-card shadow-sm"
        >
            <div className="border-b border-border px-5 py-4">
                <h3 className="text-[15px] font-semibold tracking-tight">Ocenite tiket</h3>
                <p className="mt-1 text-xs text-muted-foreground">
                    Vaša ocena pomaže nam da poboljšamo uslugu.
                </p>
            </div>
            <div className="space-y-4 px-5 py-4 text-[13px]">
                <div>
                    <div className="mb-2 text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
                        Zadovoljstvo po agentu
                    </div>
                    {ticket.agents.map((a) => (
                        <div key={a.employee_id} className="flex items-center justify-between py-1.5">
                            <span className="text-muted-foreground">{a.name}</span>
                            <StarRating
                                value={data.agents[a.employee_id] ?? 0}
                                onChange={(v) =>
                                    setData('agents', { ...data.agents, [a.employee_id]: v })
                                }
                            />
                        </div>
                    ))}
                    {errors.agents && <InputError message={errors.agents} />}
                </div>

                <div className="flex items-center justify-between border-t border-border pt-3">
                    <span className="text-muted-foreground">Brzina rešavanja</span>
                    <StarRating
                        value={data.resolution_speed}
                        onChange={(v) => setData('resolution_speed', v)}
                    />
                </div>
                <InputError message={errors.resolution_speed} />

                {showCommunication && (
                    <>
                        <div className="flex items-center justify-between">
                            <span className="text-muted-foreground">Kvalitet komunikacije</span>
                            <StarRating
                                value={data.communication_quality}
                                onChange={(v) => setData('communication_quality', v)}
                            />
                        </div>
                        <InputError message={errors.communication_quality} />
                    </>
                )}

                {showDegree && (
                    <>
                        <div className="flex items-center justify-between">
                            <span className="text-muted-foreground">Stepen rešenja</span>
                            <StarRating
                                value={data.degree_of_resolution}
                                onChange={(v) => setData('degree_of_resolution', v)}
                            />
                        </div>
                        <InputError message={errors.degree_of_resolution} />
                    </>
                )}
            </div>
            <div className="flex justify-end border-t border-border px-5 py-3">
                <Button
                    type="submit"
                    disabled={!canSubmit || processing}
                    className="bg-[#1a56db] text-white hover:bg-[#1648b8]"
                >
                    Pošalji ocenu
                </Button>
            </div>
        </form>
    );
}