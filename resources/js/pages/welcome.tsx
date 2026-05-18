import { Head, Link, usePage } from '@inertiajs/react';
import { Briefcase, ShieldCheck, User } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { dashboard, login, register } from '@/routes';

type RoleKey = 'zaposleni' | 'kupac' | 'admin';

const roles: { key: RoleKey; title: string; description: string; icon: React.ComponentType<{ className?: string }> }[] = [
    {
        key: 'zaposleni',
        title: 'ZAPOSLENI',
        description: 'Pristup za zaposlene',
        icon: Briefcase,
    },
    {
        key: 'kupac',
        title: 'KUPAC',
        description: 'Pristup za kupce',
        icon: User,
    },
    {
        key: 'admin',
        title: 'ADMIN',
        description: 'Administratorski pristup',
        icon: ShieldCheck,
    },
];

export default function Welcome({
    canRegister = true,
}: {
    canRegister?: boolean;
}) {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Dobrodošli" />
            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <header className="w-full border-b border-border/60">
                    <div className="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-4">
                        <Link href="/" className="text-lg font-semibold tracking-tight">
                            SkyAir
                        </Link>
                        <nav className="flex items-center gap-2">
                            {auth.user ? (
                                <Button asChild variant="outline" size="sm">
                                    <Link href={dashboard()}>Dashboard</Link>
                                </Button>
                            ) : (
                                <>
                                    <Button asChild variant="ghost" size="sm">
                                        <Link href={login()}>Prijava</Link>
                                    </Button>
                                    {canRegister && (
                                        <Button asChild variant="outline" size="sm">
                                            <Link href={register()}>Registracija</Link>
                                        </Button>
                                    )}
                                </>
                            )}
                        </nav>
                    </div>
                </header>

                <main className="flex flex-1 items-center justify-center px-6 py-12">
                    <div className="w-full max-w-4xl">
                        <div className="mb-10 text-center">
                            <h1 className="text-3xl font-bold tracking-tight sm:text-4xl">
                                Izaberite tip korisnika
                            </h1>
                            <p className="mt-3 text-muted-foreground">
                                Odaberite ulogu sa kojom želite da nastavite.
                            </p>
                        </div>

                        <div className="grid gap-6 sm:grid-cols-3">
                            {roles.map((role) => {
                                const Icon = role.icon;
                                return (
                                    <Link
                                        key={role.key}
                                        href={
                                            role.key === 'kupac'
                                                ? '/kupac/login'
                                                : login({ query: { role: role.key } })
                                        }
                                        className="group flex flex-col items-center justify-center rounded-xl border border-border bg-card p-8 text-center shadow-sm transition hover:-translate-y-1 hover:border-primary hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                    >
                                        <span className="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-primary/10 text-primary transition group-hover:bg-primary group-hover:text-primary-foreground">
                                            <Icon className="h-7 w-7" />
                                        </span>
                                        <h2 className="text-lg font-semibold tracking-wide">
                                            {role.title}
                                        </h2>
                                        <p className="mt-2 text-sm text-muted-foreground">
                                            {role.description}
                                        </p>
                                    </Link>
                                );
                            })}
                        </div>
                    </div>
                </main>
            </div>
        </>
    );
}
