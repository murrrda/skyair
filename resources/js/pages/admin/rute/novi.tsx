import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { AirportOption, RouteFormData, RouteFormFields } from './route-form-fields';

type PageProps = {
    airports: AirportOption[];
};

export default function NovaRuta() {
    const { props } = usePage<PageProps>();
    const { airports } = props;

    const { data, setData, post, processing, errors } = useForm<RouteFormData>({
        name: '',
        starting_airport_id: '',
        landing_airport_id: '',
        distance_km: '',
        estimated_time: '',
        active: true,
    });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        post('/admin/rute');
    }

    return (
        <>
            <Head title="Nova ruta" />
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
                            <span className="font-medium">Nova ruta</span>
                        </div>
                    </div>
                </header>

                <main className="mx-auto w-full max-w-4xl flex-1 px-6 py-10">
                    <div className="mb-8">
                        <h1 className="text-2xl font-bold tracking-tight">Dodavanje nove rute</h1>
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
                                {processing ? 'Čuvanje…' : 'Sačuvaj rutu'}
                            </Button>
                        </div>
                    </form>
                </main>
            </div>
        </>
    );
}
