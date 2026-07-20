<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
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
            class="relative z-10 w-full max-w-[26rem] motion-safe:animate-in motion-safe:fade-in motion-safe:slide-in-from-bottom-2 motion-safe:duration-500"
        >
            <div class="mb-8 flex flex-col items-center gap-4 text-center">
                <Link
                    :href="home()"
                    class="flex items-center gap-2.5 font-medium tracking-tight"
                >
                    <span
                        class="flex size-11 items-center justify-center rounded-2xl bg-primary/10 text-primary ring-1 ring-primary/15"
                    >
                        <AppLogoIcon class="size-6 fill-current" />
                    </span>
                    <span>{{ name }}</span>
                </Link>

                <div class="space-y-2">
                    <h1 class="text-2xl font-semibold tracking-tight">
                        {{ title }}
                    </h1>
                    <p class="text-sm leading-relaxed text-muted-foreground">
                        {{ description }}
                    </p>
                </div>
            </div>

            <div
                class="auth-form rounded-3xl border border-border/70 bg-card/90 p-7 shadow-[0_24px_60px_-28px_hsl(221_40%_20%/0.28)] backdrop-blur-sm sm:p-9 [&_[data-slot=input]]:h-11 [&_[data-slot=input]]:rounded-xl [&_[data-slot=input]]:bg-background [&_[data-slot=input]]:px-3.5 [&_button[data-slot=button]]:h-11 [&_button[data-slot=button]]:rounded-xl"
            >
                <slot />
            </div>
        </div>
    </div>
</template>
