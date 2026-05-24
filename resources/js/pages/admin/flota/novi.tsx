import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type FormData = {
    reg_number: string;
    model: string;
    capacity: string;
    luxury_level: string;
    range_km: string;
    max_speed: string;
    repair_service_interval: string;
    model_year: string;
    status: 'in_garage' | 'in_flight' | 'in_service';
    commissioned_at: string;
    total_mileage: string;
};

export default function NoviAvion() {
    const { data, setData, post, processing, errors } = useForm<FormData>({
        reg_number: '',
        model: '',
        capacity: '',
        luxury_level: '3',
        range_km: '',
        max_speed: '',
        repair_service_interval: '',
        model_year: String(new Date().getFullYear()),
        status: 'in_garage',
        commissioned_at: '',
        total_mileage: '0',
    });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        post('/admin/flota');
    }

    return (
        <>
            <Head title="Novi avion" />
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
                            <span className="font-medium">Novi avion</span>
                        </div>
                    </div>
                </header>

                <main className="mx-auto w-full max-w-4xl flex-1 px-6 py-10">
                    <div className="mb-8">
                        <h1 className="text-2xl font-bold tracking-tight">Dodavanje aviona u flotu</h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Sva polja označena * su obavezna.
                        </p>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-8">
                        <section className="rounded-xl border border-border bg-card p-6">
                            <h2 className="mb-5 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                Identifikacija
                            </h2>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <Field label="Registracioni broj *" error={errors.reg_number}>
                                    <Input
                                        type="number"
                                        value={data.reg_number}
                                        onChange={(e) => setData('reg_number', e.target.value)}
                                        placeholder="npr. 12345"
                                        required
                                    />
                                </Field>
                                <Field label="Model *" error={errors.model}>
                                    <Input
                                        value={data.model}
                                        onChange={(e) => setData('model', e.target.value)}
                                        placeholder="npr. Boeing 737-800"
                                        required
                                    />
                                </Field>
                                <Field label="Godina proizvodnje *" error={errors.model_year}>
                                    <Input
                                        type="number"
                                        value={data.model_year}
                                        onChange={(e) => setData('model_year', e.target.value)}
                                        min={1950}
                                        max={new Date().getFullYear() + 2}
                                        required
                                    />
                                </Field>
                                <Field label="Datum uvođenja u flotu" error={errors.commissioned_at}>
                                    <Input
                                        type="date"
                                        value={data.commissioned_at}
                                        onChange={(e) => setData('commissioned_at', e.target.value)}
                                    />
                                </Field>
                            </div>
                        </section>

                        <section className="rounded-xl border border-border bg-card p-6">
                            <h2 className="mb-5 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                Tehničke karakteristike
                            </h2>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <Field label="Kapacitet (broj sedišta) *" error={errors.capacity}>
                                    <Input
                                        type="number"
                                        value={data.capacity}
                                        onChange={(e) => setData('capacity', e.target.value)}
                                        min={1}
                                        max={1000}
                                        required
                                    />
                                </Field>
                                <Field label="Nivo luksuza (1–5) *" error={errors.luxury_level}>
                                    <Input
                                        type="number"
                                        value={data.luxury_level}
                                        onChange={(e) => setData('luxury_level', e.target.value)}
                                        min={1}
                                        max={5}
                                        required
                                    />
                                </Field>
                                <Field label="Dolet (km) *" error={errors.range_km}>
                                    <Input
                                        type="number"
                                        value={data.range_km}
                                        onChange={(e) => setData('range_km', e.target.value)}
                                        min={1}
                                        required
                                    />
                                </Field>
                                <Field label="Maksimalna brzina (km/h) *" error={errors.max_speed}>
                                    <Input
                                        type="number"
                                        value={data.max_speed}
                                        onChange={(e) => setData('max_speed', e.target.value)}
                                        min={1}
                                        required
                                    />
                                </Field>
                                <Field
                                    label="Interval servisa (letnih sati) *"
                                    error={errors.repair_service_interval}
                                >
                                    <Input
                                        type="number"
                                        value={data.repair_service_interval}
                                        onChange={(e) => setData('repair_service_interval', e.target.value)}
                                        min={1}
                                        required
                                    />
                                </Field>
                                <Field label="Ukupna kilometraža" error={errors.total_mileage}>
                                    <Input
                                        type="number"
                                        value={data.total_mileage}
                                        onChange={(e) => setData('total_mileage', e.target.value)}
                                        min={0}
                                    />
                                </Field>
                            </div>
                        </section>

                        <section className="rounded-xl border border-border bg-card p-6">
                            <h2 className="mb-5 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                Status
                            </h2>
                            <Field label="Trenutni status *" error={errors.status}>
                                <Select
                                    value={data.status}
                                    onValueChange={(v) => setData('status', v as FormData['status'])}
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="in_garage">U hangaru</SelectItem>
                                        <SelectItem value="in_flight">U letu</SelectItem>
                                        <SelectItem value="in_service">Na servisu</SelectItem>
                                    </SelectContent>
                                </Select>
                            </Field>
                        </section>

                        <div className="flex items-center justify-end gap-2 border-t border-border pt-6">
                            <Button asChild variant="ghost">
                                <Link href="/admin/flota">Otkaži</Link>
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Čuvanje…' : 'Sačuvaj avion'}
                            </Button>
                        </div>
                    </form>
                </main>
            </div>
        </>
    );
}

function Field({
    label,
    error,
    children,
}: {
    label: string;
    error?: string;
    children: React.ReactNode;
}) {
    return (
        <div className="space-y-1.5">
            <Label className="text-xs font-medium">{label}</Label>
            {children}
            <InputError message={error} />
        </div>
    );
}
