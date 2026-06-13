import { Head, Link, router, usePage } from '@inertiajs/react';
import { AlertTriangle, MapPin, Pencil, Play, RefreshCw } from 'lucide-react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';

type FlightStatus = 'scheduled' | 'boarding' | 'before_takeoff' | 'in_flight' | 'landed' | 'delayed' | 'cancelled' | 'emergency_landing';

type RouteChangeEntry = {
    id: number;
    original_route: string;
    new_route: string;
    reason: string;
    applied_at: string;
};

type PlaneChangeEntry = {
    id: number;
    original_plane: string;
    new_plane: string;
    reason: string;
    applied_at: string;
};

type FailureEntry = {
    id: number;
    description: string;
    seriousness_level: number;
    status: string;
    report_time: string;
};

type Flight = {
    id: number;
    route_id: number;
    plane_id: number | null;
    route_name: string;
    dep_code: string;
    dep_city: string;
    arr_code: string;
    arr_city: string;
    plane_model: string;
    plane_reg: string;
    expected_takeoff: string;
    expected_arrival: string;
    takeoff_formatted: string;
    arrival_formatted: string;
    status: FlightStatus;
    ticket_count: number;
    route_changes: RouteChangeEntry[];
    plane_changes: PlaneChangeEntry[];
    failures: FailureEntry[];
};

type PageProps = {
    flight: Flight;
    flash?: { success?: string; error?: string };
};

