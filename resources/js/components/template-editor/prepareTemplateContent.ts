export type TemplateVariableItem = {
    key: string;
    label: string;
    group: string;
};

const VARIABLE_MIME = 'application/x-template-variable';

export function variableDragMime(): string {
    return VARIABLE_MIME;
}

export function prepareTemplateContent(
    raw: string,
    knownKeys: string[],
): string {
    const trimmed = raw.trim();

    if (trimmed === '') {
        return '';
    }

    const keySet = new Set(knownKeys);
    const hasHtml = /<(p|h[1-3]|ul|ol|div|br|span)\b/i.test(raw);

    let html = hasHtml
        ? raw
        : raw
              .split(/\n{2,}/)
              .map(
                  (block) =>
                      `<p>${escapeHtml(block).replace(/\n/g, '<br>')}</p>`,
              )
              .join('');

    html = html.replace(/\{\{\s*(\w+)\s*\}\}/g, (match, key: string) => {
        if (!keySet.has(key)) {
            return match;
        }

        return `<span data-template-variable="${key}">{{${key}}}</span>`;
    });

    return html;
}

function escapeHtml(value: string): string {
    return value
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}
