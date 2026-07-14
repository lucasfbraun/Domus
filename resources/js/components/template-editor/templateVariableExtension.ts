import { Node, mergeAttributes } from '@tiptap/core';

export type TemplateVariableAttrs = {
    key: string;
};

declare module '@tiptap/core' {
    interface Commands<ReturnType> {
        templateVariable: {
            insertTemplateVariable: (key: string) => ReturnType;
        };
    }
}

export const TemplateVariable = Node.create({
    name: 'templateVariable',
    group: 'inline',
    inline: true,
    atom: true,
    selectable: true,

    addAttributes() {
        return {
            key: {
                default: null,
                parseHTML: (element) =>
                    element.getAttribute('data-template-variable'),
                renderHTML: (attributes) => {
                    if (!attributes.key) {
                        return {};
                    }

                    return {
                        'data-template-variable': attributes.key,
                    };
                },
            },
        };
    },

    parseHTML() {
        return [
            {
                tag: 'span[data-template-variable]',
            },
        ];
    },

    renderHTML({ node, HTMLAttributes }) {
        return [
            'span',
            mergeAttributes(HTMLAttributes, {
                class: 'template-variable',
                contenteditable: 'false',
            }),
            `{{${node.attrs.key}}}`,
        ];
    },

    renderText({ node }) {
        return `{{${node.attrs.key}}}`;
    },

    addCommands() {
        return {
            insertTemplateVariable:
                (key: string) =>
                ({ commands }) =>
                    commands.insertContent({
                        type: this.name,
                        attrs: { key },
                    }),
        };
    },
});
