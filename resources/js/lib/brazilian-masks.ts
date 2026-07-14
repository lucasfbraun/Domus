export function onlyDigits(value: string): string {
    return value.replace(/\D/g, '');
}

export function formatCpfCnpj(value: string): string {
    const digits = onlyDigits(value).slice(0, 14);

    if (digits.length <= 11) {
        return digits
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    }

    return digits
        .replace(/^(\d{2})(\d)/, '$1.$2')
        .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
        .replace(/\.(\d{3})(\d)/, '.$1/$2')
        .replace(/(\d{4})(\d)/, '$1-$2');
}

/**
 * Formats a Brazilian phone for display (without DDI).
 * Stored values may include leading 55.
 */
export function formatPhone(value: string): string {
    let digits = onlyDigits(value);

    if (digits.startsWith('55') && digits.length > 11) {
        digits = digits.slice(2);
    }

    digits = digits.slice(0, 11);

    if (digits.length <= 10) {
        return digits
            .replace(/(\d{2})(\d)/, '($1) $2')
            .replace(/(\d{4})(\d)/, '$1-$2');
    }

    return digits
        .replace(/(\d{2})(\d)/, '($1) $2')
        .replace(/(\d{5})(\d)/, '$1-$2');
}

export type BrazilianMask = 'cpf-cnpj' | 'phone';

/** Max length of the masked display value (including punctuation). */
export const brazilianMaskMaxLength: Record<BrazilianMask, number> = {
    phone: 15, // (00) 00000-0000
    'cpf-cnpj': 18, // 00.000.000/0000-00
};

export function applyBrazilianMask(value: string, mask: BrazilianMask): string {
    return mask === 'phone' ? formatPhone(value) : formatCpfCnpj(value);
}
