<script setup lang="ts">
import { computed } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import type { User } from '@/types';

type Props = {
    user: User;
    showEmail?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    showEmail: false,
});

const { getInitials } = useInitials();

const showAvatar = computed(
    () => props.user.avatar && props.user.avatar !== '',
);
</script>

<template>
    <Avatar class="h-8 w-8 overflow-hidden rounded-full ring-1 ring-border/70">
        <AvatarImage v-if="showAvatar" :src="user.avatar!" :alt="user.name" />
        <AvatarFallback
            class="rounded-full bg-primary/10 text-xs font-medium text-primary"
        >
            {{ getInitials(user.name) }}
        </AvatarFallback>
    </Avatar>

    <div class="grid min-w-0 flex-1 text-left text-sm leading-tight">
        <span class="truncate font-medium text-sidebar-foreground">{{
            user.name
        }}</span>
        <span
            v-if="showEmail"
            class="truncate text-[11px] text-muted-foreground"
            >{{ user.email }}</span
        >
    </div>
</template>
