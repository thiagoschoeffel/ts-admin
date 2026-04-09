import { promises as fs } from "node:fs";
import path from "node:path";

// We only support OUTLINE icons. Script will scan Blade views to find usage
// and copy just those into resources/svg/heroicons/outline, cleaning leftovers.

const HEROICONS_PKG = path.resolve("node_modules", "heroicons");
const OUTLINE_SOURCES = ["optimized/24/outline", "24/outline"];
const DEST_ROOT = path.resolve("resources", "svg", "heroicons");
const DEST_OUTLINE = path.join(DEST_ROOT, "outline");

const VIEW_DIR = path.resolve("resources", "views");
const JS_DIR = path.resolve("resources", "js");

const globTemplateFiles = async (dir) => {
    const out = [];
    const entries = await fs.readdir(dir, { withFileTypes: true });
    for (const entry of entries) {
        const p = path.join(dir, entry.name);
        if (entry.isDirectory()) {
            out.push(...(await globTemplateFiles(p)));
        } else if (
            entry.isFile() &&
            (p.endsWith(".blade.php") ||
                p.endsWith(".php") ||
                p.endsWith(".vue"))
        ) {
            out.push(p);
        }
    }
    return out;
};

const extractOutlineIconNames = async () => {
    const bladeFiles = await globTemplateFiles(VIEW_DIR);
    const vueFiles = await globTemplateFiles(JS_DIR);
    const allFiles = [...bladeFiles, ...vueFiles];
    const names = new Set();

    // Regex para tags x-heroicon (Blade)
    const bladeTagRegex =
        /<x-heroicon\b[^>]*\bname\s*=\s*(["'])(.*?)\1[^>]*>/gims;
    const bladeVariantRegex = /\bvariant\s*=\s*(["'])(.*?)\1/ims;

    // Regex para componente HeroIcon (Vue)
    const vueTagRegex = /<HeroIcon\b[^>]*\bname\s*=\s*(["'])(.*?)\1[^>]*>/gims;

    for (const file of allFiles) {
        const content = await fs.readFile(file, "utf8");

        // Processar tags Blade
        let match;
        while ((match = bladeTagRegex.exec(content)) !== null) {
            const name = match[2];
            const tag = match[0];
            const variantMatch = tag.match(bladeVariantRegex);
            const variant = variantMatch
                ? String(variantMatch[2]).toLowerCase()
                : "outline";
            if (variant === "outline" || variant === "") {
                names.add(name);
            }
        }

        // Processar componentes Vue
        while ((match = vueTagRegex.exec(content)) !== null) {
            const name = match[2];
            // Verificar se é uma expressão condicional simples
            const conditionalMatch = name.match(
                /(.+)\s*\?\s*(["'])(.*?)\2\s*:\s*(["'])(.*?)\4/
            );
            if (conditionalMatch) {
                // Extrair os dois nomes de ícones da expressão condicional
                names.add(conditionalMatch[3]); // primeiro ícone (true)
                names.add(conditionalMatch[5]); // segundo ícone (false)
            } else {
                names.add(name);
            }
        }
    }
    return Array.from(names).sort();
};

const resolveOutlineSourceDir = async () => {
    for (const rel of OUTLINE_SOURCES) {
        const candidate = path.join(HEROICONS_PKG, rel);
        try {
            await fs.access(candidate);
            return candidate;
        } catch {}
    }
    throw new Error(
        "Heroicons outline directory not found. Check package structure."
    );
};

const emptyDir = async (dir) => {
    await fs.rm(dir, { recursive: true, force: true });
    await fs.mkdir(dir, { recursive: true });
};

const copyOutlineIcons = async (icons) => {
    const src = await resolveOutlineSourceDir();
    await emptyDir(DEST_OUTLINE);

    const missing = [];
    let copied = 0;
    for (const name of icons) {
        const file = `${name}.svg`;
        const from = path.join(src, file);
        const to = path.join(DEST_OUTLINE, file);
        try {
            await fs.copyFile(from, to);
            copied++;
        } catch {
            missing.push(name);
        }
    }

    return { copied, missing };
};

const main = async () => {
    try {
        const icons = await extractOutlineIconNames();
        if (icons.length === 0) {
            console.log(
                "No outline icons detected in views or Vue components; cleaning destination."
            );
            await emptyDir(DEST_OUTLINE);
            return;
        }

        await fs.mkdir(DEST_ROOT, { recursive: true });
        const { copied, missing } = await copyOutlineIcons(icons);

        console.log(`Copied ${copied} outline icons.`);
        if (missing.length > 0) {
            console.warn(
                "Missing icons (not found in heroicons package):",
                missing.join(", ")
            );
        }
        console.log("Heroicons outline publish completed.");
    } catch (err) {
        console.error(err.message || String(err));
        process.exitCode = 1;
    }
};

main();
