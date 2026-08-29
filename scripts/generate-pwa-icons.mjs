import { mkdir, readFile, writeFile } from 'node:fs/promises';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import sharp from 'sharp';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const sourceIcon = resolve(root, 'public/favicon.svg');
const outputDir = resolve(root, 'public/icons');

const BRAND = '#2D52A9';

/**
 * The favicon draws the Domus glyph inside a rounded square. Maskable icons are
 * cropped to a circle by the launcher, so that variant re-lays the same glyph
 * full-bleed and shrunk into the 80% safe zone.
 */
async function buildMaskableSource() {
    const favicon = await readFile(sourceIcon, 'utf8');
    const glyph = favicon.match(/<g[\s\S]*<\/g>/);

    if (!glyph) {
        throw new Error('Could not extract the glyph group from public/favicon.svg');
    }

    const rescaled = glyph[0].replace(
        /transform="translate\([^)]*\) scale\([^)]*\)"/,
        'transform="translate(8 8) scale(0.25)"',
    );

    return Buffer.from(
        `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" fill="none">` +
            `<rect width="32" height="32" fill="${BRAND}"/>` +
            `${rescaled}</svg>`,
    );
}

async function render(source, size, file) {
    const png = await sharp(source, { density: 512 })
        .resize(size, size, { fit: 'contain', background: { r: 0, g: 0, b: 0, alpha: 0 } })
        .png({ compressionLevel: 9 })
        .toBuffer();

    await writeFile(resolve(outputDir, file), png);

    return `${file} (${size}x${size}, ${png.length} bytes)`;
}

const anySource = await readFile(sourceIcon);
const maskableSource = await buildMaskableSource();

await mkdir(outputDir, { recursive: true });

const written = await Promise.all([
    render(anySource, 192, 'icon-192.png'),
    render(anySource, 512, 'icon-512.png'),
    render(maskableSource, 192, 'icon-maskable-192.png'),
    render(maskableSource, 512, 'icon-maskable-512.png'),
]);

console.log(written.join('\n'));
