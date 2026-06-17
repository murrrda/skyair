import { Head } from '@inertiajs/react';
import { cn } from '@/lib/utils';

type CertificateStatus = 'valid' | 'expiring' | 'expired';

type Certificate = {
    id: number;
    type: string;
    issued_at: string;
    expires_at: string;
    note: string | null;
    status: CertificateStatus;
    days_until_expiry: number;
};

type Props = {
    certificates: Certificate[];
};

const STATUS_CONFIG: Record<
    CertificateStatus,
    { card: string; badge: string; label: (c: Certificate) => string }
> = {
    expired: {
        card: 'border-red-200 bg-red-50/50',
        badge: 'border border-red-200 bg-red-50 text-red-700',
        label: () => '✕ Isteklo',
    },
    expiring: {
        card: 'border-amber-300 bg-amber-50/40',
        badge: 'border border-amber-200 bg-amber-50 text-amber-700',
        label: (c) => `⚠ Za ${c.days_until_expiry} dana`,
    },
    valid: {
        card: 'border-border bg-card',
        badge: 'border border-emerald-200 bg-emerald-50 text-emerald-700',
        label: () => '✓ Važeći',
    },
};

/** 'YYYY-MM-DD' → 'DD.MM.YYYY.' */
function formatDate(date: string): string {
    const [year, month, day] = date.split('-');

    return `${day}.${month}.${year}.`;
}

export default function EmployeeCertificates({ certificates }: Props) {
    return (
        <>
            <Head title="Moji sertifikati" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold tracking-tight">
                    Moji sertifikati
                </h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    Pregled sertifikata i njihovih rokova važenja
                </p>
            </div>

            <div className="space-y-3">
                {certificates.map((certificate) => {
                    const cfg = STATUS_CONFIG[certificate.status];

                    return (
                        <div
                            key={certificate.id}
                            className={cn('rounded-xl border p-5', cfg.card)}
                        >
                            <div className="flex items-start justify-between gap-4">
                                <div className="min-w-0">
                                    <h2 className="font-semibold text-foreground">
                                        {certificate.type}
                                    </h2>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        Izdato:{' '}
                                        {formatDate(certificate.issued_at)}
                                        {certificate.note
                                            ? ` · ${certificate.note}`
                                            : ''}
                                    </p>
                                </div>

                                <div className="flex shrink-0 flex-col items-end gap-1.5">
                                    <span className="text-sm font-semibold text-foreground">
                                        {certificate.status === 'expired'
                                            ? 'Isteklo'
                                            : 'Ističe'}{' '}
                                        {formatDate(certificate.expires_at)}
                                    </span>
                                    <span
                                        className={cn(
                                            'rounded-full px-2.5 py-0.5 text-xs font-medium',
                                            cfg.badge,
                                        )}
                                    >
                                        {cfg.label(certificate)}
                                    </span>
                                </div>
                            </div>
                        </div>
                    );
                })}

                {certificates.length === 0 && (
                    <p className="py-12 text-center text-muted-foreground">
                        Trenutno nemate evidentiranih sertifikata.
                    </p>
                )}
            </div>
        </>
    );
}
