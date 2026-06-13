import { Head, Link, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

type AvailablePlane = {
    id: number;
    reg_number: number;
    model: string;
    capacity: number;
    luxury_level: number;
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
    arrival_formatted: string;
    status: string;
};

type FormData = {
    new_plane_id: string;
    reason: string;
};

type PageProps = {
    flight: Flight;
    availablePlanes: AvailablePlane[];
};

export default function ZamenaAviona() {
    const { flight, availablePlanes } = usePage<PageProps>().props;
    const { data, setData, post, processing, errors } = useForm<FormData>({
        new_plane_id: '',
        reason: '',
    });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        post(`/dispatcher/letovi/${flight.id}/zamena-aviona`);
    }

    return (
        <>
            <Head title="Zamena aviona" />
            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <header className="w-full border-b border-border/60">
                    <div className="mx-auto flex w-full max-w-4xl items-center justify-between px-6 py-4">
                        <div className="flex items-center gap-3 text-sm">
                            <Link href="/dispatcher" className="text-muted-foreground hover:text-foreground">
                                Upravljanje letovima
                            </Link>
                            <span className="text-muted-foreground">/</span>
                            <span className="font-medium">Zamena aviona</span>
                        </div>
                    </div>
                </header>

                <main className="mx-auto w-full max-w-4xl flex-1 px-6 py-10">
                    <div className="mb-8">
                        <h1 className="text-2xl font-bold tracking-tight">Zamena aviona</h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Let {flight.dep_code} → {flight.arr_code} · {flight.takeoff_formatted}
                        </p>
                    </div>

                    <section className="mb-8 rounded-xl border border-border bg-card p-6">
                        <h2 className="mb-4 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                            Trenutni avion (prizemljen)
                        </h2>
                        <div className="rounded-lg bg-red-50 p-4 text-sm dark:bg-red-950/30">
                            <span className="font-semibold">{flight.plane_model}</span>
                            <span className="ml-2 text-muted-foreground">(reg. {flight.plane_reg})</span>
                            <span className="ml-3 rounded-full bg-red-100 px-2 py-0.5 text-[11px] font-medium text-red-700 dark:bg-red-900 dark:text-red-300">
                                Van funkcije
                            </span>
                        </div>
                    </section>

                    <form onSubmit={handleSubmit}>
                        <div className="space-y-8">
                            <section className="rounded-xl border border-border bg-card p-6">
                                <h2 className="mb-5 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                    Izaberite zamenu
                                </h2>
                                {availablePlanes.length === 0 ? (
                                    <p className="text-sm text-amber-600">
                                        Nema dostupnih aviona za zamenu. Svi avioni su zauzeti ili na servisu.
                                    </p>
                                ) : (
                                    <div className="space-y-2">
                                        {availablePlanes.map((plane) => (
                                            <label
                                                key={plane.id}
                                                className={`flex cursor-pointer items-center gap-4 rounded-lg border p-4 transition ${
                                                    data.new_plane_id === String(plane.id)
                                                        ? 'border-primary bg-primary/5'
                                                        : 'border-border hover:bg-accent/50'
                                                }`}
                                            >
                                                <input
                                                    type="radio"
                                                    name="new_plane_id"
                                                    value={plane.id}
                                                    checked={data.new_plane_id === String(plane.id)}
                                                    onChange={() => setData('new_plane_id', String(plane.id))}
                                                    className="accent-primary"
                                                />
                                                <div className="flex-1">
                                                    <div className="flex items-center gap-2">
                                                        <span className="font-semibold">{plane.reg_number}</span>
                                                        <span className="text-sm text-muted-foreground">{plane.model}</span>
                                                    </div>
                                                    <div className="mt-1 text-xs text-muted-foreground">
                                                        Kapacitet: {plane.capacity} · Luksuz: nivo {plane.luxury_level}
                                                    </div>
                                                </div>
                                            </label>
                                        ))}
                                    </div>
                                )}
                                <InputError message={errors.new_plane_id} />
                            </section>

                            <section className="rounded-xl border border-border bg-card p-6">
                                <h2 className="mb-5 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                    Razlog zamene
                                </h2>
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-medium">Razlog *</Label>
                                    <textarea
                                        value={data.reason}
                                        onChange={(e) => setData('reason', e.target.value)}
                                        className="min-h-[100px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                        placeholder="Opišite razlog zamene aviona…"
                                        required
                                    />
                                    <InputError message={errors.reason} />
                                </div>
                            </section>
                        </div>

                        <div className="mt-6 flex items-center justify-end gap-2 border-t border-border pt-6">
                            <Button asChild variant="ghost">
                                <Link href="/dispatcher">Otkaži</Link>
                            </Button>
                            <Button type="submit" disabled={processing || availablePlanes.length === 0}>
                                {processing ? 'Zamena u toku…' : 'Potvrdi zamenu aviona'}
                            </Button>
                        </div>
                    </form>
                </main>
            </div>
        </>
    );
}
