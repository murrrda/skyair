import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Plane = {
    id: number;
    reg_number: number;
    model: string;
};

type PageProps = {
    plane: Plane;
};

type ServiceStatus = 'pending' | 'in_progress' | 'finished';

type ServiceFormData = {
    started: string;
    ended: string;
    status: ServiceStatus;
    description: string;
    price: string;
    service_center: string;
};

const selectClass = 'h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';

function todayIso(): string {
    return new Date().toISOString().slice(0, 16);
}

export default function NoviServis() {
    const { props } = usePage<PageProps>();
    const { plane } = props;

    const { data, setData, post, processing, errors } = useForm<ServiceFormData>({
        started: todayIso(),
        ended: '',
        status: 'in_progress',
        description: '',
        price: '',
        service_center: '',
    });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        post(`/admin/flota/${plane.id}/servisi`);
    }

    return (
        <>
            <Head title={`Novi servis · ${plane.reg_number}`} />
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
                                href={`/admin/flota/${plane.id}/servisi`}
                                className="text-muted-foreground hover:text-foreground"
                            >
                                Servisi · {plane.reg_number}
                            </Link>
                            <span className="text-muted-foreground">/</span>
                            <span className="font-medium">Novi servis</span>
                        </div>
                    </div>
                </header>

                <main className="mx-auto w-full max-w-4xl flex-1 px-6 py-10">
                    <div className="mb-8">
                        <h1 className="text-2xl font-bold tracking-tight">Zakazivanje servisa</h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Avion: <span className="font-medium">{plane.reg_number}</span> · {plane.model}
                        </p>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-8">
                        <section className="rounded-xl border border-border bg-card p-6">
                            <h2 className="mb-5 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                Termin
                            </h2>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <Field label="Početak servisa *" error={errors.started}>
                                    <Input
                                        type="datetime-local"
                                        value={data.started}
                                        onChange={(e) => setData('started', e.target.value)}
                                        required
                                    />
                                </Field>
                                <Field label="Kraj servisa (opciono)" error={errors.ended}>
                                    <Input
                                        type="datetime-local"
                                        value={data.ended}
                                        onChange={(e) => setData('ended', e.target.value)}
                                    />
                                </Field>
                                <Field label="Status *" error={errors.status}>
                                    <select
                                        value={data.status}
                                        onChange={(e) => setData('status', e.target.value as ServiceStatus)}
                                        className={selectClass}
                                    >
                                        <option value="pending">Zakazan</option>
                                        <option value="in_progress">U toku</option>
                                        <option value="finished">Završen</option>
                                    </select>
                                </Field>
                            </div>
                        </section>

                        <section className="rounded-xl border border-border bg-card p-6">
                            <h2 className="mb-5 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                Detalji
                            </h2>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <Field label="Servisni centar *" error={errors.service_center}>
                                    <Input
                                        value={data.service_center}
                                        onChange={(e) => setData('service_center', e.target.value)}
                                        placeholder="npr. BEG hangar"
                                        required
                                    />
                                </Field>
                                <Field label="Cena (€) *" error={errors.price}>
                                    <Input
                                        type="number"
                                        step="0.01"
                                        value={data.price}
                                        onChange={(e) => setData('price', e.target.value)}
                                        min={0}
                                        required
                                    />
                                </Field>
                            </div>
                            <div className="mt-4">
                                <Field label="Opis (opciono)" error={errors.description}>
                                    <textarea
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        placeholder="Šta se radi na ovom servisu…"
                                        className="min-h-[100px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                    />
                                </Field>
                            </div>
                        </section>

                        <div className="flex items-center justify-end gap-2 border-t border-border pt-6">
                            <Button asChild variant="ghost">
                                <Link href={`/admin/flota/${plane.id}/servisi`}>Otkaži</Link>
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Čuvanje…' : 'Sačuvaj servis'}
                            </Button>
                        </div>
                    </form>
                </main>
            </div>
        </>
    );
}

function Field({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) {
    return (
        <div className="space-y-1.5">
            <Label className="text-xs font-medium">{label}</Label>
            {children}
            <InputError message={error} />
        </div>
    );
}
