import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import type { FormEvent} from 'react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type RouteOption = {
    id: number;
    name: string;
    label: string;
    estimated_time: number;
};

type FlightTemplate = {
    id: number;
    name: string;
    route_id: number;
    route_label: string;
    departure_time: string;
    duration_minutes: number;
    min_capacity: number | null;
    luxury_level: number | null;
    notes: string | null;
};

type TemplateFormData = {
    name: string;
    route_id: string;
    departure_time: string;
    duration_minutes: string;
    min_capacity: string;
    luxury_level: string;
    notes: string;
};

type PageProps = {
    templates: FlightTemplate[];
    routes: RouteOption[];
    flash?: { success?: string; error?: string };
};

const emptyForm: TemplateFormData = {
    name: '',
    route_id: '',
    departure_time: '',
    duration_minutes: '',
    min_capacity: '',
    luxury_level: '',
    notes: '',
};

export default function Sabloni() {
    const { templates, routes, flash } = usePage<PageProps>().props;
    const [showForm, setShowForm] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);

    const { data, setData, post, patch, processing, errors, reset } = useForm<TemplateFormData>(emptyForm);

    useEffect(() => {
        if (flash?.success) {
toast.success(flash.success);
}

        if (flash?.error) {
toast.error(flash.error);
}
    }, [flash?.success, flash?.error]);

    function handleCreate() {
        setEditingId(null);
        reset();
        setShowForm(true);
    }

    function handleEdit(t: FlightTemplate) {
        setEditingId(t.id);
        setData({
            name: t.name,
            route_id: String(t.route_id),
            departure_time: t.departure_time,
            duration_minutes: String(t.duration_minutes),
            min_capacity: t.min_capacity ? String(t.min_capacity) : '',
            luxury_level: t.luxury_level ? String(t.luxury_level) : '',
            notes: t.notes ?? '',
        });
        setShowForm(true);
    }

    function handleSubmit(e: FormEvent) {
        e.preventDefault();

        if (editingId) {
            patch(`/dispatcher/sabloni/${editingId}`, {
                onSuccess: () => {
                    setShowForm(false);
                    setEditingId(null);
                },
            });
        } else {
            post('/dispatcher/sabloni', {
                onSuccess: () => {
                    setShowForm(false);
                    reset();
                },
            });
        }
    }

    function handleDelete(t: FlightTemplate) {
        if (!confirm(`Da li ste sigurni da želite da obrišete šablon "${t.name}"?`)) {
return;
}

        router.delete(`/dispatcher/sabloni/${t.id}`, { preserveScroll: true });
    }

    function handleUseTemplate(t: FlightTemplate) {
        const today = new Date();
        const [hours, minutes] = t.departure_time.split(':').map(Number);
        today.setHours(hours, minutes, 0, 0);

        if (today < new Date()) {
today.setDate(today.getDate() + 1);
}

        const arrival = new Date(today.getTime() + t.duration_minutes * 60000);
        const pad = (n: number) => String(n).padStart(2, '0');
        const fmt = (d: Date) =>
            `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;

        const params = new URLSearchParams({
            route_id: String(t.route_id),
            expected_takeoff: fmt(today),
            expected_arrival: fmt(arrival),
        });
        router.get(`/dispatcher/zakazivanje-leta?${params.toString()}`);
    }

    return (
        <>
            <Head title="Šabloni letova" />
            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <header className="w-full border-b border-border/60">
                    <div className="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-4">
                        <div className="flex items-center gap-3 text-sm">
                            <Link href="/dispatcher" className="text-muted-foreground hover:text-foreground">
                                Upravljanje letovima
                            </Link>
                            <span className="text-muted-foreground">/</span>
                            <span className="font-medium">Šabloni letova</span>
                        </div>
                        <Button onClick={handleCreate}>
                            <Plus className="mr-1 h-4 w-4" />
                            Novi šablon
                        </Button>
                    </div>
                </header>

                <main className="mx-auto w-full max-w-6xl flex-1 px-6 py-10">
                    <div className="mb-8">
                        <h1 className="text-2xl font-bold tracking-tight">Šabloni letova</h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Sačuvajte česte letove kao šablone za brzo zakazivanje.
                        </p>
                    </div>

                    {showForm && (
                        <div className="mb-8 rounded-xl border border-border bg-card p-6">
                            <h2 className="mb-5 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                {editingId ? 'Izmena šablona' : 'Novi šablon'}
                            </h2>
                            <form onSubmit={handleSubmit}>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <Field label="Naziv šablona *" error={errors.name}>
                                        <Input
                                            value={data.name}
                                            onChange={(e) => setData('name', e.target.value)}
                                            placeholder="npr. Jutarnji BEG-TGD"
                                            required
                                        />
                                    </Field>
                                    <Field label="Ruta *" error={errors.route_id}>
                                        <select
                                            value={data.route_id}
                                            onChange={(e) => {
                                                setData('route_id', e.target.value);
                                                const route = routes.find((r) => r.id === Number(e.target.value));

                                                if (route) {
setData('duration_minutes', String(route.estimated_time));
}
                                            }}
                                            className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                            required
                                        >
                                            <option value="">Izaberite rutu</option>
                                            {routes.map((r) => (
                                                <option key={r.id} value={r.id}>
                                                    {r.label}
                                                </option>
                                            ))}
                                        </select>
                                    </Field>
                                    <Field label="Vreme polaska *" error={errors.departure_time}>
                                        <Input
                                            type="time"
                                            value={data.departure_time}
                                            onChange={(e) => setData('departure_time', e.target.value)}
                                            required
                                        />
                                    </Field>
                                    <Field label="Trajanje (minuta) *" error={errors.duration_minutes}>
                                        <Input
                                            type="number"
                                            value={data.duration_minutes}
                                            onChange={(e) => setData('duration_minutes', e.target.value)}
                                            min={1}
                                            required
                                        />
                                    </Field>
                                    <Field label="Min. kapacitet" error={errors.min_capacity}>
                                        <Input
                                            type="number"
                                            value={data.min_capacity}
                                            onChange={(e) => setData('min_capacity', e.target.value)}
                                            min={1}
                                        />
                                    </Field>
                                    <Field label="Nivo luksuza (1–5)" error={errors.luxury_level}>
                                        <Input
                                            type="number"
                                            value={data.luxury_level}
                                            onChange={(e) => setData('luxury_level', e.target.value)}
                                            min={1}
                                            max={5}
                                        />
                                    </Field>
                                    <div className="space-y-1.5 sm:col-span-2">
                                        <Label className="text-xs font-medium">Napomena</Label>
                                        <Input
                                            value={data.notes}
                                            onChange={(e) => setData('notes', e.target.value)}
                                            placeholder="Opciona napomena"
                                        />
                                        <InputError message={errors.notes} />
                                    </div>
                                </div>
                                <div className="mt-4 flex items-center justify-end gap-2">
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        onClick={() => {
                                            setShowForm(false);
                                            setEditingId(null);
                                        }}
                                    >
                                        Otkaži
                                    </Button>
                                    <Button type="submit" disabled={processing}>
                                        {processing ? 'Čuvanje…' : editingId ? 'Sačuvaj izmene' : 'Sačuvaj šablon'}
                                    </Button>
                                </div>
                            </form>
                        </div>
                    )}

                    {templates.length === 0 && !showForm ? (
                        <div className="flex flex-col items-center justify-center rounded-xl border border-dashed border-border bg-card/50 px-6 py-20 text-center">
                            <h2 className="text-base font-semibold">Nema šablona</h2>
                            <p className="mt-1 mb-6 max-w-sm text-sm text-muted-foreground">
                                Kreirajte šablon za letove koje često zakazujete.
                            </p>
                            <Button onClick={handleCreate}>
                                <Plus className="mr-1 h-4 w-4" />
                                Novi šablon
                            </Button>
                        </div>
                    ) : (
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {templates.map((t) => (
                                <div key={t.id} className="rounded-xl border border-border bg-card p-5 shadow-sm">
                                    <div className="mb-3 flex items-start justify-between">
                                        <div>
                                            <div className="text-base font-bold tracking-tight">{t.name}</div>
                                            <div className="text-xs text-muted-foreground">{t.route_label}</div>
                                        </div>
                                        <div className="flex items-center gap-1">
                                            <button
                                                type="button"
                                                onClick={() => handleEdit(t)}
                                                className="rounded-md p-1.5 text-muted-foreground transition hover:bg-accent hover:text-foreground"
                                            >
                                                <Pencil className="h-4 w-4" />
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => handleDelete(t)}
                                                className="rounded-md p-1.5 text-muted-foreground transition hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/30 dark:hover:text-red-400"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </button>
                                        </div>
                                    </div>
                                    <div className="grid grid-cols-2 gap-3 border-t border-border pt-3 text-xs">
                                        <div>
                                            <div className="text-muted-foreground">Polazak</div>
                                            <div className="font-semibold">{t.departure_time}</div>
                                        </div>
                                        <div>
                                            <div className="text-muted-foreground">Trajanje</div>
                                            <div className="font-semibold">
                                                {Math.floor(t.duration_minutes / 60)}h {t.duration_minutes % 60}m
                                            </div>
                                        </div>
                                        {t.min_capacity && (
                                            <div>
                                                <div className="text-muted-foreground">Min. kapacitet</div>
                                                <div className="font-semibold">{t.min_capacity}</div>
                                            </div>
                                        )}
                                        {t.luxury_level && (
                                            <div>
                                                <div className="text-muted-foreground">Luksuz</div>
                                                <div className="font-semibold">Nivo {t.luxury_level}</div>
                                            </div>
                                        )}
                                    </div>
                                    {t.notes && (
                                        <div className="mt-3 border-t border-border pt-3 text-xs text-muted-foreground">
                                            {t.notes}
                                        </div>
                                    )}
                                    <div className="mt-3 border-t border-border pt-3">
                                        <Button
                                            size="sm"
                                            className="w-full"
                                            onClick={() => handleUseTemplate(t)}
                                        >
                                            Zakaži let iz šablona
                                        </Button>
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

function Field({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) {
    return (
        <div className="space-y-1.5">
            <Label className="text-xs font-medium">{label}</Label>
            {children}
            <InputError message={error} />
        </div>
    );
}
