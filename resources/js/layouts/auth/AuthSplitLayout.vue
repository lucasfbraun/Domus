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
        class="relative grid min-h-dvh overflow-hidden bg-background lg:grid-cols-[minmax(0,1.05fr)_minmax(0,0.95fr)]"
    >
        <!-- Brand panel -->
        <aside
            class="relative hidden flex-col justify-between overflow-hidden p-10 text-white lg:flex xl:p-14"
        >
            <div
                class="absolute inset-0 bg-gradient-to-br from-primary via-[hsl(221_75%_38%)] to-[hsl(224_70%_18%)]"
            />
            <div
                class="absolute -top-28 -right-20 size-[28rem] rounded-full bg-white/10 blur-3xl"
            />
            <div
                class="absolute top-1/3 -left-24 size-72 rounded-full bg-sky-300/20 blur-3xl"
            />
            <div
                class="absolute -right-10 bottom-0 size-80 rounded-[40%] bg-white/5 blur-2xl"
            />
            <div
                class="pointer-events-none absolute inset-0 opacity-[0.12]"
                style="
                    background-image: radial-gradient(
                        circle at 1px 1px,
                        white 1px,
                        transparent 0
                    );
                    background-size: 28px 28px;
                "
            />

            <Link
                :href="home()"
                class="relative z-10 flex items-center gap-3 text-lg font-medium tracking-tight transition-opacity hover:opacity-90"
            >
                <span
                    class="flex size-11 items-center justify-center rounded-2xl bg-white/15 ring-1 ring-white/25 backdrop-blur-sm"
                >
                    <AppLogoIcon class="size-7 fill-current text-white" />
                </span>
                {{ name }}
            </Link>

            <div class="relative z-10 max-w-md space-y-5">
                <h2
                    class="text-4xl font-semibold tracking-tight text-balance xl:text-5xl"
                >
                    Seus imóveis, sob controle.
                </h2>
                <p class="max-w-[36ch] text-base leading-relaxed text-white/75">
                    Contratos, pagamentos e ocupação em um só lugar, com a
                    clareza que a gestão pede.
                </p>
            </div>

            <p class="relative z-10 text-sm text-white/55">
                Gestão imobiliária sem fricção.
            </p>
        </aside>

        <!-- Form panel -->
        <main
            class="relative flex flex-col items-center justify-center px-6 py-12 sm:px-10"
        >
            <div
                class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top,hsl(214_95%_96%),transparent_55%),radial-gradient(ellipse_at_bottom_right,hsl(221_83%_97%),transparent_50%)]"
            />

            <div
                class="relative z-10 w-full max-w-[26rem] motion-safe:animate-in motion-safe:fade-in motion-safe:slide-in-from-bottom-2 motion-safe:duration-500"
            >
                <Link
                    :href="home()"
                    class="mb-8 flex items-center gap-2.5 font-medium tracking-tight lg:hidden"
                >
                    <span
                        class="flex size-10 items-center justify-center rounded-2xl bg-primary/10 text-primary ring-1 ring-primary/15"
                    >
                        <AppLogoIcon class="size-6 fill-current" />
                    </span>
                    <span>{{ name }}</span>
                </Link>

                <div
                    class="rounded-3xl border border-border/70 bg-card/90 p-7 shadow-[0_24px_60px_-28px_hsl(221_40%_20%/0.28)] backdrop-blur-sm sm:p-9"
                >
                    <div class="mb-8 space-y-2">
                        <h1
                            v-if="title"
                            class="text-2xl font-semibold tracking-tight text-foreground"
                        >
                            {{ title }}
                        </h1>
                        <p
                            v-if="description"
                            class="text-sm leading-relaxed text-muted-foreground"
                        >
                            {{ description }}
                        </p>
                    </div>

                    <div
                        class="auth-form [&_[data-slot=input]]:h-11 [&_[data-slot=input]]:rounded-xl [&_[data-slot=input]]:bg-background [&_[data-slot=input]]:px-3.5 [&_button[data-slot=button]]:h-11 [&_button[data-slot=button]]:rounded-xl"
                    >
                        <slot />
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
