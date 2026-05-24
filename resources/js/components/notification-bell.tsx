import { router, usePage } from '@inertiajs/react';
import { Bell, CheckCheck } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';

type NotificationItem = {
    id: string;
    type: string;
    data: {
        ticket_id?: number;
        ticket_number?: string;
        status_from?: string | null;
        status_to?: string;
        message?: string;
    };
    read_at: string | null;
    created_at: string | null;
};

type SharedProps = {
    auth?: { user?: { id?: number | null } | null };
    notifications?: {
        unread_count: number;
        items: NotificationItem[];
    };
};

function formatRelative(iso: string | null): string {
    if (!iso) {
return '—';
}

    const diffMin = Math.floor((Date.now() - new Date(iso).getTime()) / 60000);

    if (diffMin < 1) {
return 'sada';
}

    if (diffMin < 60) {
return `pre ${diffMin} min`;
}

    const diffH = Math.floor(diffMin / 60);

    if (diffH < 24) {
return `pre ${diffH}h`;
}

    const diffD = Math.floor(diffH / 24);

    return `pre ${diffD} dana`;
}

export function NotificationBell() {
    const { auth, notifications } = usePage<SharedProps>().props;
    const userId = auth?.user?.id ?? null;
    const unread = notifications?.unread_count ?? 0;
    const items = notifications?.items ?? [];
    const [open, setOpen] = useState(false);
    const ref = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!open) {
            return;
        }

        const handler = (e: MouseEvent) => {
            if (ref.current && !ref.current.contains(e.target as Node)) {
                setOpen(false);
            }
        };

        document.addEventListener('mousedown', handler);

        return () => document.removeEventListener('mousedown', handler);
    }, [open]);

    useEffect(() => {
        if (!userId || typeof window === 'undefined' || !window.Echo) {
            return;
        }

        const channelName = `App.Models.User.${userId}`;
        const channel = window.Echo.private(channelName);

        channel.notification((payload: { ticket_number?: string; status_to_label?: string; message?: string }) => {
            router.reload({ only: ['notifications'] });

            const title = payload.ticket_number
                ? `Tiket #${payload.ticket_number}`
                : 'Nova notifikacija';
            const description = payload.message
                ?? (payload.status_to_label ? `Novi status: ${payload.status_to_label}` : '');

            toast(title, { description });
        });

        return () => {
            window.Echo.leave(channelName);
        };
    }, [userId]);

    const markRead = (id: string) => {
        router.post(`/notifications/${id}/read`, {}, { preserveScroll: true });
    };

    const markAllRead = () => {
        router.post('/notifications/read-all', {}, { preserveScroll: true });
    };

    return (
        <div className="relative" ref={ref}>
            <button
                type="button"
                onClick={() => setOpen((v) => !v)}
                aria-label="Notifikacije"
                className="relative flex h-9 w-9 items-center justify-center rounded-full border border-border bg-muted text-muted-foreground transition hover:text-foreground"
            >
                <Bell className="h-4 w-4" />
                {unread > 0 && (
                    <span className="absolute -top-0.5 -right-0.5 flex h-4 min-w-[16px] items-center justify-center rounded-full border-2 border-card bg-[#dc2626] px-1 text-[10px] font-semibold text-white">
                        {unread > 9 ? '9+' : unread}
                    </span>
                )}
            </button>

            {open && (
                <div className="absolute top-12 right-0 z-50 w-[360px] overflow-hidden rounded-xl border border-border bg-card shadow-lg">
                    <div className="flex items-center justify-between border-b border-border px-4 py-3">
                        <div className="text-sm font-semibold">Notifikacije</div>
                        {unread > 0 && (
                            <button
                                type="button"
                                onClick={markAllRead}
                                className="inline-flex items-center gap-1 text-[11px] font-medium text-[#1a56db] hover:underline dark:text-[#7eb1f5]"
                            >
                                <CheckCheck className="h-3 w-3" />
                                Označi sve kao pročitano
                            </button>
                        )}
                    </div>

                    {items.length === 0 ? (
                        <div className="px-4 py-10 text-center text-sm text-muted-foreground">
                            Nema notifikacija.
                        </div>
                    ) : (
                        <ul className="max-h-[420px] divide-y divide-border overflow-auto">
                            {items.map((n) => {
                                const isUnread = !n.read_at;

                                return (
                                    <li
                                        key={n.id}
                                        className={
                                            'flex items-start gap-3 px-4 py-3 transition ' +
                                            (isUnread ? 'bg-[#1a56db]/5 dark:bg-[#7eb1f5]/5' : '')
                                        }
                                    >
                                        {isUnread && (
                                            <span className="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-[#1a56db] dark:bg-[#7eb1f5]" />
                                        )}
                                        <div className="flex-1">
                                            <div className="text-[13px] leading-tight font-medium">
                                                {n.data.message ??
                                                    `Tiket #${n.data.ticket_number ?? n.data.ticket_id ?? '?'} ažuriran`}
                                            </div>
                                            <div className="mt-1 text-[11px] text-muted-foreground">
                                                {formatRelative(n.created_at)}
                                            </div>
                                        </div>
                                        {isUnread && (
                                            <button
                                                type="button"
                                                onClick={() => markRead(n.id)}
                                                className="text-[11px] font-medium text-muted-foreground hover:text-foreground"
                                            >
                                                Pročitano
                                            </button>
                                        )}
                                    </li>
                                );
                            })}
                        </ul>
                    )}
                </div>
            )}
        </div>
    );
}