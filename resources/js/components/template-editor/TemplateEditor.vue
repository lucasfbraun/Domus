<script setup lang="ts">
import {
    Bold,
    GripVertical,
    Heading2,
    Italic,
    List,
    ListOrdered,
    Redo2,
    Undo2,
} from '@lucide/vue';
import Placeholder from '@tiptap/extension-placeholder';
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import { computed, onBeforeUnmount, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import {
    prepareTemplateContent,
    variableDragMime,
} from './prepareTemplateContent';
import type { TemplateVariableItem } from './prepareTemplateContent';
import { TemplateVariable } from './templateVariableExtension';

const props = defineProps<{
    name?: string;
    modelValue?: string;
    variables: TemplateVariableItem[];
    placeholder?: string;
    invalid?: boolean;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const knownKeys = computed(() => props.variables.map((item) => item.key));

const groupedVariables = computed(() => {
    const groups = new Map<string, TemplateVariableItem[]>();

    for (const item of props.variables) {
        const list = groups.get(item.group) ?? [];
        list.push(item);
        groups.set(item.group, list);
    }

    return [...groups.entries()].map(([group, items]) => ({ group, items }));
});

const editor = useEditor({
    content: prepareTemplateContent(
        props.modelValue ?? '',
        knownKeys.value,
    ),
    extensions: [
        StarterKit.configure({
            heading: { levels: [2, 3] },
        }),
        Placeholder.configure({
            placeholder:
                props.placeholder ??
                'Escreva o contrato. Arraste variáveis da lateral…',
        }),
        TemplateVariable,
    ],
    editorProps: {
        attributes: {
            class: cn(
                'template-editor-content min-h-72 px-4 py-3 text-sm leading-relaxed focus:outline-none',
            ),
        },
        handleDrop(view, event) {
            const key = event.dataTransfer?.getData(variableDragMime());

            if (!key) {
                return false;
            }

            event.preventDefault();

            const coords = view.posAtCoords({
                left: event.clientX,
                top: event.clientY,
            });

            if (!coords) {
                return true;
            }

            editor.value
                ?.chain()
                .focus()
                .insertContentAt(coords.pos, {
                    type: 'templateVariable',
                    attrs: { key },
                })
                .run();

            return true;
        },
        handleDOMEvents: {
            dragover: (_view, event) => {
                if (
                    event.dataTransfer?.types.includes(variableDragMime())
                ) {
                    event.preventDefault();

                    if (event.dataTransfer) {
                        event.dataTransfer.dropEffect = 'copy';
                    }
                }

                return false;
            },
        },
    },
    onUpdate: ({ editor: current }) => {
        emit('update:modelValue', current.getHTML());
    },
});

watch(
    () => props.modelValue,
    (value) => {
        if (!editor.value || value === undefined) {
            return;
        }

        const current = editor.value.getHTML();

        if (value === current) {
            return;
        }

        editor.value.commands.setContent(
            prepareTemplateContent(value, knownKeys.value),
            { emitUpdate: false },
        );
    },
);

onBeforeUnmount(() => {
    editor.value?.destroy();
});

function insertVariable(key: string): void {
    editor.value?.chain().focus().insertTemplateVariable(key).run();
}

function variableToken(key: string): string {
    return `{{${key}}}`;
}

function onVariableDragStart(event: DragEvent, key: string): void {
    if (!event.dataTransfer) {
        return;
    }

    event.dataTransfer.setData(variableDragMime(), key);
    event.dataTransfer.setData('text/plain', variableToken(key));
    event.dataTransfer.effectAllowed = 'copy';
}

function runCommand(
    command: 'toggleBold' | 'toggleItalic' | 'toggleHeading' | 'toggleBulletList' | 'toggleOrderedList' | 'undo' | 'redo',
    level?: 2 | 3,
): void {
    if (!editor.value) {
        return;
    }

    const chain = editor.value.chain().focus();

    switch (command) {
        case 'toggleBold':
            chain.toggleBold().run();
            break;
        case 'toggleItalic':
            chain.toggleItalic().run();
            break;
        case 'toggleHeading':
            chain.toggleHeading({ level: level ?? 2 }).run();
            break;
        case 'toggleBulletList':
            chain.toggleBulletList().run();
            break;
        case 'toggleOrderedList':
            chain.toggleOrderedList().run();
            break;
        case 'undo':
            chain.undo().run();
            break;
        case 'redo':
            chain.redo().run();
            break;
    }
}
</script>

<template>
    <div
        class="grid grid-cols-1 gap-4 md:grid-cols-5"
        :class="{ 'opacity-90': !editor }"
    >
        <div
            class="min-w-0 overflow-hidden rounded-xl border bg-background shadow-xs md:col-span-3"
            :class="
                invalid
                    ? 'border-destructive ring-3 ring-destructive/20'
                    : 'border-input'
            "
        >
            <div
                class="flex flex-wrap items-center gap-1 border-b border-border/80 bg-muted/40 px-2 py-1.5"
            >
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="size-8"
                    :class="{
                        'bg-accent text-accent-foreground': editor?.isActive(
                            'bold',
                        ),
                    }"
                    :disabled="!editor"
                    aria-label="Negrito"
                    @click="runCommand('toggleBold')"
                >
                    <Bold class="size-4" />
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="size-8"
                    :class="{
                        'bg-accent text-accent-foreground': editor?.isActive(
                            'italic',
                        ),
                    }"
                    :disabled="!editor"
                    aria-label="Itálico"
                    @click="runCommand('toggleItalic')"
                >
                    <Italic class="size-4" />
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="size-8"
                    :class="{
                        'bg-accent text-accent-foreground': editor?.isActive(
                            'heading',
                            { level: 2 },
                        ),
                    }"
                    :disabled="!editor"
                    aria-label="Título"
                    @click="runCommand('toggleHeading', 2)"
                >
                    <Heading2 class="size-4" />
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="size-8"
                    :class="{
                        'bg-accent text-accent-foreground':
                            editor?.isActive('bulletList'),
                    }"
                    :disabled="!editor"
                    aria-label="Lista"
                    @click="runCommand('toggleBulletList')"
                >
                    <List class="size-4" />
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="size-8"
                    :class="{
                        'bg-accent text-accent-foreground':
                            editor?.isActive('orderedList'),
                    }"
                    :disabled="!editor"
                    aria-label="Lista numerada"
                    @click="runCommand('toggleOrderedList')"
                >
                    <ListOrdered class="size-4" />
                </Button>
                <div class="mx-1 h-5 w-px bg-border" />
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="size-8"
                    :disabled="!editor"
                    aria-label="Desfazer"
                    @click="runCommand('undo')"
                >
                    <Undo2 class="size-4" />
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="size-8"
                    :disabled="!editor"
                    aria-label="Refazer"
                    @click="runCommand('redo')"
                >
                    <Redo2 class="size-4" />
                </Button>
            </div>

            <EditorContent :editor="editor" />
        </div>

        <aside
            class="flex h-fit flex-col gap-3 rounded-xl border border-border/80 bg-muted/30 p-3 md:col-span-2 md:sticky md:top-4"
        >
            <div>
                <p class="text-sm font-medium">Variáveis</p>
                <p class="text-xs text-muted-foreground">
                    Arraste para o texto ou clique para inserir.
                </p>
            </div>

            <div class="flex max-h-[28rem] flex-col gap-4 overflow-y-auto pr-1">
                <div
                    v-for="section in groupedVariables"
                    :key="section.group"
                    class="space-y-2"
                >
                    <p
                        class="text-[0.65rem] font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        {{ section.group }}
                    </p>
                    <ul class="space-y-1.5">
                        <li
                            v-for="item in section.items"
                            :key="item.key"
                        >
                            <button
                                type="button"
                                draggable="true"
                                class="group flex w-full cursor-grab items-center gap-2 rounded-lg border border-border/70 bg-background px-2 py-1.5 text-left shadow-xs transition hover:border-primary/40 hover:bg-primary/5 active:cursor-grabbing"
                                :title="variableToken(item.key)"
                                @dragstart="
                                    onVariableDragStart($event, item.key)
                                "
                                @click="insertVariable(item.key)"
                            >
                                <GripVertical
                                    class="size-3.5 shrink-0 text-muted-foreground/70 group-hover:text-primary"
                                />
                                <span class="min-w-0 flex-1">
                                    <span
                                        class="block truncate text-xs font-medium"
                                    >
                                        {{ item.label }}
                                    </span>
                                    <span
                                        class="block truncate font-mono text-[0.65rem] text-muted-foreground"
                                    >
                                        {{ variableToken(item.key) }}
                                    </span>
                                </span>
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </aside>

        <input
            type="hidden"
            :name="name ?? 'content'"
            :value="modelValue ?? ''"
        />
    </div>
</template>

<style>
.tiptap p.is-editor-empty:first-child::before {
    color: var(--muted-foreground);
    content: attr(data-placeholder);
    float: left;
    height: 0;
    pointer-events: none;
}

.template-editor-content h2 {
    margin: 0.75rem 0 0.5rem;
    font-size: 1.125rem;
    font-weight: 600;
    line-height: 1.4;
}

.template-editor-content h3 {
    margin: 0.65rem 0 0.4rem;
    font-size: 1rem;
    font-weight: 600;
    line-height: 1.4;
}

.template-editor-content p {
    margin: 0.4rem 0;
}

.template-editor-content ul,
.template-editor-content ol {
    margin: 0.5rem 0;
    padding-left: 1.25rem;
}

.template-editor-content ul {
    list-style: disc;
}

.template-editor-content ol {
    list-style: decimal;
}

.template-editor-content li {
    margin: 0.2rem 0;
}

.template-editor-content .template-variable {
    display: inline-flex;
    align-items: center;
    border-radius: 0.375rem;
    background: color-mix(in oklab, var(--primary) 12%, transparent);
    padding: 0.1rem 0.4rem;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--primary);
    vertical-align: baseline;
    white-space: nowrap;
}
</style>
