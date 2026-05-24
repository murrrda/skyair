import { Head, Link, router, useForm } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { cn } from '@/lib/utils';

// ─── Types ───────────────────────────────────────────────────────────────────

type UserData = {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    phone_number: string | null;
};

type TipUgovora = {
    id: number;
    naziv: string;
};

type ZaposlenRow = {
    user_id: number;
    role: string;
    status: 'aktivan' | 'neaktivan' | 'otkazan';
    user: UserData;
    tipovi_ugovora: TipUgovora[];
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: PaginationLink[];
};

type Props = {
    zaposleni: Paginated<ZaposlenRow>;
    tipoviUgovora: TipUgovora[];
    filters: {
        search?: string;
        role?: string;
        tip_ugovora_id?: string;
    };
};

// ─── Helpers ─────────────────────────────────────────────────────────────────

const ROLE_LABELS: Record<string, string> = {
    admin: 'Admin',
    pilot: 'Pilot',
    dispatcher: 'Dispečer',
    agent: 'Agent',
    cabin_crew: 'Kabinsko osoblje',
};

function StatusBadge({ status }: { status: ZaposlenRow['status'] }) {
    const styles = {
        aktivan: 'bg-emerald-50 text-emerald-700 border-emerald-200',
        neaktivan: 'bg-amber-50 text-amber-700 border-amber-200',
        otkazan: 'bg-red-50 text-red-700 border-red-200',
    };
    const labels = { aktivan: 'Aktivan', neaktivan: 'Neaktivan', otkazan: 'Otkazan' };

    return (
        <span className={cn('inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-medium', styles[status])}>
            {labels[status]}
        </span>
    );
}

function Initials({ name }: { name: string }) {
    const parts = name.trim().split(' ');
    const initials = parts.length >= 2 ? parts[0][0] + parts[parts.length - 1][0] : parts[0][0];

    return (
        <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#E6F1FB] text-xs font-semibold text-[#185FA5]">
            {initials.toUpperCase()}
        </div>
    );
}

// ─── Terminate dialog ─────────────────────────────────────────────────────────

type TerminateTarget = { user_id: number; name: string };

