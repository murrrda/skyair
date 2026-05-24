import { Head, Link, usePage } from '@inertiajs/react';
import { Plus, Wrench } from 'lucide-react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';

type ServiceStatus = 'pending' | 'in_progress' | 'finished';

type Service = {
    id: number;
    started: string | null;
    ended: string | null;
    status: ServiceStatus;
    description: string | null;
    price: number;
    service_center: string;
};

type Plane = {
    id: number;
    reg_number: number;
    model: string;
    status: 'in_garage' | 'in_flight' | 'in_service';
    model_year: number;
    total_mileage: number;
    repair_service_interval: number;
};

type PageProps = {
    plane: Plane;
    services: Service[];
    flash?: { success?: string; error?: string };
};

const statusMeta: Record<ServiceStatus, { label: string; className: string }> = {
    pending: { label: 'Zakazan', className: 'bg-[#fef3c7] text-[#a16207]' },
    in_progress: { label: 'U toku', className: 'bg-[#eef2ff] text-[#2152e0]' },
    finished: { label: 'Završen', className: 'bg-[#ecfdf5] text-[#059669]' },
};

const planeStatusMeta: Record<Plane['status'], { label: string; className: string }> = {
    in_garage: { label: 'U hangaru', className: 'bg-[#ecfdf5] text-[#059669]' },
    in_flight: { label: 'U letu', className: 'bg-[#eef2ff] text-[#2152e0]' },
    in_service: { label: 'Na servisu', className: 'bg-[#fef3c7] text-[#a16207]' },
};

function formatDate(iso: string | null): string {
    if (!iso) return '—';
    return new Date(iso).toLocaleDateString('sr-RS', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
}

function formatPrice(price: number): string {
    return `€${price.toLocaleString('sr-RS', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

export default function ServisiPlane() {
    const { props } = usePage<PageProps>();
    const { plane, services, flash } = props;

    useEffect(() => {
        if (flash?.success) toast.success(flash.success);
        if (flash?.error) toast.error(flash.error);
    }, [flash?.success, flash?.error]);

    const finished = services.filter((s) => s.status === 'finished');
    const ongoing = services.filter((s) => s.status !== 'finished');
    const totalSpent = finished.reduce((acc, s) => acc + s.price, 0);

    return (
        <>
            <Head title={`Servisi · ${plane.reg_number}`} />
            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <header className="w-full border-b border-border/60">
                    <div className="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-4">
                        <div className="flex items-center gap-3 text-sm">
                            <Link href="/admin" className="text-muted-foreground hover:text-foreground">
                                Admin panel
                            </Link>
                            <span className="text-muted-foreground">/</span>
                            <Link href="/admin/flota" className="text-muted-foreground hover:text-foreground">
                                Flota
                            </Link>
                            <span className="text-muted-foreground">/</span>
                            <span className="font-medium">Servisi · {plane.reg_number}</span>
                        </div>
                        <Button asChild>
                            <Link href={`/admin/flota/${plane.id}/servisi/novi`}>
                                <Plus className="mr-1 h-4 w-4" />
                                Zakaži servis
                            </Link>
                        </Button>
                    </div>
                </header>

                <main className="mx-auto w-full max-w-6xl flex-1 px-6 py-10">
                    <div className="mb-8 flex items-center gap-3">
                        <h1 className="text-2xl font-bold tracking-tight">{plane.reg_number}</h1>
                        <span className={`rounded-full px-2 py-0.5 text-[11px] font-medium ${planeStatusMeta[plane.status].className}`}>
                            {planeStatusMeta[plane.status].label}
                        </span>
                    </div>
                    <p className="-mt-6 mb-8 text-sm text-muted-foreground">
                        {plane.model} · {plane.model_year} · {plane.total_mileage.toLocaleString('sr-RS')} km · interval servisa: {plane.repair_service_interval} letnih sati
                    </p>

                    <div className="mb-8 grid gap-4 sm:grid-cols-3">
                        <Stat label="Aktivni servisi" value={String(ongoing.length)} sub={ongoing.length === 0 ? 'nema otvorenih' : 'zahtevaju pažnju'} />
                        <Stat label="Završeni servisi" value={String(finished.length)} sub={`${services.length} ukupno`} />
                        <Stat label="Ukupan trošak" value={formatPrice(totalSpent)} sub="kroz istoriju" />
                    </div>

                    <div className="overflow-hidden rounded-xl border border-border bg-card">
                        <div className="border-b border-border bg-muted/30 px-4 py-3 text-sm font-semibold">
                            Istorija servisa
                        </div>
                        {services.length === 0 ? (
                            <div className="flex flex-col items-center justify-center px-6 py-16 text-center">
                                <Wrench className="mb-3 h-10 w-10 text-muted-foreground" />
                                <p className="text-sm text-muted-foreground">
                                    Ovaj avion još uvek nema zakazanih servisa.
                                </p>
                            </div>
                        ) : (
                            <table className="w-full text-sm">
                                <thead className="border-b border-border bg-muted/20 text-xs uppercase text-muted-foreground">
                                    <tr>
                                        <th className="px-4 py-3 text-left font-medium">Početak</th>
                                        <th className="px-4 py-3 text-left font-medium">Kraj</th>
                                        <th className="px-4 py-3 text-left font-medium">Centar</th>
                                        <th className="px-4 py-3 text-right font-medium">Cena</th>
                                        <th className="px-4 py-3 text-center font-medium">Status</th>
                                        <th className="px-4 py-3 text-right font-medium"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {services.map((s) => (
                                        <tr key={s.id} className="border-b border-border/60 last:border-b-0 hover:bg-muted/30">
                                            <td className="px-4 py-3 font-medium">{formatDate(s.started)}</td>
                                            <td className="px-4 py-3 text-muted-foreground">{formatDate(s.ended)}</td>
                                            <td className="px-4 py-3">{s.service_center}</td>
                                            <td className="px-4 py-3 text-right">{formatPrice(s.price)}</td>
                                            <td className="px-4 py-3 text-center">
                                                <span className={`rounded-full px-2 py-0.5 text-[11px] font-medium ${statusMeta[s.status].className}`}>
                                                    {statusMeta[s.status].label}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                <Link
                                                    href={`/admin/servisi/${s.id}`}
                                                    className="text-xs font-medium text-primary hover:underline"
                                                >
                                                    Detalji →
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </div>
                </main>
            </div>
        </>
    );
}

function Stat({ label, value, sub }: { label: string; value: string; sub?: string }) {
    return (
        <div className="rounded-xl border border-border bg-card p-4">
            <div className="text-xs font-medium text-muted-foreground">{label}</div>
            <div className="mt-1 text-2xl font-bold tracking-tight">{value}</div>
            {sub && <div className="mt-0.5 text-xs text-muted-foreground">{sub}</div>}
        </div>
    );
}
