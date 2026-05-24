import { Head, Link, router, usePage } from '@inertiajs/react';
import { Pencil, Plane as PlaneIcon, Plus, Trash2, Wrench } from 'lucide-react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';

type PlaneStatus = 'in_garage' | 'in_flight' | 'in_service';

type Plane = {
    id: number;
    reg_number: number;
    model: string;
    capacity: number;
    luxury_level: number;
    range_km: number;
    max_speed: number;
    repair_service_interval: number;
    model_year: number;
    status: PlaneStatus;
    commissioned_at: string | null;
    total_mileage: number;
};

type PageProps = {
    planes: Plane[];
    flash?: { success?: string; error?: string };
};

const statusMeta: Record<PlaneStatus, { label: string; className: string }> = {
    in_garage: {
        label: 'U hangaru',
        className: 'bg-[#ecfdf5] text-[#059669]',
    },
    in_flight: {
        label: 'U letu',
        className: 'bg-[#eef2ff] text-[#2152e0]',
    },
    in_service: {
        label: 'Na servisu',
        className: 'bg-[#fef3c7] text-[#a16207]',
    },
};

function formatNumber(n: number): string {
    return n.toLocaleString('sr-RS');
}

export default function FlotaIndex() {
    const { props } = usePage<PageProps>();
    const { planes, flash } = props;

    useEffect(() => {
        if (flash?.success) {
            toast.success(flash.success);
        }
        if (flash?.error) {
            toast.error(flash.error);
        }
    }, [flash?.success, flash?.error]);

    function handleDelete(plane: Plane) {
        if (!confirm(`Da li ste sigurni da želite da obrišete avion ${plane.reg_number} (${plane.model})?`)) {
            return;
        }
        router.delete(`/admin/flota/${plane.id}`, { preserveScroll: true });
    }

    return (
        <>
            <Head title="Flota" />
            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <header className="w-full border-b border-border/60">
                    <div className="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-4">
                        <div className="flex items-center gap-3">
                            <Link href="/admin" className="text-sm text-muted-foreground hover:text-foreground">
                                ← Admin panel
                            </Link>
                            <span className="text-muted-foreground">/</span>
                            <span className="text-sm font-medium">Flota</span>
                        </div>
                        <nav className="flex items-center gap-2">
                            <Button asChild>
                                <Link href="/admin/flota/novi">
                                    <Plus className="mr-1 h-4 w-4" />
                                    Dodaj avion
                                </Link>
                            </Button>
                        </nav>
                    </div>
                </header>

                <main className="mx-auto w-full max-w-6xl flex-1 px-6 py-10">
                    <div className="mb-8 flex items-end justify-between">
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight">Flota</h1>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {planes.length === 0
                                    ? 'Još uvek nema aviona u floti.'
                                    : `${planes.length} avion${planes.length === 1 ? '' : planes.length < 5 ? 'a' : 'a'} u floti`}
                            </p>
                        </div>
                    </div>

                    {planes.length === 0 ? (
                        <div className="flex flex-col items-center justify-center rounded-xl border border-dashed border-border bg-card/50 px-6 py-20 text-center">
                            <PlaneIcon className="mb-3 h-10 w-10 text-muted-foreground" />
                            <h2 className="text-base font-semibold">Flota je prazna</h2>
                            <p className="mt-1 mb-6 max-w-sm text-sm text-muted-foreground">
                                Kliknite "Dodaj avion" da unesete prvi avion u flotu.
                            </p>
                            <Button asChild>
                                <Link href="/admin/flota/novi">
                                    <Plus className="mr-1 h-4 w-4" />
                                    Dodaj avion
                                </Link>
                            </Button>
                        </div>
                    ) : (
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {planes.map((plane) => (
                                <div
                                    key={plane.id}
                                    className="rounded-xl border border-border bg-card p-5 shadow-sm"
                                >
                                    <div className="mb-3 flex items-start justify-between">
                                        <div>
                                            <div className="text-xl font-bold tracking-tight">
                                                {plane.reg_number}
                                            </div>
                                            <div className="text-xs text-muted-foreground">
                                                {plane.model}
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <span
                                                className={`rounded-full px-2 py-0.5 text-[11px] font-medium ${statusMeta[plane.status].className}`}
                                            >
                                                {statusMeta[plane.status].label}
                                            </span>
                                            <Link
                                                href={`/admin/flota/${plane.id}/servisi`}
                                                className="rounded-md p-1.5 text-muted-foreground transition hover:bg-accent hover:text-foreground"
                                                aria-label={`Servisi za avion ${plane.reg_number}`}
                                            >
                                                <Wrench className="h-4 w-4" />
                                            </Link>
                                            <Link
                                                href={`/admin/flota/${plane.id}/uredi`}
                                                className="rounded-md p-1.5 text-muted-foreground transition hover:bg-accent hover:text-foreground"
                                                aria-label={`Uredi avion ${plane.reg_number}`}
                                            >
                                                <Pencil className="h-4 w-4" />
                                            </Link>
                                            <button
                                                type="button"
                                                onClick={() => handleDelete(plane)}
                                                className="rounded-md p-1.5 text-muted-foreground transition hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/30 dark:hover:text-red-400"
                                                aria-label={`Obriši avion ${plane.reg_number}`}
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </button>
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-2 gap-3 border-t border-border pt-3 text-xs">
                                        <div>
                                            <div className="text-muted-foreground">Kapacitet</div>
                                            <div className="font-semibold">{plane.capacity} mesta</div>
                                        </div>
                                        <div>
                                            <div className="text-muted-foreground">Dolet</div>
                                            <div className="font-semibold">{formatNumber(plane.range_km)} km</div>
                                        </div>
                                        <div>
                                            <div className="text-muted-foreground">Godina</div>
                                            <div className="font-semibold">{plane.model_year}</div>
                                        </div>
                                        <div>
                                            <div className="text-muted-foreground">Ukupno km</div>
                                            <div className="font-semibold">{formatNumber(plane.total_mileage)}</div>
                                        </div>
                                    </div>
                                    <div className="mt-3 flex items-center justify-between border-t border-border pt-3 text-xs">
                                        <span className="text-muted-foreground">Sledeći servis za</span>
                                        <span className="font-semibold">{formatNumber(plane.repair_service_interval)} letnih sati</span>
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
