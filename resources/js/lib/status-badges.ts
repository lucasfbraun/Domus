export type StatusType =
    | 'contract'
    | 'charge'
    | 'deposit'
    | 'property'
    | 'tenant'
    | 'signature'
    | 'occurrence';

type StatusConfig = {
    label: string;
    className: string;
};

const statusColors = {
    success:
        'border-transparent bg-emerald-500/15 text-emerald-700 dark:text-emerald-400',
    warning:
        'border-transparent bg-amber-500/15 text-amber-700 dark:text-amber-400',
    danger: 'border-transparent bg-red-500/15 text-red-700 dark:text-red-400',
    info: 'border-transparent bg-blue-500/15 text-blue-700 dark:text-blue-400',
    neutral: 'border-transparent bg-muted text-muted-foreground',
    primary:
        'border-transparent bg-primary/15 text-primary dark:text-primary',
} as const;

const statusMaps: Record<StatusType, Record<string, StatusConfig>> = {
    contract: {
        draft: { label: 'Rascunho', className: statusColors.neutral },
        active: { label: 'Ativo', className: statusColors.success },
        expiring: { label: 'Vence em breve', className: statusColors.warning },
        closed: { label: 'Encerrado', className: statusColors.neutral },
        cancelled: { label: 'Cancelado', className: statusColors.danger },
    },
    charge: {
        open: { label: 'Aberta', className: statusColors.info },
        waiting_payment: {
            label: 'Aguardando pagamento',
            className: statusColors.warning,
        },
        paid: { label: 'Paga', className: statusColors.success },
        overdue: { label: 'Vencida', className: statusColors.danger },
        cancelled: { label: 'Cancelada', className: statusColors.neutral },
    },
    deposit: {
        pending: { label: 'Pendente', className: statusColors.neutral },
        waiting_payment: {
            label: 'Aguardando pagamento',
            className: statusColors.warning,
        },
        paid: { label: 'Paga', className: statusColors.success },
        refunded: { label: 'Devolvida', className: statusColors.info },
    },
    property: {
        available: { label: 'Disponível', className: statusColors.success },
        rented: { label: 'Alugado', className: statusColors.info },
        maintenance: {
            label: 'Em manutenção',
            className: statusColors.warning,
        },
        inactive: { label: 'Inativo', className: statusColors.neutral },
    },
    tenant: {
        active: { label: 'Ativo', className: statusColors.success },
        inactive: { label: 'Inativo', className: statusColors.neutral },
        delinquent: { label: 'Inadimplente', className: statusColors.danger },
        former: { label: 'Ex-inquilino', className: statusColors.neutral },
    },
    signature: {
        not_generated: {
            label: 'Não gerado',
            className: statusColors.neutral,
        },
        awaiting_signature: {
            label: 'Aguardando assinatura',
            className: statusColors.warning,
        },
        in_review: { label: 'Em análise', className: statusColors.warning },
        approved: { label: 'Aprovado', className: statusColors.success },
        rejected: { label: 'Rejeitado', className: statusColors.danger },
    },
    occurrence: {
        open: { label: 'Aberta', className: statusColors.info },
        in_review: { label: 'Em análise', className: statusColors.warning },
        resolved: { label: 'Resolvida', className: statusColors.success },
        closed: { label: 'Fechada', className: statusColors.neutral },
    },
};

export function getStatusConfig(
    type: StatusType,
    status?: string | null,
): StatusConfig | null {
    if (!status) {
        return null;
    }

    return statusMaps[type][status] ?? null;
}

export function getStatusLabel(
    type: StatusType,
    status?: string | null,
): string {
    return getStatusConfig(type, status)?.label ?? status ?? '—';
}

export function getStatusClassName(
    type: StatusType,
    status?: string | null,
): string {
    return (
        getStatusConfig(type, status)?.className ?? statusColors.neutral
    );
}
