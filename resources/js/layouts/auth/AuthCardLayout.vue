<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { home } from '@/routes';

const page = usePage();
const name = page.props.name;

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative flex min-h-dvh flex-col items-center justify-center overflow-hidden px-6 py-12"
    >
        <div
            class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top,hsl(214_95%_96%),transparent_55%),radial-gradient(ellipse_at_bottom_right,hsl(221_83%_97%),transparent_50%)]"
        />

        <div
            class="relative z-10 flex w-full max-w-md flex-col gap-6 motion-safe:animate-in motion-safe:fade-in motion-safe:slide-in-from-bottom-2 motion-safe:duration-500"
        >
            <Link
                :href="home()"
                class="flex items-center justify-center gap-2.5 self-center font-medium tracking-tight"
            >
                <span
                    class="flex size-11 items-center justify-center rounded-2xl bg-primary/10 text-primary ring-1 ring-primary/15"
                >
                    <AppLogoIcon class="size-6 fill-current" />
                </span>
                <span>{{ name }}</span>
            </Link>

            <Card
                class="rounded-3xl border-border/70 shadow-[0_24px_60px_-28px_hsl(221_40%_20%/0.28)]"
            >
                <CardHeader class="px-8 pt-8 pb-0 text-center sm:px-10">
                    <CardTitle class="text-2xl font-semibold tracking-tight">
                        {{ title }}
                    </CardTitle>
                    <CardDescription class="leading-relaxed">
                        {{ description }}
                    </CardDescription>
                </CardHeader>
                <CardContent
                    class="auth-form px-8 py-8 sm:px-10 [&_[data-slot=input]]:h-11 [&_[data-slot=input]]:rounded-xl [&_[data-slot=input]]:bg-background [&_[data-slot=input]]:px-3.5 [&_button[data-slot=button]]:h-11 [&_button[data-slot=button]]:rounded-xl"
                >
                    <slot />
                </CardContent>
            </Card>
        </div>
    </div>
</template>
