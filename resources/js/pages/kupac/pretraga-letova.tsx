import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useState } from 'react';

const destinations = [
    { name: 'Pariz, Francuska', code: 'Pariz', price: 'od 12.500 RSD', color: 'bg-[#E6F1FB] text-[#185FA5]' },
    { name: 'Atina, Grčka', code: 'Atina', price: 'od 9.800 RSD', color: 'bg-[#E1F5EE] text-[#0F6E56]' },
    { name: 'Rim, Italija', code: 'Rim', price: 'od 11.200 RSD', color: 'bg-[#FAEEDA] text-[#854F0B]' },
];

export default function PretragaLetova() {
    const { auth } = usePage().props as any;
    const [from, setFrom] = useState('');
    const [to, setTo] = useState('');
    const [date, setDate] = useState('');

    function handleSearch() {
        router.get('/kupac/rezultati-pretrage', {
            from: from || undefined,
            to: to || undefined,
            date: date || undefined,
        });
    }

    return (
        <>
            <Head title="Pretraga letova" />
            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <header className="w-full border-b border-border/60">
                    <div className="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-4">
                        <Link href="/" className="flex items-center gap-2.5">
                            <div className="flex h-8 w-8 items-center justify-center overflow-hidden rounded-md bg-white">
                                <AppLogoIcon className="h-full w-full object-contain p-0.5" />
                            </div>
                            <span className="text-[15px] font-semibold">SkyAir</span>
                        </Link>
                        <nav className="flex items-center gap-5 text-[13px]">
                            <Link href="/kupac/pretraga-letova" className="font-semibold text-foreground">Letovi</Link>
                            <Link href="/kupac/moji-letovi" className="text-muted-foreground">Moji letovi</Link>
                            <Link href="/support-tickets" className="text-muted-foreground">Moji tiketi</Link>
                            <Link href="#" className="text-muted-foreground">Loyalty</Link>
                            {auth.user ? (
                                <div className="flex items-center gap-2">
                                    <span className="text-muted-foreground">{auth.user.first_name} {auth.user.last_name?.charAt(0)}.</span>
                                    <div className="flex h-8 w-8 items-center justify-center rounded-full bg-[#E6F1FB] text-xs font-semibold text-[#185FA5]">
                                        {auth.user.first_name?.charAt(0)}{auth.user.last_name?.charAt(0)}
                                    </div>
                                </div>
                            ) : (
                                <Button asChild variant="outline" size="sm">
                                    <Link href="/kupac/login">Prijavi se</Link>
                                </Button>
                            )}
                        </nav>
                    </div>
                </header>

                <main>
                    <div className="px-6 py-12 text-center">
                        <h1 className="text-3xl font-bold tracking-tight sm:text-4xl">
                            Pronađi savršen let
                        </h1>
                        <p className="mt-2 text-muted-foreground">
                            Pretražuj letove širom sveta po najboljim cenama
                        </p>
                    </div>

                    <div className="mx-auto max-w-6xl px-6">
                        <div className="rounded-xl bg-muted p-5">
                            <div className="mb-4 flex gap-1.5">
                                <button className="rounded-lg border border-border bg-background px-4 py-2 text-xs font-medium text-foreground">
                                    Povratna
                                </button>
                                <button className="rounded-lg px-4 py-2 text-xs text-muted-foreground">
                                    U jednom smeru
                                </button>
                            </div>

                            <div className="mb-2 grid grid-cols-2 gap-2">
                                <div className="rounded-lg border border-border bg-background p-3">
                                    <Label className="text-[11px] text-muted-foreground">Polazak</Label>
                                    <Input className="mt-1 border-0 p-0 text-sm font-medium shadow-none focus-visible:ring-0" placeholder="Beograd (BEG)" value={from} onChange={e => setFrom(e.target.value)} />
                                </div>
                                <div className="rounded-lg border border-border bg-background p-3">
                                    <Label className="text-[11px] text-muted-foreground">Destinacija</Label>
                                    <Input className="mt-1 border-0 p-0 text-sm font-medium shadow-none focus-visible:ring-0" placeholder="Pariz (CDG)" value={to} onChange={e => setTo(e.target.value)} />
                                </div>
                            </div>

                            <div className="mb-4 grid grid-cols-3 gap-2">
                                <div className="rounded-lg border border-border bg-background p-3">
                                    <Label className="text-[11px] text-muted-foreground">Datum polaska</Label>
                                    <Input type="date" className="mt-1 border-0 p-0 text-sm font-medium shadow-none focus-visible:ring-0" value={date} onChange={e => setDate(e.target.value)} />
                                </div>
                                <div className="rounded-lg border border-border bg-background p-3">
                                    <Label className="text-[11px] text-muted-foreground">Datum povratka</Label>
                                    <Input type="date" className="mt-1 border-0 p-0 text-sm font-medium shadow-none focus-visible:ring-0" />
                                </div>
                                <div className="rounded-lg border border-border bg-background p-3">
                                    <Label className="text-[11px] text-muted-foreground">Putnici / klasa</Label>
                                    <Input className="mt-1 border-0 p-0 text-sm font-medium shadow-none focus-visible:ring-0" placeholder="1 odrasli, ekonomska" />
                                </div>
                            </div>

                            <Button className="w-full bg-[#185FA5] py-6 text-sm font-semibold hover:bg-[#0C447C]" onClick={handleSearch}>
                                Pretraži letove
                            </Button>
                        </div>
                    </div>

                    <div className="mx-auto mt-8 max-w-6xl px-6 pb-12">
                        <h2 className="mb-4 text-base font-semibold">Popularne destinacije</h2>
                        <div className="grid grid-cols-3 gap-3">
                            {destinations.map((dest) => (
                                <button key={dest.code} className="cursor-pointer rounded-xl border border-border bg-card p-3 text-left transition-colors hover:border-[#185FA5]/40" onClick={() => router.get('/kupac/rezultati-pretrage', { to: dest.code })}>
                                    <div className={`mb-3 flex h-24 items-center justify-center rounded-lg text-xs font-medium ${dest.color}`}>
                                        {dest.code}
                                    </div>
                                    <div className="text-sm font-medium">{dest.name}</div>
                                    <div className="mt-1 text-xs text-muted-foreground">{dest.price}</div>
                                </button>
                            ))}
                        </div>
                    </div>
                </main>
            </div>
        </>
    );
}