const statusMeta: Record<FlightStatus, { label: string; className: string }> = {
    scheduled: { label: 'Zakazan', className: 'bg-blue-50 text-blue-700 dark:bg-blue-950/30 dark:text-blue-400' },
    boarding: { label: 'Ukrcavanje', className: 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400' },
    before_takeoff: { label: 'Pred poletanje', className: 'bg-orange-50 text-orange-700 dark:bg-orange-950/30 dark:text-orange-400' },
    in_flight: { label: 'U letu', className: 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/30 dark:text-indigo-400' },
    landed: { label: 'Sleteo', className: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400' },
    delayed: { label: 'Kasni', className: 'bg-red-50 text-red-700 dark:bg-red-950/30 dark:text-red-400' },
    cancelled: { label: 'Otkazan', className: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' },
    emergency_landing: { label: 'Prinudno sletanje', className: 'bg-red-100 text-red-800 dark:bg-red-950/50 dark:text-red-300' },
};

const failureStatusLabels: Record<string, string> = {
    waiting_for_service: 'Čeka servis',
    currently_serviced: 'U servisu',
    fixed: 'Popravljeno',
};

const canStart = (s: FlightStatus) => s === 'scheduled' || s === 'boarding' || s === 'before_takeoff';

export default function DetaljiLeta() {
    const { flight, flash } = usePage<PageProps>().props;
    const isInFlight = flight.status === 'in_flight';
    const isEmergency = flight.status === 'emergency_landing';

    useEffect(() => {
        if (flash?.success) {
            toast.success(flash.success);
        }

        if (flash?.error) {
            toast.error(flash.error);
        }
    }, [flash?.success, flash?.error]);

    function handleStartFlight() {
        if (!confirm('Da li ste sigurni da želite da pokrenete let?')) {
            return;
        }

        router.post(`/dispatcher/letovi/${flight.id}/pokreni`);
    }

    return (
        <>
            <Head title={`Let ${flight.dep_code} → ${flight.arr_code}`} />
            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <header className="w-full border-b border-border/60">
                    <div className="mx-auto flex w-full max-w-4xl items-center justify-between px-6 py-4">
                        <div className="flex items-center gap-3 text-sm">
                            <Link href="/dispatcher" className="text-muted-foreground hover:text-foreground">
                                Upravljanje letovima
                            </Link>
                            <span className="text-muted-foreground">/</span>
                            <span className="font-medium">Detalji leta</span>
                        </div>
                        <div className="flex items-center gap-2">
                            <Button asChild size="sm" variant="outline">
                                <Link href={`/dispatcher/letovi/${flight.id}/izmena`}>
                                    <Pencil className="mr-1 h-3.5 w-3.5" />
                                    Izmeni
                                </Link>
                            </Button>
                            {canStart(flight.status) && (
                                <Button size="sm" onClick={handleStartFlight}>
                                    <Play className="mr-1 h-3.5 w-3.5" />
                                    Pokreni let
                                </Button>
                            )}
                        </div>
                    </div>
                </header>

                <main className="mx-auto w-full max-w-4xl flex-1 px-6 py-10">
                    <div className="mb-8 flex items-start justify-between">
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">
                                {flight.dep_code} → {flight.arr_code}
                            </h1>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {flight.dep_city} — {flight.arr_city} · {flight.route_name}
                            </p>
                        </div>
                        <span className={`rounded-full px-3 py-1 text-xs font-medium ${statusMeta[flight.status]?.className ?? ''}`}>
                            {statusMeta[flight.status]?.label ?? flight.status}
                        </span>
                    </div>

                    <div className="space-y-6">
                        <section className="rounded-xl border border-border bg-card p-6">
                            <h2 className="mb-4 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                Informacije o letu
                            </h2>
                            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                <InfoItem label="Poletanje" value={flight.takeoff_formatted} />
                                <InfoItem label="Sletanje" value={flight.arrival_formatted} />
                                <InfoItem label="Avion" value={`${flight.plane_model} (${flight.plane_reg})`} />
                                <InfoItem label="Prodato karata" value={String(flight.ticket_count)} />
                            </div>
                        </section>

                        {(isInFlight || isEmergency) && (
                            <section className={`rounded-xl border p-6 ${isEmergency ? 'border-red-300 bg-red-50/50 dark:border-red-800 dark:bg-red-950/20' : 'border-border bg-card'}`}>
                                <h2 className="mb-4 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                    Operacije tokom leta
                                </h2>
                                <div className="flex flex-wrap gap-3">
                                    {isInFlight && (
                                        <>
                                            <Button asChild variant="outline">
                                                <Link href={`/dispatcher/letovi/${flight.id}/promena-rute`}>
                                                    <MapPin className="mr-1.5 h-4 w-4" />
                                                    Promeni rutu
                                                </Link>
                                            </Button>
                                            <Button asChild variant="destructive">
                                                <Link href={`/dispatcher/letovi/${flight.id}/prinudno-sletanje`}>
                                                    <AlertTriangle className="mr-1.5 h-4 w-4" />
                                                    Prinudno sletanje
                                                </Link>
                                            </Button>
                                        </>
                                    )}
                                    {isEmergency && (
                                        <Button asChild variant="outline">
                                            <Link href={`/dispatcher/letovi/${flight.id}/zamena-aviona`}>
                                                <RefreshCw className="mr-1.5 h-4 w-4" />
                                                Zameni avion
                                            </Link>
                                        </Button>
                                    )}
                                </div>
                            </section>
                        )}

                        {flight.route_changes.length > 0 && (
                            <section className="rounded-xl border border-border bg-card p-6">
                                <h2 className="mb-4 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                    Istorija promena rute
                                </h2>
                                <div className="space-y-3">
                                    {flight.route_changes.map((rc) => (
                                        <div key={rc.id} className="rounded-lg border border-border p-4 text-sm">
                                            <div className="flex items-center justify-between">
                                                <span className="font-medium">{rc.original_route} → {rc.new_route}</span>
                                                <span className="text-xs text-muted-foreground">{rc.applied_at}</span>
                                            </div>
                                            <p className="mt-1 text-muted-foreground">{rc.reason}</p>
                                        </div>
                                    ))}
                                </div>
                            </section>
                        )}

                        {flight.plane_changes.length > 0 && (
                            <section className="rounded-xl border border-border bg-card p-6">
                                <h2 className="mb-4 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                    Istorija zamena aviona
                                </h2>
                                <div className="space-y-3">
                                    {flight.plane_changes.map((pc) => (
                                        <div key={pc.id} className="rounded-lg border border-border p-4 text-sm">
                                            <div className="flex items-center justify-between">
                                                <span className="font-medium">{pc.original_plane} → {pc.new_plane}</span>
                                                <span className="text-xs text-muted-foreground">{pc.applied_at}</span>
                                            </div>
                                            <p className="mt-1 text-muted-foreground">{pc.reason}</p>
                                        </div>
                                    ))}
                                </div>
                            </section>
                        )}

                        {flight.failures.length > 0 && (
                            <section className="rounded-xl border border-red-200 bg-red-50/30 p-6 dark:border-red-900 dark:bg-red-950/10">
                                <h2 className="mb-4 text-sm font-semibold uppercase tracking-wide text-red-700 dark:text-red-400">
                                    Evidentirani kvarovi
                                </h2>
                                <div className="space-y-3">
                                    {flight.failures.map((f) => (
                                        <div key={f.id} className="rounded-lg border border-red-200 bg-white p-4 text-sm dark:border-red-800 dark:bg-red-950/30">
                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center gap-2">
                                                    <span className="font-medium">Ozbiljnost: {f.seriousness_level}/5</span>
                                                    <span className="rounded-full bg-red-100 px-2 py-0.5 text-[11px] font-medium text-red-700 dark:bg-red-900 dark:text-red-300">
                                                        {failureStatusLabels[f.status] ?? f.status}
                                                    </span>
                                                </div>
                                                <span className="text-xs text-muted-foreground">{f.report_time}</span>
                                            </div>
                                            <p className="mt-1 text-muted-foreground">{f.description}</p>
                                        </div>
                                    ))}
                                </div>
                            </section>
                        )}
                    </div>
                </main>
            </div>
        </>
    );
}

function InfoItem({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <div className="text-xs text-muted-foreground">{label}</div>
            <div className="font-semibold">{value}</div>
        </div>
    );
}
