import { Head, Link, usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { NotificationBell } from '@/components/notification-bell';

type Review = {
    id: number;
    ticket_number: string;
    category: string | null;
    customer_name: string;
    closed_at: string | null;
    agent_rating: number;
    agent_comment: string | null;
    resolution_speed: number;
    resolution_speed_comment: string | null;
    communication_quality: number | null;
    communication_quality_comment: string | null;
    degree_of_resolution: number | null;
    degree_of_resolution_comment: string | null;
    rated_at: string | null;
};

type Stats = {
    total_reviews: number;
    average_rating: number | null;
    distribution: Record<number, number>;
};

type PageProps = {
    reviews: Review[];
    stats: Stats;
    auth: { user: { first_name?: string; last_name?: string } | null };
};

function formatDateTime(iso: string | null): string {
    if (!iso) {
return '—';
}

    try {
        return new Date(iso).toLocaleString('sr-RS', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return iso;
    }
}

function Stars({ value }: { value: number }) {
    return (
        <span className="inline-flex items-center gap-0.5">
            {[1, 2, 3, 4, 5].map((n) => (
                <span key={n} className={n <= value ? 'text-[#f59e0b]' : 'text-muted-foreground'}>
                    {n <= value ? '★' : '☆'}
                </span>
            ))}
            <span className="ml-1 text-[11px] text-muted-foreground">{value}/5</span>
        </span>
    );
}

function DistributionBar({ star, count, max }: { star: number; count: number; max: number }) {
    const pct = max > 0 ? (count / max) * 100 : 0;

    return (
        <div className="flex items-center gap-2 text-[13px]">
            <span className="w-4 text-right text-muted-foreground">{star}</span>
            <span className="text-[#f59e0b]">★</span>
            <div className="h-2 flex-1 overflow-hidden rounded-full bg-muted">
                <div
                    className="h-full rounded-full bg-[#f59e0b] transition-all"
                    style={{ width: `${pct}%` }}
                />
            </div>
            <span className="w-6 text-right text-xs text-muted-foreground">{count}</span>
        </div>
    );
}

export default function ZaposleniRecenzije() {
    const { reviews, stats, auth } = usePage<PageProps>().props;

    const initials =
        (auth.user?.first_name?.charAt(0) ?? '') + (auth.user?.last_name?.charAt(0) ?? '');

    const maxDist = Math.max(...Object.values(stats.distribution), 1);

    return (
        <>
            <Head title="Moje recenzije — podrška" />
            <div className="min-h-screen bg-background text-foreground">
                <nav className="flex h-14 items-center border-b border-border bg-card px-8">
                    <Link href="/" className="mr-10 flex items-center gap-2.5">
                        <div className="flex h-8 w-8 items-center justify-center overflow-hidden rounded-lg bg-white">
                            <AppLogoIcon className="h-full w-full object-contain p-0.5" />
                        </div>
                        <span className="text-[15px] font-semibold tracking-tight">SkyAir</span>
                    </Link>
                    <div className="flex items-center gap-4">
                        <Link
                            href="/zaposleni/podrska"
                            className="text-[13px] font-medium text-muted-foreground transition hover:text-foreground"
                        >
                            Kanban tabla
                        </Link>
                        <span className="text-[13px] font-semibold text-foreground">
                            Moje recenzije
                        </span>
                    </div>
                    <div className="ml-auto flex items-center gap-3">
                        {auth.user && <NotificationBell />}
                        {auth.user && (
                            <div className="flex items-center gap-2 rounded-full border border-border bg-muted py-1 pr-3 pl-1">
                                <div className="flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-[#059669] to-[#047857] text-[11px] font-semibold text-white">
                                    {initials || 'Z'}
                                </div>
                                <span className="text-[13px] font-medium">
                                    {auth.user.first_name} {auth.user.last_name?.charAt(0)}.
                                </span>
                            </div>
                        )}
                    </div>
                </nav>

                <div className="flex flex-col gap-6 px-8 py-7">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Moje recenzije</h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Pregled svih ocena koje su korisnici ostavili za vaš rad
                        </p>
                    </div>

                    <div className="grid grid-cols-1 gap-5 md:grid-cols-3">
                        <div className="rounded-xl border border-border bg-card p-5 shadow-sm">
                            <div className="text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
                                Ukupno recenzija
                            </div>
                            <div className="mt-2 text-3xl font-bold">{stats.total_reviews}</div>
                        </div>
                        <div className="rounded-xl border border-border bg-card p-5 shadow-sm">
                            <div className="text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
                                Prosečna ocena
                            </div>
                            <div className="mt-2 flex items-baseline gap-2">
                                <span className="text-3xl font-bold">
                                    {stats.average_rating ?? '—'}
                                </span>
                                {stats.average_rating !== null && (
                                    <Stars value={Math.round(stats.average_rating)} />
                                )}
                            </div>
                        </div>
                        <div className="rounded-xl border border-border bg-card p-5 shadow-sm">
                            <div className="mb-3 text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
                                Distribucija ocena
                            </div>
                            <div className="space-y-1.5">
                                {[5, 4, 3, 2, 1].map((star) => (
                                    <DistributionBar
                                        key={star}
                                        star={star}
                                        count={stats.distribution[star] ?? 0}
                                        max={maxDist}
                                    />
                                ))}
                            </div>
                        </div>
                    </div>

                    {reviews.length === 0 ? (
                        <div className="rounded-xl border border-dashed border-border bg-card px-6 py-12 text-center text-sm text-muted-foreground">
                            Još nemate nijednu recenziju.
                        </div>
                    ) : (
                        <div className="space-y-4">
                            {reviews.map((r) => (
                                <ReviewCard key={r.id} review={r} />
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}

function ReviewCard({ review }: { review: Review }) {
    return (
        <div className="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
            <div className="flex items-center justify-between border-b border-border px-5 py-3.5">
                <div className="flex items-center gap-3">
                    <span className="font-mono text-[13px] font-medium text-[#1a56db] dark:text-[#7eb1f5]">
                        #{review.ticket_number}
                    </span>
                    {review.category && (
                        <span className="rounded border border-border bg-muted px-2 py-0.5 text-[11px] text-muted-foreground">
                            {review.category}
                        </span>
                    )}
                    <span className="text-[13px] text-muted-foreground">
                        {review.customer_name}
                    </span>
                </div>
                <span className="text-xs text-muted-foreground">
                    {formatDateTime(review.rated_at)}
                </span>
            </div>
            <div className="grid grid-cols-1 gap-4 px-5 py-4 md:grid-cols-2">
                <div>
                    <div className="mb-2 text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
                        Vaša ocena
                    </div>
                    <div className="flex items-center gap-2">
                        <Stars value={review.agent_rating} />
                    </div>
                    {review.agent_comment && (
                        <p className="mt-2 rounded-md border border-border bg-muted px-2.5 py-1.5 text-[12px] leading-relaxed text-foreground">
                            &ldquo;{review.agent_comment}&rdquo;
                        </p>
                    )}
                </div>
                <div>
                    <div className="mb-2 text-[11px] font-semibold tracking-wider text-muted-foreground uppercase">
                        Ocene po kategoriji
                    </div>
                    <div className="space-y-1 text-[13px]">
                        <DimensionRow label="Brzina rešavanja" value={review.resolution_speed} />
                        <DimensionRow
                            label="Kvalitet komunikacije"
                            value={review.communication_quality}
                        />
                        <DimensionRow
                            label="Stepen rešenja"
                            value={review.degree_of_resolution}
                        />
                    </div>
                </div>
            </div>
        </div>
    );
}

function DimensionRow({ label, value }: { label: string; value: number | null }) {
    if (value === null) {
return null;
}

    return (
        <div className="flex items-center justify-between border-b border-border py-1.5 last:border-b-0">
            <span className="text-muted-foreground">{label}</span>
            <Stars value={value} />
        </div>
    );
}