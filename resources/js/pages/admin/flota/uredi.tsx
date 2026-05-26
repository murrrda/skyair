import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { PlaneFormData, PlaneFormFields, PlaneStatus } from './plane-form-fields';

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
    plane: Plane;
};

export default function UrediAvion() {
    const { props } = usePage<PageProps>();
    const { plane } = props;

    const { data, setData, patch, processing, errors } = useForm<PlaneFormData>({
        reg_number: String(plane.reg_number),
        model: plane.model,
        capacity: String(plane.capacity),
        luxury_level: String(plane.luxury_level),
        range_km: String(plane.range_km),
        max_speed: String(plane.max_speed),
        repair_service_interval: String(plane.repair_service_interval),
        model_year: String(plane.model_year),
        status: plane.status,
        commissioned_at: plane.commissioned_at ?? '',
        total_mileage: String(plane.total_mileage),
    });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        patch(`/admin/flota/${plane.id}`);
    }

    return (
        <>
            <Head title={`Uredi avion #${plane.reg_number}`} />
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
                            <span className="font-medium">Uredi avion #{plane.reg_number}</span>
                        </div>
                    </div>
                </header>

                <main className="mx-auto w-full max-w-4xl flex-1 px-6 py-10">
                    <div className="mb-8">
                        <h1 className="text-2xl font-bold tracking-tight">
                            Uređivanje aviona
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Sva polja označena * su obavezna.
                        </p>
                    </div>

                    <form onSubmit={handleSubmit}>
                        <PlaneFormFields data={data} setData={setData} errors={errors} />

                        <div className="mt-6 flex items-center justify-end gap-2 border-t border-border pt-6">
                            <Button asChild variant="ghost">
                                <Link href="/admin/flota">Otkaži</Link>
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Čuvanje…' : 'Sačuvaj izmene'}
                            </Button>
                        </div>
                    </form>
                </main>
            </div>
        </>
    );
}
