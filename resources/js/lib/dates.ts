export const SAO_PAULO_TIMEZONE = 'America/Sao_Paulo';

export const DEFAULT_LOCALE = 'pt-BR';

export type DateInput = string | Date | null | undefined;

const dateFormatOptions: Intl.DateTimeFormatOptions = {
    timeZone: SAO_PAULO_TIMEZONE,
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
};

const dateMediumFormatOptions: Intl.DateTimeFormatOptions = {
    timeZone: SAO_PAULO_TIMEZONE,
    dateStyle: 'medium',
};

const dateTimeFormatOptions: Intl.DateTimeFormatOptions = {
    timeZone: SAO_PAULO_TIMEZONE,
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
};

function parseDateInput(value: DateInput): Date | null {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    if (value instanceof Date) {
        return Number.isNaN(value.getTime()) ? null : value;
    }

    const trimmed = value.trim();
    const dateOnlyMatch = /^(\d{4})-(\d{2})-(\d{2})$/.exec(trimmed);

    if (dateOnlyMatch) {
        const [, year, month, day] = dateOnlyMatch;

        // Noon UTC avoids day shifts when formatting in America/Sao_Paulo.
        return new Date(
            Date.UTC(Number(year), Number(month) - 1, Number(day), 12, 0, 0),
        );
    }

    const parsed = new Date(trimmed);

    return Number.isNaN(parsed.getTime()) ? null : parsed;
}

function formatWithIntl(
    value: DateInput,
    options: Intl.DateTimeFormatOptions,
    fallback: string,
): string {
    const date = parseDateInput(value);

    if (!date) {
        return fallback;
    }

    return new Intl.DateTimeFormat(DEFAULT_LOCALE, options).format(date);
}

/** Formats a date in São Paulo timezone. Example: 14/07/2026 */
export function formatDate(value: DateInput, fallback = '—'): string {
    return formatWithIntl(value, dateFormatOptions, fallback);
}

/** Formats a date using pt-BR medium style. Example: 14 de jul. de 2026 */
export function formatDateMedium(value: DateInput, fallback = '—'): string {
    return formatWithIntl(value, dateMediumFormatOptions, fallback);
}

/** Formats date and time in São Paulo timezone. Example: 14/07/2026, 09:30 */
export function formatDateTime(value: DateInput, fallback = '—'): string {
    return formatWithIntl(value, dateTimeFormatOptions, fallback);
}

/** Formats with custom Intl options, defaulting to São Paulo timezone. */
export function formatDateCustom(
    value: DateInput,
    options: Intl.DateTimeFormatOptions = {},
    fallback = '—',
): string {
    return formatWithIntl(
        value,
        { timeZone: SAO_PAULO_TIMEZONE, ...options },
        fallback,
    );
}
