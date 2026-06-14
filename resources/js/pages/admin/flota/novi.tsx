import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import type { PlaneFormData} from './plane-form-fields';
import { PlaneFormFields } from './plane-form-fields';

export default function NoviAvion() {
    const { data, setData, post, processing, errors } = useForm<PlaneFormData>({
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

                    <form onSubmit={handleSubmit}>
                        <PlaneFormFields data={data} setData={setData} errors={errors} />

                        <div className="mt-6 flex items-center justify-end gap-2 border-t border-border pt-6">
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
