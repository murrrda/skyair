import { Link, usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';

interface KupacHeaderProps {
    active?: 'letovi' | 'moji-letovi' | 'moji-tiketi' | 'loyalty';
}

export default function KupacHeader({ active }: KupacHeaderProps) {
    const { auth } = usePage().props as any;

    const linkClass = (key: string) =>
        key === active ? 'font-semibold text-foreground' : 'text-muted-foreground hover:text-foreground transition';

    return (
        <header className="w-full border-b border-border/60 bg-background">
            <div className="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-4">
                <Link href="/" className="flex items-center gap-2.5">
                    <div className="flex h-8 w-8 items-center justify-center overflow-hidden rounded-md bg-white">
                        <AppLogoIcon className="h-full w-full object-contain p-0.5" />
                    </div>
                    <span className="text-[15px] font-semibold">SkyAir</span>
                </Link>
                <nav className="flex items-center gap-5 text-[13px]">
                    <Link href="/kupac/pretraga-letova" className={linkClass('letovi')}>Letovi</Link>
                    <Link href="/kupac/moji-letovi" className={linkClass('moji-letovi')}>Moji letovi</Link>
                    <Link href="/support-tickets" className={linkClass('moji-tiketi')}>Moji tiketi</Link>
                    <Link href="/kupac/loyalty" className={linkClass('loyalty')}>Loyalty</Link>

                    {auth.user ? (
                        <Link href="/kupac/edit-profile" className="flex items-center gap-2 transition hover:opacity-80">
                            <span className="text-muted-foreground">
                                {auth.user.first_name} {auth.user.last_name?.charAt(0)}.
                            </span>
                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-[#E6F1FB] text-xs font-semibold text-[#185FA5]">
                                {auth.user.first_name?.charAt(0)}{auth.user.last_name?.charAt(0)}
                            </div>
                        </Link>
                    ) : (
                        <Button asChild variant="outline" size="sm">
                            <Link href="/kupac/login">Prijavi se</Link>
                        </Button>
                    )}
                </nav>
            </div>
        </header>
    );
}
