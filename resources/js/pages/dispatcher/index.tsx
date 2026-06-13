import { Head, Link, usePage } from '@inertiajs/react';
import { CalendarClock, Pencil, Plane, Plus } from 'lucide-react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';

type FlightStatus = 'scheduled' | 'boarding' | 'before_takeoff' | 'in_flight' | 'landed' | 'delayed' | 'cancelled';

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
};

type PageProps = {
    flights: Flight[];
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
};

export default function DispatcherIndex() {
    const { props } = usePage<PageProps>();
    const { flights, flash } = props;

    useEffect(() => {
        if (flash?.success) {
toast.success(flash.success);
}

        if (flash?.error) {
toast.error(flash.error);
}
    }, [flash?.success, flash?.error]);

    return (
        <>
            <Head title="Upravljanje letovima" />
            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <header className="w-full border-b border-border/60">
                    <div className="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-4">
                        <span className="text-sm font-medium">Upravljanje letovima</span>
                        <nav className="flex items-center gap-2">
                            <Button asChild variant="outline">
                                <Link href="/dispatcher/sabloni">
                                    <CalendarClock className="mr-1 h-4 w-4" />
                                    Šabloni
                                </Link>
                            </Button>
                            <Button asChild>
                                <Link href="/dispatcher/zakazivanje-leta">
                                    <Plus className="mr-1 h-4 w-4" />
                                    Zakaži let
                                </Link>
                            </Button>
                        </nav>
                    </div>
                </header>

                <main className="mx-auto w-full max-w-6xl flex-1 px-6 py-10">
                    <div className="mb-8">
                        <h1 className="text-2xl font-bold tracking-tight">Letovi</h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {flights.length === 0
                                ? 'Još uvek nema zakazanih letova.'
                                : `${flights.length} let${flights.length === 1 ? '' : 'ova'} u sistemu`}
                        </p>
                    </div>

                    {flights.length === 0 ? (
                        <div className="flex flex-col items-center justify-center rounded-xl border border-dashed border-border bg-card/50 px-6 py-20 text-center">
                            <Plane className="mb-3 h-10 w-10 text-muted-foreground" />
                            <h2 className="text-base font-semibold">Nema letova</h2>
                            <p className="mt-1 mb-6 max-w-sm text-sm text-muted-foreground">
                                Kliknite &quot;Zakaži let&quot; da zakažete prvi let.
                            </p>
                            <Button asChild>
                                <Link href="/dispatcher/zakazivanje-leta">
                                    <Plus className="mr-1 h-4 w-4" />
                                    Zakaži let
                                </Link>
                            </Button>
                        </div>
                    ) : (
                        <div className="space-y-3">
                            {flights.map((flight) => (
                                <div
                                    key={flight.id}
                                    className="flex items-center justify-between rounded-xl border border-border bg-card p-5 shadow-sm"
                                >
                                    <div className="flex items-center gap-6">
                                        <div className="min-w-[140px]">
                                            <div className="text-lg font-bold tracking-tight">
                                                {flight.dep_code} → {flight.arr_code}
                                            </div>
                                            <div className="text-xs text-muted-foreground">
                                                {flight.dep_city} — {flight.arr_city}
                                            </div>
                                        </div>
                                        <div className="text-sm">
                                            <div className="font-medium">{flight.takeoff_formatted}</div>
                                            <div className="text-xs text-muted-foreground">
                                                → {flight.arrival_formatted}
                                            </div>
                                        </div>
                                        <div className="text-sm">
                                            <div className="text-xs text-muted-foreground">Avion</div>
                                            <div className="font-medium">{flight.plane_model}</div>
                                        </div>
                                        <div className="text-sm">
                                            <div className="text-xs text-muted-foreground">Karata</div>
                                            <div className="font-medium">{flight.ticket_count}</div>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <span
                                            className={`rounded-full px-2.5 py-0.5 text-[11px] font-medium ${statusMeta[flight.status].className}`}
                                        >
                                            {statusMeta[flight.status].label}
                                        </span>
                                        <Link
                                            href={`/dispatcher/letovi/${flight.id}/izmena`}
                                            className="rounded-md p-1.5 text-muted-foreground transition hover:bg-accent hover:text-foreground"
                                        >
                                            <Pencil className="h-4 w-4" />
                                        </Link>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </main>
            </div>
        </>
    );
}
