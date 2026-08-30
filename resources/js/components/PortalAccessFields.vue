<script setup lang="ts">
import { computed } from 'vue';
import FormSelect from '@/components/FormSelect.vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';

export type LinkableUser = {
    id: number;
    name: string;
    email: string;
    roles: string[];
};

export type LinkedUser = {
    id: number;
    name: string;
    email: string;
    roles?: string[];
};

export type PortalAccessMode = 'none' | 'new' | 'existing';

const props = defineProps<{
    users?: LinkableUser[];
    linkedUser?: LinkedUser | null;
    errors: Record<string, string | undefined>;
    idPrefix: string;
}>();

const mode = defineModel<PortalAccessMode>('mode', { required: true });

const userOptions = computed(() =>
    (props.users ?? []).map((user) => ({
        value: user.id,
        label: `${user.name} (${user.email}) — ${
            user.roles.length ? user.roles.join(', ') : 'sem papel'
        }`,
    })),
);
</script>

<template>
    <div class="space-y-4 rounded-xl border border-border/80 bg-muted/30 p-5">
        <div
            v-if="linkedUser"
            class="rounded-lg border border-dashed border-border/80 bg-background px-3 py-2 text-sm text-muted-foreground"
        >
            Login atual:
            <span class="font-medium text-foreground">{{
                linkedUser.email
            }}</span>
            <span v-if="linkedUser.roles?.length">
                (papéis: {{ linkedUser.roles.join(', ') }})
            </span>
        </div>

        <div class="grid gap-3">
            <Label>Acesso ao portal</Label>
            <RadioGroup
                :model-value="mode"
                class="grid gap-3"
                @update:model-value="mode = $event as PortalAccessMode"
            >
                <Label
                    :for="`${idPrefix}-portal-none`"
                    class="flex cursor-pointer items-center gap-2 rounded-lg border border-border/80 bg-card px-3 py-2.5 font-normal hover:bg-accent/50"
                >
                    <RadioGroupItem
                        :id="`${idPrefix}-portal-none`"
                        value="none"
                    />
                    Sem acesso ao portal
                </Label>
                <Label
                    :for="`${idPrefix}-portal-new`"
                    class="flex cursor-pointer items-center gap-2 rounded-lg border border-border/80 bg-card px-3 py-2.5 font-normal hover:bg-accent/50"
                >
                    <RadioGroupItem
                        :id="`${idPrefix}-portal-new`"
                        value="new"
                    />
                    Criar novo login
                </Label>
                <Label
                    :for="`${idPrefix}-portal-existing`"
                    class="flex cursor-pointer items-center gap-2 rounded-lg border border-border/80 bg-card px-3 py-2.5 font-normal hover:bg-accent/50"
                >
                    <RadioGroupItem
                        :id="`${idPrefix}-portal-existing`"
                        value="existing"
                    />
                    Vincular a usuário existente
                </Label>
            </RadioGroup>
        </div>

        <div v-if="mode === 'new'" class="grid gap-4">
            <div class="grid gap-2">
                <Label :for="`${idPrefix}-password`">Senha do portal</Label>
                <Input
                    :id="`${idPrefix}-password`"
                    type="password"
                    name="password"
                    autocomplete="new-password"
                    placeholder="Senha para acesso ao portal"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label :for="`${idPrefix}-password_confirmation`">
                    Confirmar senha
                </Label>
                <Input
                    :id="`${idPrefix}-password_confirmation`"
                    type="password"
                    name="password_confirmation"
                    autocomplete="new-password"
                    placeholder="Confirmar senha do portal"
                />
                <InputError :message="errors.password_confirmation" />
            </div>
        </div>

        <div v-if="mode === 'existing'" class="grid gap-2">
            <Label :for="`${idPrefix}-existing_user_id`">
                Usuário existente
            </Label>
            <FormSelect
                :id="`${idPrefix}-existing_user_id`"
                name="existing_user_id"
                :options="userOptions"
                :default-value="linkedUser?.id"
                placeholder="Selecione um usuário"
            />
            <InputError :message="errors.existing_user_id" />
        </div>
    </div>
</template>