function TerminateDialog({
    target,
    onClose,
}: {
    target: TerminateTarget | null;
    onClose: () => void;
}) {
    const { data, setData, delete: destroy, processing, errors, reset } = useForm({
        razlog_otkaza: '',
        datum_otkaza: '',
    });

    function handleClose() {
        reset();
        onClose();
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();

        if (!target) {
return;
}

        destroy(`/admin/employee/${target.user_id}`, {
            onSuccess: handleClose,
        });
    }

    return (
        <Dialog open={target !== null} onOpenChange={(open) => !open && handleClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Otpusti zaposlenog</DialogTitle>
                </DialogHeader>

                <p className="text-sm text-muted-foreground">
                    Otpuštate <span className="font-medium text-foreground">{target?.name}</span>. Ova akcija se ne može poništiti.
                </p>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="space-y-1.5">
                        <Label htmlFor="razlog_otkaza">Razlog otpuštanja *</Label>
                        <textarea
                            id="razlog_otkaza"
                            rows={3}
                            value={data.razlog_otkaza}
                            onChange={(e) => setData('razlog_otkaza', e.target.value)}
                            className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            placeholder="Unesite razlog..."
                            required
                        />
                        {errors.razlog_otkaza && (
                            <p className="text-xs text-destructive">{errors.razlog_otkaza}</p>
                        )}
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="datum_otkaza">Datum otpuštanja</Label>
                        <Input
                            id="datum_otkaza"
                            type="date"
                            value={data.datum_otkaza}
                            onChange={(e) => setData('datum_otkaza', e.target.value)}
                        />
                        {errors.datum_otkaza && (
                            <p className="text-xs text-destructive">{errors.datum_otkaza}</p>
                        )}
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={handleClose}>
                            Odustani
                        </Button>
                        <Button type="submit" variant="destructive" disabled={processing}>
                            Otpusti
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

// ─── Page ─────────────────────────────────────────────────────────────────────

export default function ZaposlenIndex({ zaposleni, tipoviUgovora, filters }: Props) {
    const [terminateTarget, setTerminateTarget] = useState<TerminateTarget | null>(null);

    function filter(key: string, value: string) {
        router.get(
            '/admin/employee',
            { ...filters, [key]: value || undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    return (
        <>
            <Head title="Zaposleni" />

            {/* Breadcrumb */}
            <nav className="mb-6 flex items-center gap-1.5 text-sm text-muted-foreground">
                <Link href="/admin" className="hover:text-foreground">Administracija</Link>
                <span>›</span>
                <span className="text-foreground">Zaposleni</span>
            </nav>

            {/* Title row */}
            <div className="mb-6 flex items-start justify-between">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Zaposleni</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Upravljanje evidencijom zaposlenih, sertifikatima i obukama
                    </p>
                </div>
                <Button asChild className="bg-[#185FA5] hover:bg-[#0C447C]">
                    <Link href="/admin/employee/create">+ Dodaj zaposlenog</Link>
                </Button>
            </div>

            {/* Filters */}
            <div className="mb-4 flex flex-wrap items-center gap-3">
                <div className="relative">
                    <Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        className="w-72 pl-9"
                        placeholder="Pretraži po imenu, e-mailu..."
                        defaultValue={filters.search ?? ''}
                        onChange={(e) => filter('search', e.target.value)}
                    />
                </div>

                <Select
                    value={filters.role ?? ''}
                    onValueChange={(v) => filter('role', v === '_all' ? '' : v)}
                >
                    <SelectTrigger className="w-44">
                        <SelectValue placeholder="Sva zanimanja" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="_all">Sva zanimanja</SelectItem>
                        {Object.entries(ROLE_LABELS).map(([value, label]) => (
                            <SelectItem key={value} value={value}>{label}</SelectItem>
                        ))}
                    </SelectContent>
                </Select>

                <Select
                    value={filters.tip_ugovora_id ?? ''}
                    onValueChange={(v) => filter('tip_ugovora_id', v === '_all' ? '' : v)}
                >
                    <SelectTrigger className="w-44">
                        <SelectValue placeholder="Svi ugovori" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="_all">Svi ugovori</SelectItem>
                        {tipoviUgovora.map((tip) => (
                            <SelectItem key={tip.id} value={String(tip.id)}>{tip.naziv}</SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            {/* Table */}
            <div className="rounded-lg border border-border bg-card">
                <div className="flex items-center justify-between px-4 py-3">
                    <span className="text-sm font-medium">Svi zaposleni</span>
                    <span className="text-sm text-muted-foreground">{zaposleni.total} zaposlenih</span>
                </div>

                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-t border-border bg-muted/40">
                            <th className="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">Zaposleni</th>
                            <th className="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">E-mail</th>
                            <th className="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">Telefon</th>
                            <th className="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">Ugovor</th>
                            <th className="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">Status</th>
                            <th className="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">Akcije</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-border">
                        {zaposleni.data.length === 0 && (
                            <tr>
                                <td colSpan={6} className="px-4 py-8 text-center text-muted-foreground">
                                    Nema pronađenih zaposlenih.
                                </td>
                            </tr>
                        )}
                        {zaposleni.data.map((z) => (
                            <tr key={z.user_id} className="hover:bg-muted/30">
                                <td className="px-4 py-3">
                                    <div className="flex items-center gap-3">
                                        <Initials name={`${z.user.first_name} ${z.user.last_name}`} />
                                        <div>
                                            <div className="font-medium">{z.user.first_name} {z.user.last_name}</div>
                                            <div className="text-xs text-muted-foreground">{ROLE_LABELS[z.role] ?? z.role}</div>
                                        </div>
                                    </div>
                                </td>
                                <td className="px-4 py-3 text-muted-foreground">{z.user.email}</td>
                                <td className="px-4 py-3 text-muted-foreground">{z.user.phone_number ?? '—'}</td>
                                <td className="px-4 py-3">
                                    {z.tipovi_ugovora[0] ? (
                                        <Badge variant="secondary">{z.tipovi_ugovora[0].naziv}</Badge>
                                    ) : (
                                        <span className="text-muted-foreground">—</span>
                                    )}
                                </td>
                                <td className="px-4 py-3">
                                    <StatusBadge status={z.status} />
                                </td>
                                <td className="px-4 py-3">
                                    <div className="flex items-center gap-2">
                                        <Button asChild variant="outline" size="sm">
                                            <Link href={`/admin/employee/${z.user_id}/edit`}>
                                                ✎ Izmijeni
                                            </Link>
                                        </Button>
                                        {z.status !== 'otkazan' && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                className="border-red-200 text-red-600 hover:bg-red-50 hover:text-red-700"
                                                onClick={() =>
                                                    setTerminateTarget({
                                                        user_id: z.user_id,
                                                        name: `${z.user.first_name} ${z.user.last_name}`,
                                                    })
                                                }
                                            >
                                                Otpusti
                                            </Button>
                                        )}
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>

                {/* Pagination */}
                {zaposleni.last_page > 1 && (
                    <div className="flex items-center justify-between border-t border-border px-4 py-3">
                        <span className="text-sm text-muted-foreground">
                            Prikazano {zaposleni.from}–{zaposleni.to} od {zaposleni.total} zaposlenih
                        </span>
                        <div className="flex items-center gap-1">
                            {zaposleni.links.map((link, i) => (
                                <button
                                    key={i}
                                    disabled={!link.url}
                                    onClick={() => link.url && router.get(link.url, {}, { preserveState: true })}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
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

            <TerminateDialog
                target={terminateTarget}
                onClose={() => setTerminateTarget(null)}
            />
        </>
    );
}
