/**
 * Post-build sanitizer for old WebKit (iOS 12 / Safari < 13).
 *
 * Terser collapses ternaries like `a ? 0.2 : 1` to `a?.2:1`. That WebKit's
 * parser reads the `?.` as optional chaining and throws
 * "SyntaxError: Unexpected token '.'", which aborts the whole bundle and leaves
 * pages rendering only the Blade header. `keep_numbers` prevents it in code
 * Terser re-minifies, but a few pre-minified node_modules ship `?.<digit>`
 * already and slip through. Optional chaining is NEVER followed by a digit
 * (`obj?.2` is invalid JS), so inserting a space — `?.<digit>` -> `? .<digit>`
 * — is a safe, unambiguous fix that only touches ternaries.
 */
const fs = require('fs');
const path = require('path');

const targets = [
    path.join(__dirname, 'public', 'cdn', 'js', 'app.js'),
    path.join(__dirname, 'public', 'cdn', 'js', 'vendor.js'),
    path.join(__dirname, 'public', 'cdn', 'js', 'manifest.js'),
];

let totalFixed = 0;
for (const file of targets) {
    if (!fs.existsSync(file)) continue;
    const src = fs.readFileSync(file, 'utf8');
    let count = 0;
    const out = src.replace(/\?\.(\d)/g, (_m, d) => { count++; return '? .' + d; });
    if (count > 0) {
        fs.writeFileSync(file, out, 'utf8');
        totalFixed += count;
        console.log(`[fix-ios12] ${path.basename(file)}: rewrote ${count} ternary "?.<digit>" → "? .<digit>"`);
    }
}
console.log(`[fix-ios12] done — ${totalFixed} fix(es) applied.`);
