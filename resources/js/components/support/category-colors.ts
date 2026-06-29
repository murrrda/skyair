export const CATEGORY_COLORS = [
    '#3b82f6', // blue
    '#f59e0b', // amber
    '#10b981', // emerald
    '#ef4444', // red
    '#8b5cf6', // violet
    '#06b6d4', // cyan
    '#f97316', // orange
    '#84cc16', // lime
    '#ec4899', // pink
    '#6366f1', // indigo
];

export function categoryColor(index: number): string {
    return CATEGORY_COLORS[index % CATEGORY_COLORS.length];
}
