import { Head, Link, usePage } from '@inertiajs/react';
import { Ticket, Users } from 'lucide-react';
import { Button } from '@/components/ui/button';

const sections = [
    {
        key: 'prodaja-karata',
        title: 'PRODAJA KARATA',
        description: 'Upravljanje prodajom karata',
        href: '/admin/prodaja-karata',
        icon: Ticket,
    },
    {
        key: 'zaposleni',
        title: 'UPRAVLJANJE ZAPOSLENIMA',
        description: 'Pregled i upravljanje zaposlenima',
        href: '/admin/zaposleni',
        icon: Users,
    },
];

export default function AdminIndex() {
    return (
        <>
            <Head title="Admin panel" />
            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <header className="w-full border-b border-border/60">
                    <div className="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-4">
                        <Link href="/" className="text-lg font-semibold tracking-tight">
                            SkyAir
                        </Link>
                        <nav className="flex items-center gap-2">
                            <Button asChild variant="ghost" size="sm">
                                <Link href="/">Nazad</Link>
                            </Button>
                        </nav>
                    </div>
                </header>

                <main className="flex flex-1 items-center justify-center px-6 py-12">
                    <div className="w-full max-w-3xl">
                        <div className="mb-10 text-center">
                            <h1 className="text-3xl font-bold tracking-tight sm:text-4xl">
                                Admin panel
                            </h1>
                            <p className="mt-3 text-muted-foreground">
                                Odaberite sekciju kojoj želite da pristupite.
                            </p>
                        </div>

                        <div className="grid gap-6 sm:grid-cols-2">
                            {sections.map((section) => {
                                const Icon = section.icon;
                                return (
                                    <Link
                                        key={section.key}
                                        href={section.href}
                                        className="group flex flex-col items-center justify-center rounded-xl border border-border bg-card p-8 text-center shadow-sm transition hover:-translate-y-1 hover:border-primary hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                    >
                                        <span className="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-primary/10 text-primary transition group-hover:bg-primary group-hover:text-primary-foreground">
                                            <Icon className="h-7 w-7" />
                                        </span>
                                        <h2 className="text-lg font-semibold tracking-wide">
                                            {section.title}
                                        </h2>
                                        <p className="mt-2 text-sm text-muted-foreground">
                                            {section.description}
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
