import { Head, Link, router, usePage } from '@inertiajs/react';
import { Map as MapIcon, Pencil, Plus, Trash2 } from 'lucide-react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';

type Airport = {
    id: number;
    iata_code: string;
    city: string;
};

type Route = {
    id: number;
    name: string;
    starting_airport: Airport | null;
    landing_airport: Airport | null;
    distance_km: number;
    estimated_time: number;
    active: boolean;
};

type PageProps = {
    routes: Route[];
    flash?: { success?: string; error?: string };
};

function formatDuration(minutes: number): string {
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    if (h === 0) return `${m} min`;
    if (m === 0) return `${h}h`;
    return `${h}h ${m}m`;
}

function formatKm(km: number): string {
    return `${km.toLocaleString('sr-RS')} km`;
}

export default function RuteIndex() {
    const { props } = usePage<PageProps>();
    const { routes, flash } = props;

    useEffect(() => {
        if (flash?.success) toast.success(flash.success);
        if (flash?.error) toast.error(flash.error);
    }, [flash?.success, flash?.error]);

    function handleDelete(route: Route) {
        if (!confirm(`Da li ste sigurni da želite da obrišete rutu "${route.name}"?`)) {
            return;
        }
        router.delete(`/admin/rute/${route.id}`, { preserveScroll: true });
    }

    return (
        <>
            <Head title="Rute" />
            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <header className="w-full border-b border-border/60">
                    <div className="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-4">
                        <div className="flex items-center gap-3 text-sm">
                            <Link href="/admin" className="text-muted-foreground hover:text-foreground">
                                ← Admin panel
                            </Link>
                            <span className="text-muted-foreground">/</span>
                            <span className="font-medium">Rute</span>
                        </div>
                        <Button asChild>
                            <Link href="/admin/rute/nova">
                                <Plus className="mr-1 h-4 w-4" />
                                Dodaj rutu
                            </Link>
                        </Button>
                    </div>
                </header>

                <main className="mx-auto w-full max-w-6xl flex-1 px-6 py-10">
                    <div className="mb-8">
                        <h1 className="text-2xl font-bold tracking-tight">Rute</h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {routes.length === 0
                                ? 'Još uvek nema definisanih ruta.'
                                : `${routes.length} rut${routes.length === 1 ? 'a' : routes.length < 5 ? 'e' : 'a'} u sistemu`}
                        </p>
                    </div>

                    {routes.length === 0 ? (
                        <div className="flex flex-col items-center justify-center rounded-xl border border-dashed border-border bg-card/50 px-6 py-20 text-center">
                            <MapIcon className="mb-3 h-10 w-10 text-muted-foreground" />
                            <h2 className="text-base font-semibold">Nema ruta</h2>
                            <p className="mt-1 mb-6 max-w-sm text-sm text-muted-foreground">
                                Kliknite "Dodaj rutu" da kreirate prvu rutu.
                            </p>
                            <Button asChild>
                                <Link href="/admin/rute/nova">
                                    <Plus className="mr-1 h-4 w-4" />
                                    Dodaj rutu
                                </Link>
                            </Button>
                        </div>
                    ) : (
                        <div className="overflow-hidden rounded-xl border border-border bg-card">
                            <table className="w-full text-sm">
                                <thead className="border-b border-border bg-muted/30 text-xs uppercase text-muted-foreground">
                                    <tr>
                                        <th className="px-4 py-3 text-left font-medium">Naziv</th>
                                        <th className="px-4 py-3 text-left font-medium">Polazni</th>
                                        <th className="px-4 py-3 text-left font-medium">Krajnji</th>
                                        <th className="px-4 py-3 text-right font-medium">Distanca</th>
                                        <th className="px-4 py-3 text-right font-medium">Trajanje</th>
                                        <th className="px-4 py-3 text-center font-medium">Status</th>
                                        <th className="px-4 py-3 text-right font-medium">Akcije</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {routes.map((route) => (
                                        <tr key={route.id} className="border-b border-border/60 last:border-b-0">
                                            <td className="px-4 py-3 font-semibold">{route.name}</td>
                                            <td className="px-4 py-3">
                                                {route.starting_airport ? (
                                                    <span>
                                                        <span className="font-medium">{route.starting_airport.iata_code}</span>
                                                        <span className="text-muted-foreground"> · {route.starting_airport.city}</span>
                                                    </span>
                                                ) : (
                                                    <span className="text-muted-foreground">—</span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                {route.landing_airport ? (
                                                    <span>
                                                        <span className="font-medium">{route.landing_airport.iata_code}</span>
                                                        <span className="text-muted-foreground"> · {route.landing_airport.city}</span>
                                                    </span>
                                                ) : (
                                                    <span className="text-muted-foreground">—</span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-right">{formatKm(route.distance_km)}</td>
                                            <td className="px-4 py-3 text-right">{formatDuration(route.estimated_time)}</td>
                                            <td className="px-4 py-3 text-center">
                                                {route.active ? (
                                                    <span className="rounded-full bg-[#ecfdf5] px-2 py-0.5 text-[11px] font-medium text-[#059669]">
                                                        Aktivna
                                                    </span>
                                                ) : (
                                                    <span className="rounded-full bg-muted px-2 py-0.5 text-[11px] font-medium text-muted-foreground">
                                                        Neaktivna
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex items-center justify-end gap-1">
                                                    <Link
                                                        href={`/admin/rute/${route.id}/uredi`}
                                                        className="rounded-md p-1.5 text-muted-foreground transition hover:bg-accent hover:text-foreground"
                                                        aria-label={`Uredi rutu ${route.name}`}
                                                    >
                                                        <Pencil className="h-4 w-4" />
                                                    </Link>
                                                    <button
                                                        type="button"
                                                        onClick={() => handleDelete(route)}
                                                        className="rounded-md p-1.5 text-muted-foreground transition hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/30 dark:hover:text-red-400"
                                                        aria-label={`Obriši rutu ${route.name}`}
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </main>
            </div>
        </>
    );
}
