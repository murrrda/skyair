import { Link, router, usePage } from '@inertiajs/react';
import { LogOut } from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import { logout } from '@/routes';
import type { Auth } from '@/types';

const NAV_LINKS = [
    { label: 'Zaposleni', href: '/admin/employee' },
    { label: 'Kategorije tiketa', href: '/admin/kategorije' },
    { label: 'Incidenti', href: '/admin/incidenti' },
    { label: 'Performanse', href: '/admin/performanse' },
    { label: 'Statistika', href: '/admin/statistika' },
    { label: 'Korisnička podrška', href: '/admin/podrska/statistike' },
];

export default function AdminLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    const page = usePage();
    const { auth } = page.props as unknown as { auth: Auth };
    const currentUrl = page.url;
    const getInitials = useInitials();

    return (
        <div className="min-h-screen bg-background text-foreground">
            <header className="border-b border-border/60">
                <div className="mx-auto flex max-w-7xl items-center justify-between px-6 py-3">
                    <Link href="/admin" className="flex items-center gap-2">
                        <div className="flex h-8 w-8 items-center justify-center overflow-hidden rounded-md bg-white">
                            <AppLogoIcon className="h-full w-full object-contain p-0.5" />
                        </div>
                        <span className="text-[15px] font-semibold">
                            SkyAir
                        </span>
                    </Link>

                    <div className="flex items-center gap-7">
                        {NAV_LINKS.map((link) => (
                            <Link
                                key={link.href}
                                href={link.href}
                                className={cn(
                                    'text-sm transition-colors',
                                    currentUrl.startsWith(link.href)
                                        ? 'font-semibold text-foreground'
                                        : 'text-muted-foreground hover:text-foreground',
                                )}
                            >
                                {link.label}
                            </Link>
                        ))}

                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <button
                                    type="button"
                                    className="flex h-8 w-8 items-center justify-center rounded-full bg-[#E6F1FB] text-xs font-semibold text-[#185FA5] outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                >
                                    {auth?.user
                                        ? getInitials(auth.user.name)
                                        : '?'}
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-56">
                                {auth?.user && (
                                    <>
                                        <DropdownMenuLabel className="font-normal">
                                            <div className="flex flex-col">
                                                <span className="text-sm font-medium">
                                                    {auth.user.name}
                                                </span>
                                                <span className="text-xs text-muted-foreground">
                                                    {auth.user.email}
                                                </span>
                                            </div>
                                        </DropdownMenuLabel>
                                        <DropdownMenuSeparator />
                                    </>
                                )}
                                <DropdownMenuItem asChild>
                                    <Link
                                        href={logout()}
                                        as="button"
                                        onClick={() => router.flushAll()}
                                        className="w-full cursor-pointer"
                                        data-test="logout-button"
                                    >
                                        <LogOut className="mr-2 h-4 w-4" />
                                        Odjavi se
                                    </Link>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>
            </header>

            <div className="mx-auto max-w-7xl px-6 py-6">{children}</div>
        </div>
    );
}
