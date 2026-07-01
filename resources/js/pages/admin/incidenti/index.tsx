import { Head, Link, router, useForm } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { todayIso } from '@/lib/date';
import { cn } from '@/lib/utils';

// ─── Types ───────────────────────────────────────────────────────────────────

type Option = { id: number; name: string };
type SeverityOption = {
    id: number;
    name: string;
    rank: number;
    description: string | null;
};
type FlightOption = { id: number; label: string };
type EmployeeOption = { user_id: number; name: string };

type IncidentRow = {
    id: number;
    flight: { number: string; route: string; plane: string };
    occurred_at: string;
    occurred_time: string;
    type: string;
    severity: { name: string; rank: number };
    responsible: { user_id: number; name: string }[];
};

type PaginationLink = { url: string | null; label: string; active: boolean };
type Paginated<T> = {
    data: T[];
    last_page: number;
    total: number;
    from: number;
    to: number;
    links: PaginationLink[];
};

type Filters = {
    search?: string | null;
    incident_type_id?: string | null;
    severity_level_id?: string | null;
    from?: string | null;
    to?: string | null;
};

type Props = {
    incidents: Paginated<IncidentRow>;
    incidentTypes: Option[];
    severityLevels: SeverityOption[];
    flights: FlightOption[];
    employees: EmployeeOption[];
    filters: Filters;
    flash?: { success?: string; error?: string };
};

// ─── Severity styling (by rank) ────────────────────────────────────────────────

const SEVERITY_STYLES: Record<
    number,
    { badge: string; dot: string; text: string; border: string; bg: string }
> = {
    1: {
        badge: 'bg-[#e1f5ee] text-[#0f6e56]',
        dot: 'bg-[#0f6e56]',
        text: 'text-[#0f6e56]',
        border: 'border-[#0f6e56]',
        bg: 'bg-[#f0fbf7]',
    },
    2: {
        badge: 'bg-[#fef9e7] text-[#7d6608]',
        dot: 'bg-[#7d6608]',
        text: 'text-[#7d6608]',
        border: 'border-[#7d6608]',
        bg: 'bg-[#fffdf5]',
    },
    3: {
        badge: 'bg-[#faeeda] text-[#854f0b]',
        dot: 'bg-[#854f0b]',
        text: 'text-[#854f0b]',
        border: 'border-[#854f0b]',
        bg: 'bg-[#fffaf3]',
    },
    4: {
        badge: 'bg-[#fcebeb] text-[#a32d2d]',
        dot: 'bg-[#a32d2d]',
        text: 'text-[#a32d2d]',
        border: 'border-[#a32d2d]',
        bg: 'bg-[#fef6f6]',
    },
};

function severityStyle(rank: number) {
    return SEVERITY_STYLES[rank] ?? SEVERITY_STYLES[1];
}

// ─── Create dialog ──────────────────────────────────────────────────────────────

