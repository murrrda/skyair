import { Link, usePage } from '@inertiajs/react';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import type { Auth } from '@/types';

const NAV_LINKS = [
    { label: 'Zaposleni', href: '/admin/employee' },
    { label: 'Incidenti', href: '/admin/incidenti' },
    { label: 'Performanse', href: '/admin/performanse' },
];

export default function AdminLayout({ children }: { children: React.ReactNode }) {
    const page = usePage();
    const { auth } = page.props as unknown as { auth: Auth };
    const currentUrl = page.url;
    const getInitials = useInitials();

    return (
        <div className="min-h-screen bg-background text-foreground">
            <header className="border-b border-border/60">
                <div className="mx-auto flex max-w-7xl items-center justify-between px-6 py-3">
                    <Link href="/admin" className="flex items-center gap-2">
                        <div className="flex h-8 w-8 items-center justify-center rounded-md bg-[#E6F1FB] text-sm font-bold text-[#185FA5]">
                            SA
                        </div>
                        <span className="text-[15px] font-semibold">SkyAir</span>
                    </Link>

                    <nav className="flex items-center gap-7">
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
                    </nav>

                    <div className="flex h-8 w-8 items-center justify-center rounded-full bg-[#E6F1FB] text-xs font-semibold text-[#185FA5]">
                        {auth?.user ? getInitials(auth.user.name) : '?'}
                    </div>
                </div>
            </header>

            <div className="mx-auto max-w-7xl px-6 py-6">{children}</div>
        </div>
    );
}
