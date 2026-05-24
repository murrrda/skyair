import { Head, Link, router, usePage } from '@inertiajs/react';
import { CheckCircle2 } from 'lucide-react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';

type ServiceStatus = 'pending' | 'in_progress' | 'finished';

type Service = {
    id: number;
    plane_id: number;
    started: string | null;
    ended: string | null;
    status: ServiceStatus;
    description: string | null;
    price: number;
    service_center: string;
    admin_name: string | null;
    plane: {
        id: number;
        reg_number: number;
        model: string;
    };
};

type PageProps = {
    service: Service;
    flash?: { success?: string; error?: string };
};

const statusMeta: Record<ServiceStatus, { label: string; className: string }> = {
    pending: { label: 'Zakazan', className: 'bg-[#fef3c7] text-[#a16207]' },
    in_progress: { label: 'U toku', className: 'bg-[#eef2ff] text-[#2152e0]' },
    finished: { label: 'Završen', className: 'bg-[#ecfdf5] text-[#059669]' },
};

function formatDateTime(iso: string | null): string {
    if (!iso) return '—';
    return new Date(iso).toLocaleString('sr-RS', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function formatPrice(price: number): string {
    return `€${price.toLocaleString('sr-RS', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

export default function ServisDetalji() {
    const { props } = usePage<PageProps>();
    const { service, flash } = props;

    useEffect(() => {
        if (flash?.success) toast.success(flash.success);
        if (flash?.error) toast.error(flash.error);
    }, [flash?.success, flash?.error]);

    function handleComplete() {
        if (!confirm('Da li ste sigurni da želite da označite servis kao završen? Avion će biti vraćen u hangar.')) {
            return;
        }
        router.post(`/admin/servisi/${service.id}/zavrsi`, {}, { preserveScroll: true });
    }

    return (
        <>
            <Head title={`Servis #${service.id}`} />
            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <header className="w-full border-b border-border/60">
                    <div className="mx-auto flex w-full max-w-4xl items-center justify-between px-6 py-4">
                        <div className="flex items-center gap-3 text-sm">
                            <Link href="/admin" className="text-muted-foreground hover:text-foreground">
                                Admin panel
                            </Link>
                            <span className="text-muted-foreground">/</span>
                            <Link href="/admin/flota" className="text-muted-foreground hover:text-foreground">
                                Flota
                            </Link>
                            <span className="text-muted-foreground">/</span>
                            <Link
                                href={`/admin/flota/${service.plane.id}/servisi`}
                                className="text-muted-foreground hover:text-foreground"
                            >
                                Servisi · {service.plane.reg_number}
                            </Link>
                            <span className="text-muted-foreground">/</span>
                            <span className="font-medium">Servis #{service.id}</span>
                        </div>
                    </div>
                </header>

                <main className="mx-auto w-full max-w-4xl flex-1 px-6 py-10">
                    <div className="mb-8 flex items-start justify-between">
                        <div>
                            <div className="flex items-center gap-3">
                                <h1 className="text-2xl font-bold tracking-tight">Servis #{service.id}</h1>
                                <span className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${statusMeta[service.status].className}`}>
                                    {statusMeta[service.status].label}
                                </span>
                            </div>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Avion: <span className="font-medium">{service.plane.reg_number}</span> · {service.plane.model}
                            </p>
                        </div>
                        {service.status !== 'finished' && (
                            <Button onClick={handleComplete}>
                                <CheckCircle2 className="mr-1 h-4 w-4" />
                                Označi kao završen
                            </Button>
                        )}
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <DetailCard label="Početak" value={formatDateTime(service.started)} />
                        <DetailCard label="Kraj" value={formatDateTime(service.ended)} />
                        <DetailCard label="Servisni centar" value={service.service_center} />
                        <DetailCard label="Cena" value={formatPrice(service.price)} />
                        <DetailCard label="Naručio" value={service.admin_name ?? '—'} />
                        <DetailCard label="Status" value={statusMeta[service.status].label} />
                    </div>

                    {service.description && (
                        <div className="mt-6 rounded-xl border border-border bg-card p-6">
                            <h2 className="mb-3 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                Opis
                            </h2>
                            <p className="whitespace-pre-line text-sm leading-relaxed">
                                {service.description}
                            </p>
                        </div>
                    )}
                </main>
            </div>
        </>
    );
}

function DetailCard({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-xl border border-border bg-card p-4">
            <div className="text-xs font-medium text-muted-foreground">{label}</div>
            <div className="mt-1 text-base font-semibold">{value}</div>
        </div>
    );
}
