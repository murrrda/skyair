import { Head, Link, usePage, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type PlaneStatus = 'in_garage' | 'in_flight' | 'in_service';

type PlaneFlight = {
    id: number;
    takeoff: string;
    arrival: string;
    status: string;
};

type PlaneAvailability = {
    id: number;
    reg_number: number;
    model: string;
    capacity: number;
    luxury_level: number;
    status: PlaneStatus;
    flights: PlaneFlight[];
};

type PageProps = {
    planes: PlaneAvailability[];
    filters: { from: string; to: string };
    flash?: { success?: string; error?: string };
};

const planeStatusMeta: Record<PlaneStatus, { label: string; className: string }> = {
    in_garage: { label: 'U hangaru', className: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400' },
    in_flight: { label: 'U letu', className: 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/30 dark:text-indigo-400' },
    in_service: { label: 'Na servisu', className: 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400' },
};

function formatDate(iso: string) {
    return new Date(iso).toLocaleString('sr-RS', {
        day: '2-digit',
        month: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
}

export default function DostupnostAviona() {
    const { props } = usePage<PageProps>();
    const { planes, filters, flash } = props;

    const [from, setFrom] = useState(filters.from);
    const [to, setTo] = useState(filters.to);

    useEffect(() => {
        if (flash?.success) {
toast.success(flash.success);
}

        if (flash?.error) {
toast.error(flash.error);
}
    }, [flash?.success, flash?.error]);

    function handleFilter() {
        router.get('/dispatcher/dostupnost-aviona', { from, to }, { preserveState: true });
    }

    const available = planes.filter((p) => p.status !== 'in_service' && p.flights.length === 0);
    const busy = planes.filter((p) => p.status === 'in_service' || p.flights.length > 0);

    return (
        <>
            <Head title="Dostupnost aviona" />
            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <header className="w-full border-b border-border/60">
                    <div className="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-4">
                        <div className="flex items-center gap-3 text-sm">
                            <Link href="/dispatcher" className="text-muted-foreground hover:text-foreground">
                                Upravljanje letovima
                            </Link>
                            <span className="text-muted-foreground">/</span>
                            <span className="font-medium">Dostupnost aviona</span>
                        </div>
                    </div>
                </header>

                <main className="mx-auto w-full max-w-6xl flex-1 px-6 py-10">
                    <div className="mb-8">
                        <h1 className="text-2xl font-bold tracking-tight">Dostupnost aviona</h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Pregledajte koji avioni su slobodni u odabranom periodu.
                        </p>
                    </div>

                    <div className="mb-8 rounded-xl border border-border bg-card p-6">
                        <h2 className="mb-4 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                            Period
                        </h2>
                        <div className="flex items-end gap-4">
                            <div className="space-y-1.5">
                                <Label className="text-xs font-medium">Od</Label>
                                <Input type="date" value={from} onChange={(e) => setFrom(e.target.value)} />
                            </div>
                            <div className="space-y-1.5">
                                <Label className="text-xs font-medium">Do</Label>
                                <Input type="date" value={to} onChange={(e) => setTo(e.target.value)} />
                            </div>
                            <Button onClick={handleFilter}>Prikaži</Button>
                        </div>
                    </div>

                    <div className="mb-6">
                        <h2 className="mb-4 text-lg font-semibold">
                            Slobodni avioni{' '}
                            <span className="text-sm font-normal text-muted-foreground">({available.length})</span>
                        </h2>
                        {available.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Nema slobodnih aviona u odabranom periodu.
                            </p>
                        ) : (
                            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                {available.map((plane) => (
                                    <div key={plane.id} className="rounded-xl border border-border bg-card p-5 shadow-sm">
                                        <div className="mb-3 flex items-start justify-between">
                                            <div>
                                                <div className="text-xl font-bold tracking-tight">{plane.reg_number}</div>
                                                <div className="text-xs text-muted-foreground">{plane.model}</div>
                                            </div>
                                            <span className={`rounded-full px-2 py-0.5 text-[11px] font-medium ${planeStatusMeta[plane.status].className}`}>
                                                {planeStatusMeta[plane.status].label}
                                            </span>
                                        </div>
                                        <div className="grid grid-cols-2 gap-3 border-t border-border pt-3 text-xs">
                                            <div>
                                                <div className="text-muted-foreground">Kapacitet</div>
                                                <div className="font-semibold">{plane.capacity} mesta</div>
                                            </div>
                                            <div>
                                                <div className="text-muted-foreground">Luksuz</div>
                                                <div className="font-semibold">Nivo {plane.luxury_level}</div>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>

                    <div>
                        <h2 className="mb-4 text-lg font-semibold">
                            Zauzeti avioni{' '}
                            <span className="text-sm font-normal text-muted-foreground">({busy.length})</span>
                        </h2>
                        {busy.length === 0 ? (
                            <p className="text-sm text-muted-foreground">Svi avioni su slobodni.</p>
                        ) : (
                            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                {busy.map((plane) => (
                                    <div key={plane.id} className="rounded-xl border border-border bg-card p-5 shadow-sm opacity-75">
                                        <div className="mb-3 flex items-start justify-between">
                                            <div>
                                                <div className="text-xl font-bold tracking-tight">{plane.reg_number}</div>
                                                <div className="text-xs text-muted-foreground">{plane.model}</div>
                                            </div>
                                            <span className={`rounded-full px-2 py-0.5 text-[11px] font-medium ${planeStatusMeta[plane.status].className}`}>
                                                {plane.status === 'in_service' ? 'Na servisu' : planeStatusMeta[plane.status].label}
                                            </span>
                                        </div>
                                        <div className="grid grid-cols-2 gap-3 border-t border-border pt-3 text-xs">
                                            <div>
                                                <div className="text-muted-foreground">Kapacitet</div>
                                                <div className="font-semibold">{plane.capacity} mesta</div>
                                            </div>
                                            <div>
                                                <div className="text-muted-foreground">Luksuz</div>
                                                <div className="font-semibold">Nivo {plane.luxury_level}</div>
                                            </div>
                                        </div>
                                        {plane.flights.length > 0 && (
                                            <div className="mt-3 border-t border-border pt-3">
                                                <div className="mb-1 text-[11px] font-medium text-muted-foreground">Zauzet:</div>
                                                {plane.flights.map((f) => (
                                                    <div key={f.id} className="text-xs text-muted-foreground">
                                                        {formatDate(f.takeoff)} — {formatDate(f.arrival)}
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </main>
            </div>
        </>
    );
}
