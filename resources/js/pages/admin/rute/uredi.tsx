import { Head, Link, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import type { AirportOption, RouteFormData} from './route-form-fields';
import { RouteFormFields } from './route-form-fields';

type RouteResource = {
    id: number;
    name: string;
    starting_airport_id: number;
    landing_airport_id: number;
    distance_km: number;
    estimated_time: number;
    active: boolean;
};

type PageProps = {
    route: RouteResource;
    airports: AirportOption[];
};

export default function UrediRutu() {
    const { props } = usePage<PageProps>();
    const { route, airports } = props;

    const { data, setData, patch, processing, errors } = useForm<RouteFormData>({
        name: route.name,
        starting_airport_id: String(route.starting_airport_id),
        landing_airport_id: String(route.landing_airport_id),
        distance_km: String(route.distance_km),
        estimated_time: String(route.estimated_time),
        active: route.active,
    });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        patch(`/admin/rute/${route.id}`);
    }

    return (
        <>
            <Head title={`Uredi rutu: ${route.name}`} />
            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <header className="w-full border-b border-border/60">
                    <div className="mx-auto flex w-full max-w-4xl items-center justify-between px-6 py-4">
                        <div className="flex items-center gap-3 text-sm">
                            <Link href="/admin" className="text-muted-foreground hover:text-foreground">
                                Admin panel
                            </Link>
                            <span className="text-muted-foreground">/</span>
                            <Link href="/admin/rute" className="text-muted-foreground hover:text-foreground">
                                Rute
                            </Link>
                            <span className="text-muted-foreground">/</span>
                            <span className="font-medium">Uredi rutu</span>
                        </div>
                    </div>
                </header>

                <main className="mx-auto w-full max-w-4xl flex-1 px-6 py-10">
                    <div className="mb-8">
                        <h1 className="text-2xl font-bold tracking-tight">
                            Uređivanje rute: {route.name}
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Sva polja označena * su obavezna.
                        </p>
                    </div>

                    <form onSubmit={handleSubmit}>
                        <RouteFormFields data={data} setData={setData} errors={errors} airports={airports} />

                        <div className="mt-6 flex items-center justify-end gap-2 border-t border-border pt-6">
                            <Button asChild variant="ghost">
                                <Link href="/admin/rute">Otkaži</Link>
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
