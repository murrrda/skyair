import { Head, Link, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Airport = {
    id: number;
    iata_code: string;
    label: string;
};

type Flight = {
    id: number;
    dep_code: string;
    dep_city: string;
    arr_code: string;
    arr_city: string;
    plane_model: string;
    plane_reg: string;
    takeoff_formatted: string;
    status: string;
};

type FormData = {
    airport_id: string;
    reason_type: string;
    description: string;
    seriousness_level: string;
};

const reasonTypes = [
    { value: 'malfunction', label: 'Kvar aviona' },
    { value: 'weather', label: 'Vremenske nepogode' },
    { value: 'medical', label: 'Medicinski razlog' },
    { value: 'security', label: 'Bezbednosna pretnja' },
    { value: 'other', label: 'Ostalo' },
];

type PageProps = {
    flight: Flight;
    airports: Airport[];
};

export default function PrinudnoSletanje() {
    const { flight, airports } = usePage<PageProps>().props;
    const { data, setData, post, processing, errors } = useForm<FormData>({
        airport_id: '',
        reason_type: 'malfunction',
        description: '',
        seriousness_level: '3',
    });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        post(`/dispatcher/letovi/${flight.id}/prinudno-sletanje`);
    }

    return (
        <>
            <Head title="Prinudno sletanje" />
            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <header className="w-full border-b border-border/60">
                    <div className="mx-auto flex w-full max-w-4xl items-center justify-between px-6 py-4">
                        <div className="flex items-center gap-3 text-sm">
                            <Link href="/dispatcher" className="text-muted-foreground hover:text-foreground">
                                Upravljanje letovima
                            </Link>
                            <span className="text-muted-foreground">/</span>
                            <span className="font-medium text-red-600">Prinudno sletanje</span>
                        </div>
                    </div>
                </header>

                <main className="mx-auto w-full max-w-4xl flex-1 px-6 py-10">
                    <div className="mb-8">
                        <h1 className="text-2xl font-bold tracking-tight text-red-700 dark:text-red-400">
                            Prinudno sletanje
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Let {flight.dep_code} → {flight.arr_code} · {flight.takeoff_formatted} · {flight.plane_model} (reg. {flight.plane_reg})
                        </p>
                    </div>

                    <div className="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-950/30 dark:text-red-300">
                        Izdavanje naloga za prinudno sletanje će odmah promeniti status leta.
                        {data.reason_type === 'malfunction' && ' Avion će biti označen za servis.'}
                    </div>

                    <form onSubmit={handleSubmit}>
                        <div className="space-y-8">
                            <section className="rounded-xl border border-border bg-card p-6">
                                <h2 className="mb-5 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                    Aerodrom za sletanje
                                </h2>
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-medium">Izaberite aerodrom *</Label>
                                    <select
                                        value={data.airport_id}
                                        onChange={(e) => setData('airport_id', e.target.value)}
                                        className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                        required
                                    >
                                        <option value="">Izaberite aerodrom</option>
                                        {airports.map((a) => (
                                            <option key={a.id} value={a.id}>
                                                {a.label}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.airport_id} />
                                </div>
                            </section>

                            <section className="rounded-xl border border-border bg-card p-6">
                                <h2 className="mb-5 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                    Detalji prinudnog sletanja
                                </h2>
                                <div className="space-y-4">
                                    <div className="space-y-1.5">
                                        <Label className="text-xs font-medium">Razlog *</Label>
                                        <select
                                            value={data.reason_type}
                                            onChange={(e) => setData('reason_type', e.target.value)}
                                            className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                            required
                                        >
                                            {reasonTypes.map((r) => (
                                                <option key={r.value} value={r.value}>
                                                    {r.label}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={errors.reason_type} />
                                    </div>
                                    <div className="space-y-1.5">
                                        <Label className="text-xs font-medium">Ozbiljnost situacije (1–5) *</Label>
                                        <Input
                                            type="number"
                                            value={data.seriousness_level}
                                            onChange={(e) => setData('seriousness_level', e.target.value)}
                                            min={1}
                                            max={5}
                                            required
                                        />
                                        <p className="text-xs text-muted-foreground">1 = blag, 5 = kritičan</p>
                                        <InputError message={errors.seriousness_level} />
                                    </div>
                                    <div className="space-y-1.5">
                                        <Label className="text-xs font-medium">Opis situacije *</Label>
                                        <textarea
                                            value={data.description}
                                            onChange={(e) => setData('description', e.target.value)}
                                            className="min-h-[120px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                            placeholder="Opišite razlog za prinudno sletanje…"
                                            required
                                        />
                                        <InputError message={errors.description} />
                                    </div>
                                </div>
                            </section>
                        </div>

                        <div className="mt-6 flex items-center justify-end gap-2 border-t border-border pt-6">
                            <Button asChild variant="ghost">
                                <Link href="/dispatcher">Otkaži</Link>
                            </Button>
                            <Button type="submit" variant="destructive" disabled={processing}>
                                {processing ? 'Slanje naloga…' : 'Izdaj nalog za prinudno sletanje'}
                            </Button>
                        </div>
                    </form>
                </main>
            </div>
        </>
    );
}