function CreateIncidentDialog({
    open,
    onClose,
    incidentTypes,
    severityLevels,
    flights,
    employees,
}: {
    open: boolean;
    onClose: () => void;
    incidentTypes: Option[];
    severityLevels: SeverityOption[];
    flights: FlightOption[];
    employees: EmployeeOption[];
}) {
    const { data, setData, post, processing, errors, reset, transform } =
        useForm({
            flight_id: '',
            date: '',
            time: '',
            incident_type_id: '',
            severity_level_id: '',
            description: '',
            responsible_employees: [] as number[],
        });

    transform((d) => ({
        flight_id: d.flight_id,
        incident_type_id: d.incident_type_id,
        severity_level_id: d.severity_level_id,
        occurred_at: d.date && d.time ? `${d.date} ${d.time}` : d.date,
        description: d.description,
        responsible_employees: d.responsible_employees,
    }));

    function handleClose() {
        reset();
        onClose();
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post('/admin/incidenti', { onSuccess: handleClose });
    }

    const occurredAtError = (errors as Record<string, string>).occurred_at;
    const selected = data.responsible_employees;
    const available = employees.filter((e) => !selected.includes(e.user_id));

    function addEmployee(id: number) {
        setData('responsible_employees', [...selected, id]);
    }

    function removeEmployee(id: number) {
        setData(
            'responsible_employees',
            selected.filter((x) => x !== id),
        );
    }

    return (
        <Dialog open={open} onOpenChange={(o) => !o && handleClose()}>
            <DialogContent className="sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>Prijavi incident</DialogTitle>
                    <p className="text-sm text-muted-foreground">
                        Unesite sve poznate informacije o incidentu
                    </p>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4">
                    {/* Flight */}
                    <div className="space-y-1.5">
                        <Label className="text-xs font-semibold tracking-wide uppercase">
                            Let <span className="text-destructive">*</span>
                        </Label>
                        <Select
                            value={data.flight_id}
                            onValueChange={(v) => setData('flight_id', v)}
                        >
                            <SelectTrigger
                                className={
                                    errors.flight_id ? 'border-destructive' : ''
                                }
                            >
                                <SelectValue placeholder="Izaberite let..." />
                            </SelectTrigger>
                            <SelectContent>
                                {flights.map((f) => (
                                    <SelectItem key={f.id} value={String(f.id)}>
                                        {f.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.flight_id && (
                            <p className="text-xs text-destructive">
                                {errors.flight_id}
                            </p>
                        )}
                    </div>

                    {/* Date + time */}
                    <div className="grid grid-cols-2 gap-3">
                        <div className="space-y-1.5">
                            <Label className="text-xs font-semibold tracking-wide uppercase">
                                Datum incidenta{' '}
                                <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                type="date"
                                max={todayIso()}
                                value={data.date}
                                onChange={(e) =>
                                    setData('date', e.target.value)
                                }
                                className={
                                    occurredAtError ? 'border-destructive' : ''
                                }
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label className="text-xs font-semibold tracking-wide uppercase">
                                Vreme incidenta{' '}
                                <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                type="time"
                                value={data.time}
                                onChange={(e) =>
                                    setData('time', e.target.value)
                                }
                            />
                        </div>
                    </div>
                    {occurredAtError && (
                        <p className="text-xs text-destructive">
                            {occurredAtError}
                        </p>
                    )}

                    {/* Type */}
                    <div className="space-y-1.5">
                        <Label className="text-xs font-semibold tracking-wide uppercase">
                            Tip incidenta{' '}
                            <span className="text-destructive">*</span>
                        </Label>
                        <Select
                            value={data.incident_type_id}
                            onValueChange={(v) =>
                                setData('incident_type_id', v)
                            }
                        >
                            <SelectTrigger
                                className={
                                    errors.incident_type_id
                                        ? 'border-destructive'
                                        : ''
                                }
                            >
                                <SelectValue placeholder="Izaberite tip..." />
                            </SelectTrigger>
                            <SelectContent>
                                {incidentTypes.map((t) => (
                                    <SelectItem key={t.id} value={String(t.id)}>
                                        {t.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.incident_type_id && (
                            <p className="text-xs text-destructive">
                                {errors.incident_type_id}
                            </p>
                        )}
                    </div>

                    {/* Severity cards */}
                    <div className="space-y-1.5">
                        <Label className="text-xs font-semibold tracking-wide uppercase">
                            Nivo težine{' '}
                            <span className="text-destructive">*</span>
                        </Label>
                        <div className="grid grid-cols-4 gap-2">
                            {severityLevels.map((s) => {
                                const st = severityStyle(s.rank);
                                const active =
                                    data.severity_level_id === String(s.id);

                                return (
                                    <button
                                        type="button"
                                        key={s.id}
                                        onClick={() =>
                                            setData(
                                                'severity_level_id',
                                                String(s.id),
                                            )
                                        }
                                        className={cn(
                                            'flex flex-col items-center gap-1 rounded-lg border-2 px-2 py-3 text-center transition',
                                            active
                                                ? cn(st.border, st.bg)
                                                : 'border-[#e5e2d8] bg-white hover:border-[#d3d1c7]',
                                        )}
                                    >
                                        <span
                                            className={cn(
                                                'size-2.5 rounded-full',
                                                st.dot,
                                            )}
                                        />
                                        <span
                                            className={cn(
                                                'text-xs font-bold',
                                                st.text,
                                            )}
                                        >
                                            {s.name}
                                        </span>
                                        <span className="text-[10px] text-muted-foreground">
                                            {s.description}
                                        </span>
                                    </button>
                                );
                            })}
                        </div>
                        {errors.severity_level_id && (
                            <p className="text-xs text-destructive">
                                {errors.severity_level_id}
                            </p>
                        )}
                    </div>

                    {/* Description */}
                    <div className="space-y-1.5">
                        <Label className="text-xs font-semibold tracking-wide uppercase">
                            Šta se desilo{' '}
                            <span className="text-destructive">*</span>
                        </Label>
                        <textarea
                            rows={4}
                            value={data.description}
                            onChange={(e) =>
                                setData('description', e.target.value)
                            }
                            placeholder="Opišite incident što detaljnije..."
                            className={cn(
                                'w-full rounded-md border bg-background px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50',
                                errors.description
                                    ? 'border-destructive'
                                    : 'border-input',
                            )}
                        />
                        {errors.description && (
                            <p className="text-xs text-destructive">
                                {errors.description}
                            </p>
                        )}
                    </div>

                    {/* Responsible employees */}
                    <div className="space-y-1.5">
                        <Label className="text-xs font-semibold tracking-wide uppercase">
                            Odgovorni zaposleni
                        </Label>
                        <Select
                            value=""
                            onValueChange={(v) => addEmployee(Number(v))}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Dodaj zaposlenog..." />
                            </SelectTrigger>
                            <SelectContent>
                                {available.length === 0 && (
                                    <div className="px-2 py-1.5 text-sm text-muted-foreground">
                                        Nema dostupnih
                                    </div>
                                )}
                                {available.map((e) => (
                                    <SelectItem
                                        key={e.user_id}
                                        value={String(e.user_id)}
                                    >
                                        {e.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {selected.length > 0 && (
                            <div className="flex flex-wrap gap-2 pt-1">
                                {selected.map((id) => {
                                    const emp = employees.find(
                                        (e) => e.user_id === id,
                                    );

                                    return (
                                        <span
                                            key={id}
                                            className="inline-flex items-center gap-1.5 rounded-full border border-[#c9ddf3] bg-[#e6f1fb] py-1 pr-2.5 pl-2 text-xs text-[#185fa5]"
                                        >
                                            {emp?.name ?? `#${id}`}
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    removeEmployee(id)
                                                }
                                                className="text-[#185fa5] hover:text-[#0c447c]"
                                            >
                                                ✕
                                            </button>
                                        </span>
                                    );
                                })}
                            </div>
                        )}
                    </div>

                    <DialogFooter className="gap-2 sm:gap-0">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={handleClose}
                        >
                            Otkaži
                        </Button>
                        <Button
                            type="submit"
                            disabled={processing}
                            className="bg-[#185FA5] hover:bg-[#0C447C]"
                        >
                            Prijavi incident
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

// ─── Page ─────────────────────────────────────────────────────────────────────

export default function IncidentIndex({
    incidents,
    incidentTypes,
    severityLevels,
    flights,
    employees,
    filters,
    flash,
}: Props) {
    const [createOpen, setCreateOpen] = useState(false);

    useEffect(() => {
        if (flash?.success) {
            toast.success(flash.success);
        }

        if (flash?.error) {
            toast.error(flash.error);
        }
    }, [flash?.success, flash?.error]);

    function filter(key: string, value: string) {
        router.get(
            '/admin/incidenti',
            { ...filters, [key]: value || undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    return (
        <>
            <Head title="Incidenti" />

            {/* Tabs */}
            <div className="mb-6 flex items-center gap-6 border-b border-border">
                <span className="border-b-2 border-[#185FA5] pb-3 text-sm font-semibold text-[#185FA5]">
                    Lista incidenata
                </span>
                <Link
                    href="/admin/incidenti/rizicni"
                    className="pb-3 text-sm text-muted-foreground hover:text-foreground"
                >
                    Rizični zaposleni
                </Link>
            </div>

            {/* Title row */}
            <div className="mb-6 flex items-start justify-between">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">
                        Incidenti
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Evidencija i pregled svih prijavljenih incidenata
                    </p>
                </div>
                <Button
                    onClick={() => setCreateOpen(true)}
                    className="bg-[#185FA5] hover:bg-[#0C447C]"
                >
                    + Prijavi incident
                </Button>
            </div>

            {/* Filters */}
            <div className="mb-4 flex flex-wrap items-end gap-3">
                <div className="relative">
                    <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        className="w-72 pl-9"
                        placeholder="Pretraži po opisu, broju leta..."
                        defaultValue={filters.search ?? ''}
                        onChange={(e) => filter('search', e.target.value)}
                    />
                </div>

                <div className="space-y-1">
                    <Label className="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">
                        Tip incidenta
                    </Label>
                    <Select
                        value={filters.incident_type_id ?? ''}
                        onValueChange={(v) =>
                            filter('incident_type_id', v === '_all' ? '' : v)
                        }
                    >
                        <SelectTrigger className="w-44">
                            <SelectValue placeholder="Svi tipovi" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="_all">Svi tipovi</SelectItem>
                            {incidentTypes.map((t) => (
                                <SelectItem key={t.id} value={String(t.id)}>
                                    {t.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="space-y-1">
                    <Label className="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">
                        Težina
                    </Label>
                    <Select
                        value={filters.severity_level_id ?? ''}
                        onValueChange={(v) =>
                            filter('severity_level_id', v === '_all' ? '' : v)
                        }
                    >
                        <SelectTrigger className="w-40">
                            <SelectValue placeholder="Sve težine" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="_all">Sve težine</SelectItem>
                            {severityLevels.map((s) => (
                                <SelectItem key={s.id} value={String(s.id)}>
                                    {s.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="space-y-1">
                    <Label className="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">
                        Period — od
                    </Label>
                    <Input
                        type="date"
                        max={todayIso()}
                        className="w-40"
                        defaultValue={filters.from ?? ''}
                        onChange={(e) => filter('from', e.target.value)}
                    />
                </div>

                <div className="space-y-1">
                    <Label className="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">
                        Period — do
                    </Label>
                    <Input
                        type="date"
                        max={todayIso()}
                        className="w-40"
                        defaultValue={filters.to ?? ''}
                        onChange={(e) => filter('to', e.target.value)}
                    />
                </div>
            </div>

            {/* Table */}
            <div className="rounded-lg border border-border bg-card">
                <div className="flex items-center justify-between px-4 py-3">
                    <span className="text-sm font-medium">Svi incidenti</span>
                    <span className="text-sm text-muted-foreground">
                        {incidents.total} incidenata
                    </span>
                </div>

                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-t border-border bg-muted/40">
                            <th className="px-4 py-2.5 text-left text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                Let
                            </th>
                            <th className="px-4 py-2.5 text-left text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                Datum i vreme
                            </th>
                            <th className="px-4 py-2.5 text-left text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                Tip incidenta
                            </th>
                            <th className="px-4 py-2.5 text-left text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                Nivo ozbiljnosti
                            </th>
                            <th className="px-4 py-2.5 text-left text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                Odgovorni zaposleni
                            </th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-border">
                        {incidents.data.length === 0 && (
                            <tr>
                                <td
                                    colSpan={5}
                                    className="px-4 py-8 text-center text-muted-foreground"
                                >
                                    Nema prijavljenih incidenata.
                                </td>
                            </tr>
                        )}
                        {incidents.data.map((inc) => {
                            const st = severityStyle(inc.severity.rank);

                            return (
                                <tr key={inc.id} className="hover:bg-muted/30">
                                    <td className="px-4 py-3">
                                        <div className="font-semibold">
                                            {inc.flight.number} ·{' '}
                                            {inc.flight.route}
                                        </div>
                                        <div className="text-xs text-muted-foreground">
                                            {inc.flight.plane}
                                        </div>
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {inc.occurred_at} · {inc.occurred_time}
                                    </td>
                                    <td className="px-4 py-3">
                                        <span className="inline-flex items-center rounded-full bg-[#f1efe8] px-2.5 py-0.5 text-xs text-[#5f5e5a]">
                                            {inc.type}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3">
                                        <span
                                            className={cn(
                                                'inline-flex items-center rounded-md px-2.5 py-1 text-xs font-bold',
                                                st.badge,
                                            )}
                                        >
                                            {inc.severity.name}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3">
                                        {inc.responsible.length === 0 ? (
                                            <span className="text-muted-foreground">
                                                —
                                            </span>
                                        ) : (
                                            <div className="flex flex-wrap gap-1.5">
                                                {inc.responsible.map((r) => (
                                                    <span
                                                        key={r.user_id}
                                                        className="inline-flex items-center rounded-full bg-[#e6f1fb] px-2.5 py-0.5 text-xs text-[#185fa5]"
                                                    >
                                                        {r.name}
                                                    </span>
                                                ))}
                                            </div>
                                        )}
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>

                {incidents.last_page > 1 && (
                    <div className="flex items-center justify-between border-t border-border px-4 py-3">
                        <span className="text-sm text-muted-foreground">
                            Prikazano {incidents.from}–{incidents.to} od{' '}
                            {incidents.total} incidenata
                        </span>
                        <div className="flex items-center gap-1">
                            {incidents.links.map((link, i) => (
                                <button
                                    key={i}
                                    disabled={!link.url}
                                    onClick={() =>
                                        link.url &&
                                        router.get(
                                            link.url,
                                            {},
                                            { preserveState: true },
                                        )
                                    }
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                    className={cn(
                                        'flex h-8 min-w-8 items-center justify-center rounded-md border px-2 text-sm transition',
                                        link.active
                                            ? 'border-[#185FA5] bg-[#185FA5] text-white'
                                            : 'border-border bg-background text-foreground hover:bg-muted disabled:cursor-not-allowed disabled:opacity-40',
                                    )}
                                />
                            ))}
                        </div>
                    </div>
                )}
            </div>

            <CreateIncidentDialog
                open={createOpen}
                onClose={() => setCreateOpen(false)}
                incidentTypes={incidentTypes}
                severityLevels={severityLevels}
                flights={flights}
                employees={employees}
            />
        </>
    );
}
