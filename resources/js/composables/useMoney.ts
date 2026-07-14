import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { MoneyConfig } from '@/types/money';

const defaultConfig: MoneyConfig = {
    currency: 'BRL',
    locale: 'pt-BR',
    decimals: 2,
};

export type UseMoneyReturn = {
    config: ReturnType<typeof computed<MoneyConfig>>;
    formatCurrency: (
        value?: number | string | null,
        empty?: string,
    ) => string;
};

export function useMoney(): UseMoneyReturn {
    const page = usePage();
    const config = computed(
        () => (page.props.money as MoneyConfig | undefined) ?? defaultConfig,
    );

    function formatCurrency(
        value?: number | string | null,
        empty = '—',
    ): string {
        if (value === undefined || value === null || value === '') {
            return empty;
        }

        return new Intl.NumberFormat(config.value.locale, {
            style: 'currency',
            currency: config.value.currency,
            minimumFractionDigits: config.value.decimals,
            maximumFractionDigits: config.value.decimals,
        }).format(Number(value));
    }

    return {
        config,
        formatCurrency,
    };
}
