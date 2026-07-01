/**
 * Today's date as a local `YYYY-MM-DD` string.
 *
 * Uses local date parts rather than `new Date().toISOString()` to avoid the
 * UTC off-by-one that can happen near midnight. Handy as a `max`/`min` bound
 * on native `<input type="date">` fields.
 */
export function todayIso(): string {
    const d = new Date();

    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}
