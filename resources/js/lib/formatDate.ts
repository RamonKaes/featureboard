const formatter = new Intl.DateTimeFormat('de-DE', { dateStyle: 'long' });

export function formatDate(date: string | null): string {
    return date ? formatter.format(new Date(date)) : '';
}
