import { Head, Link, usePage } from '@inertiajs/react';
import { ChevronDown, ChevronRight, FileText, Tag } from 'lucide-react';
import { useState } from 'react';

type CategoryField = {
    id: number;
    field_name: string;
    field_type: string;
    required: boolean;
    display_label: string;
    prompt: string;
    validation_rule: string | null;
    reference_table: string | null;
    reference_column: string | null;
};

type Category = {
    id: number;
    name: string;
    fields: CategoryField[];
};

type PageProps = {
    categories: Category[];
};

const fieldTypeMeta: Record<string, { label: string; className: string }> = {
    text: { label: 'Tekst', className: 'bg-[#ecfdf5] text-[#059669]' },
    number: { label: 'Broj', className: 'bg-[#eef2ff] text-[#2152e0]' },
    datetime: { label: 'Datum/vreme', className: 'bg-[#fef3c7] text-[#a16207]' },
    reference: { label: 'Referenca', className: 'bg-[#fce7f3] text-[#be185d]' },
};

export default function KategorijeIndex() {
    const { props } = usePage<PageProps>();
    const { categories } = props;
    const [expanded, setExpanded] = useState<Set<number>>(new Set());

    function toggle(id: number) {
        setExpanded((prev) => {
            const next = new Set(prev);

            if (next.has(id)) {
                next.delete(id);
            } else {
                next.add(id);
            }

            return next;
        });
    }

    function expandAll() {
        setExpanded(new Set(categories.map((c) => c.id)));
    }

    function collapseAll() {
        setExpanded(new Set());
    }

    return (
        <>
            <Head title="Kategorije tiketa" />
            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <header className="w-full border-b border-border/60">
                    <div className="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-4">
                        <div className="flex items-center gap-3">
                            <Link href="/admin" className="text-sm text-muted-foreground hover:text-foreground">
                                ← Admin panel
                            </Link>
                            <span className="text-muted-foreground">/</span>
                            <span className="text-sm font-medium">Kategorije tiketa</span>
                        </div>
                        <nav className="flex items-center gap-2">
                            <button
                                type="button"
                                onClick={expandAll}
                                className="rounded-md px-3 py-1.5 text-xs font-medium text-muted-foreground transition hover:bg-accent hover:text-foreground"
                            >
                                Proširi sve
                            </button>
                            <button
                                type="button"
                                onClick={collapseAll}
                                className="rounded-md px-3 py-1.5 text-xs font-medium text-muted-foreground transition hover:bg-accent hover:text-foreground"
                            >
                                Skupi sve
                            </button>
                        </nav>
                    </div>
                </header>

                <main className="mx-auto w-full max-w-6xl flex-1 px-6 py-10">
                    <div className="mb-8">
                        <h1 className="text-2xl font-bold tracking-tight">Kategorije tiketa</h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Pregled kategorija i obaveznih polja za NLP ekstrakciju podataka.
                        </p>
                    </div>

                    <div className="space-y-3">
                        {categories.map((category) => {
                            const isExpanded = expanded.has(category.id);

                            return (
                                <div
                                    key={category.id}
                                    className="rounded-xl border border-border bg-card shadow-sm"
                                >
                                    <button
                                        type="button"
                                        onClick={() => toggle(category.id)}
                                        className="flex w-full items-center justify-between px-5 py-4 text-left transition hover:bg-accent/50"
                                    >
                                        <div className="flex items-center gap-3">
                                            <Tag className="h-4 w-4 text-muted-foreground" />
                                            <span className="font-semibold">{category.name}</span>
                                            <span className="rounded-full bg-muted px-2 py-0.5 text-[11px] font-medium text-muted-foreground">
                                                {category.fields.length}{' '}
                                                {category.fields.length === 1
                                                    ? 'polje'
                                                    : category.fields.length < 5
                                                      ? 'polja'
                                                      : 'polja'}
                                            </span>
                                        </div>
                                        {isExpanded ? (
                                            <ChevronDown className="h-4 w-4 text-muted-foreground" />
                                        ) : (
                                            <ChevronRight className="h-4 w-4 text-muted-foreground" />
                                        )}
                                    </button>

                                    {isExpanded && (
                                        <div className="border-t border-border">
                                            {category.fields.length === 0 ? (
                                                <div className="flex items-center gap-2 px-5 py-6 text-sm text-muted-foreground">
                                                    <FileText className="h-4 w-4" />
                                                    Slobodna forma — nema obaveznih polja.
                                                </div>
                                            ) : (
                                                <div className="overflow-x-auto">
                                                    <table className="w-full text-sm">
                                                        <thead>
                                                            <tr className="border-b border-border bg-muted/30 text-left text-xs text-muted-foreground">
                                                                <th className="px-5 py-2.5 font-medium">Oznaka</th>
                                                                <th className="px-5 py-2.5 font-medium">Naziv polja</th>
                                                                <th className="px-5 py-2.5 font-medium">Tip</th>
                                                                <th className="px-5 py-2.5 font-medium">Obavezno</th>
                                                                <th className="px-5 py-2.5 font-medium">Prompt za NLP</th>
                                                                <th className="px-5 py-2.5 font-medium">Validacija</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            {category.fields.map((field) => {
                                                                const typeMeta = fieldTypeMeta[field.field_type] ?? {
                                                                    label: field.field_type,
                                                                    className: 'bg-muted text-muted-foreground',
                                                                };

                                                                return (
                                                                    <tr
                                                                        key={field.id}
                                                                        className="border-b border-border/50 last:border-0"
                                                                    >
                                                                        <td className="px-5 py-3 font-medium">
                                                                            {field.display_label}
                                                                        </td>
                                                                        <td className="px-5 py-3">
                                                                            <code className="rounded bg-muted px-1.5 py-0.5 text-xs">
                                                                                {field.field_name}
                                                                            </code>
                                                                        </td>
                                                                        <td className="px-5 py-3">
                                                                            <span
                                                                                className={`rounded-full px-2 py-0.5 text-[11px] font-medium ${typeMeta.className}`}
                                                                            >
                                                                                {typeMeta.label}
                                                                            </span>
                                                                            {field.reference_table && (
                                                                                <span className="ml-1 text-[11px] text-muted-foreground">
                                                                                    → {field.reference_table}
                                                                                </span>
                                                                            )}
                                                                        </td>
                                                                        <td className="px-5 py-3">
                                                                            {field.required ? (
                                                                                <span className="font-medium text-red-600 dark:text-red-400">
                                                                                    Da
                                                                                </span>
                                                                            ) : (
                                                                                <span className="text-muted-foreground">
                                                                                    Ne
                                                                                </span>
                                                                            )}
                                                                        </td>
                                                                        <td className="max-w-xs px-5 py-3 text-xs text-muted-foreground">
                                                                            {field.prompt}
                                                                        </td>
                                                                        <td className="px-5 py-3">
                                                                            {field.validation_rule ? (
                                                                                <code className="rounded bg-muted px-1.5 py-0.5 text-xs">
                                                                                    {field.validation_rule}
                                                                                </code>
                                                                            ) : (
                                                                                <span className="text-xs text-muted-foreground">
                                                                                    —
                                                                                </span>
                                                                            )}
                                                                        </td>
                                                                    </tr>
                                                                );
                                                            })}
                                                        </tbody>
                                                    </table>
                                                </div>
                                            )}
                                        </div>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                </main>
            </div>
        </>
    );
}