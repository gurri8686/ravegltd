{{-- ============================================================
     Theme Color Configurator — Right-side Drawer
     All 11 color variables exposed individually, grouped by area.
     Toggle via: window.toggleColorPicker()
     ============================================================ --}}

{{-- Dim overlay --}}
<div id="cpOverlay" onclick="toggleColorPicker()"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.18);z-index:1040;"></div>

{{-- Right-side drawer --}}
<div id="colorPickerDrawer"
     style="position:fixed;top:0;right:0;width:300px;height:100vh;z-index:1041;
            background:#fff;box-shadow:-6px 0 28px rgba(0,0,0,0.15);
            display:flex;flex-direction:column;font-family:inherit;
            transform:translateX(100%);transition:transform 0.28s cubic-bezier(.4,0,.2,1);">

    {{-- Header --}}
    <div style="background:var(--color-deeper);padding:14px 18px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
        <span style="color:#fff;font-size:14px;font-weight:600;letter-spacing:0.2px;">
            <i class="feather icon-sliders" style="margin-right:7px;"></i> Theme Colors
        </span>
        <button onclick="toggleColorPicker()" title="Close"
                style="background:none;border:none;color:rgba(255,255,255,0.8);font-size:22px;line-height:1;cursor:pointer;padding:0;">&times;</button>
    </div>

    {{-- Hint --}}
    <div style="padding:10px 18px 8px;border-bottom:1px solid #f0f0f0;flex-shrink:0;background:#fafafa;">
        <p style="margin:0;font-size:11.5px;color:#777;line-height:1.5;">
            Type a hex code or click the swatch to pick. <strong>Save</strong> keeps changes after reload.
        </p>
    </div>

    {{-- Scrollable color rows --}}
    <div id="color-picker-rows" style="flex:1;overflow-y:auto;padding:0 18px 12px;"></div>

    {{-- Footer --}}
    <div style="padding:12px 18px;border-top:1px solid #eee;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;background:#fafafa;">
        <button id="cpResetBtn"
                style="background:#fff;border:1px solid #ddd;color:#555;padding:6px 12px;border-radius:6px;font-size:12.5px;cursor:pointer;">
            <i class="feather icon-rotate-ccw" style="font-size:11px;margin-right:3px;"></i> Reset
        </button>
        <div style="display:flex;gap:8px;align-items:center;">
            <span id="cpSavedMsg" style="color:#22a85a;font-size:12px;font-weight:600;display:none;">Saved!</span>
            <button id="cpSaveBtn"
                    style="background:var(--color-primary);border:none;color:#fff;padding:6px 16px;border-radius:6px;font-size:12.5px;font-weight:600;cursor:pointer;">
                <i class="feather icon-save" style="font-size:11px;margin-right:3px;"></i> Save
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    // ─── All color variables grouped by area ────────────────────────────────
    var GROUPS = [
        {
            heading: 'Navigation',
            vars: [
                { key: '--color-deeper',         label: 'Background',       desc: 'Sidebar · Header · Footer' },
                { key: '--color-dark',            label: 'Active / Hover',   desc: 'Active menu · Table header' },
                { key: '--color-on-dark',         label: 'Text on Nav',      desc: 'Icons & text in sidebar' },
                { key: '--color-nav-section',     label: 'Section Labels',   desc: 'Group headers in sidebar' },
                { key: '--color-sidebar-search',  label: 'Search BG',        desc: 'Sidebar search input' },
                { key: '--color-light',           label: 'Active Border',    desc: 'Left accent line on active item' },
            ]
        },
        {
            heading: 'Buttons & Accents',
            vars: [
                { key: '--color-primary',       label: 'Primary / Save',    desc: 'Main buttons · active state' },
                { key: '--color-primary-hover',  label: 'Button Hover',      desc: 'Darker shade on hover/focus' },
                { key: '--color-secondary',     label: 'Edit Buttons',      desc: 'Edit / warning actions' },
            ]
        },
        {
            heading: 'Page & Text',
            vars: [
                { key: '--color-bg',                 label: 'Page Background',   desc: 'Content area background' },
                { key: '--color-text-body',          label: 'Body Text',         desc: 'Form inputs · general text' },
                { key: '--color-text-muted',         label: 'Muted Text',        desc: 'Labels · secondary text' },
                { key: '--color-table-header-text',  label: 'Table Header Text', desc: 'Invoice & data table header text' },
            ]
        },
    ];

    // Flat list of all keys (for save/reset)
    var ALL_KEYS = [];
    GROUPS.forEach(function(g){ g.vars.forEach(function(v){ ALL_KEYS.push(v.key); }); });

    // ─── Color helpers ──────────────────────────────────────────────────────
    function toHex(color) {
        if (!color) return '#000000';
        color = color.trim();
        if (/^#[0-9a-fA-F]{6}$/.test(color)) return color;
        var ctx = document.createElement('canvas').getContext('2d');
        ctx.fillStyle = color;
        return ctx.fillStyle.slice(0, 7);
    }
    function setVar(k, v) { document.documentElement.style.setProperty(k, v); }
    function getVar(k)    { return getComputedStyle(document.documentElement).getPropertyValue(k).trim(); }

    // ─── Apply a validated hex to CSS + all UI elements for a key ──────────
    function applyHex(hex, key) {
        setVar(key, hex);
        var slug = key.replace('--', '');
        var sw = document.getElementById('cp-swatch-' + slug);
        var ci = document.getElementById('cp-input-'  + slug);
        var hi = document.getElementById('cp-hex-'    + slug);
        if (sw) sw.style.background = hex;
        if (ci) ci.value = hex;
        if (hi) hi.value = hex;
    }

    // ─── Drawer open/close ──────────────────────────────────────────────────
    var isOpen = false, built = false;

    window.toggleColorPicker = function () {
        isOpen = !isOpen;
        var drawer  = document.getElementById('colorPickerDrawer');
        var overlay = document.getElementById('cpOverlay');
        if (isOpen) {
            overlay.style.display = 'block';
            drawer.style.transform = 'translateX(0)';
            buildRows();
        } else {
            drawer.style.transform = 'translateX(100%)';
            overlay.style.display = 'none';
        }
    };

    // ─── Build rows (once); refresh values on re-open ───────────────────────
    function buildRows() {
        if (built) {
            ALL_KEYS.forEach(function(key) {
                var slug = key.replace('--', '');
                var hex  = toHex(getVar(key));
                var sw   = document.getElementById('cp-swatch-' + slug);
                var ci   = document.getElementById('cp-input-'  + slug);
                var hi   = document.getElementById('cp-hex-'    + slug);
                if (sw) sw.style.background = hex;
                if (ci) ci.value = hex;
                if (hi) hi.value = hex;
            });
            return;
        }
        built = true;

        var container = document.getElementById('color-picker-rows');
        container.innerHTML = '';

        GROUPS.forEach(function(group) {
            // ── Section heading ────────────────────────────────────────────
            var heading = document.createElement('div');
            heading.textContent = group.heading;
            heading.style.cssText = 'font-size:10px;font-weight:700;text-transform:uppercase;' +
                                    'letter-spacing:0.8px;color:#aaa;padding:14px 0 6px;' +
                                    'border-bottom:1px solid #eee;margin-bottom:2px;';
            container.appendChild(heading);

            // ── Color rows ─────────────────────────────────────────────────
            group.vars.forEach(function(v) {
                var slug     = v.key.replace('--', '');
                var inputId  = 'cp-input-'  + slug;
                var hexId    = 'cp-hex-'    + slug;
                var swatchId = 'cp-swatch-' + slug;
                var currentHex = toHex(getVar(v.key));

                var row = document.createElement('div');
                row.style.cssText = 'padding:9px 0;border-bottom:1px solid #f7f7f7;';

                // Top: label + desc
                var topLine = document.createElement('div');
                topLine.style.cssText = 'display:flex;align-items:baseline;justify-content:space-between;margin-bottom:6px;';

                var lbl = document.createElement('label');
                lbl.htmlFor     = hexId;
                lbl.textContent = v.label;
                lbl.style.cssText = 'margin:0;font-size:12.5px;color:#222;font-weight:500;cursor:pointer;';

                var desc = document.createElement('span');
                desc.textContent = v.desc;
                desc.style.cssText = 'font-size:10px;color:#bbb;';

                topLine.appendChild(lbl);
                topLine.appendChild(desc);

                // Bottom: hex input + swatch + hidden picker
                var bottomLine = document.createElement('div');
                bottomLine.style.cssText = 'display:flex;align-items:center;gap:6px;';

                var hexInput = document.createElement('input');
                hexInput.type        = 'text';
                hexInput.id          = hexId;
                hexInput.value       = currentHex;
                hexInput.maxLength   = 7;
                hexInput.placeholder = '#rrggbb';
                hexInput.dataset.varKey = v.key;
                hexInput.style.cssText = 'flex:1;height:28px;padding:0 7px;border:1px solid #ddd;border-radius:5px;' +
                                         'font-family:monospace;font-size:12px;color:#333;letter-spacing:0.4px;outline:none;';

                var swatch = document.createElement('div');
                swatch.id    = swatchId;
                swatch.title = 'Click to open color picker';
                swatch.style.cssText = 'width:28px;height:28px;border-radius:5px;border:1px solid rgba(0,0,0,0.12);' +
                                       'background:' + currentHex + ';cursor:pointer;flex-shrink:0;';

                var picker = document.createElement('input');
                picker.type  = 'color';
                picker.id    = inputId;
                picker.value = currentHex;
                picker.dataset.varKey = v.key;
                picker.style.cssText  = 'position:absolute;opacity:0;width:0;height:0;pointer-events:none;';

                picker.addEventListener('input', function() {
                    applyHex(this.value, this.dataset.varKey);
                });
                swatch.addEventListener('click', function() { picker.click(); });

                hexInput.addEventListener('input', function() {
                    var raw = this.value.trim();
                    var normalized = raw.startsWith('#') ? raw : '#' + raw;
                    var valid = /^#[0-9a-fA-F]{6}$/.test(normalized);
                    this.style.borderColor = valid ? '#ddd' : '#f87171';
                    if (valid) applyHex(normalized, this.dataset.varKey);
                });
                hexInput.addEventListener('paste', function() {
                    var self = this;
                    setTimeout(function() { self.dispatchEvent(new Event('input')); }, 10);
                });

                bottomLine.appendChild(hexInput);
                bottomLine.appendChild(swatch);
                bottomLine.appendChild(picker);
                row.appendChild(topLine);
                row.appendChild(bottomLine);
                container.appendChild(row);
            });
        });
    }

    // ─── Save / Reset ────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {

        document.getElementById('cpSaveBtn').addEventListener('click', function () {
            var saved = {};
            ALL_KEYS.forEach(function(k) { saved[k] = getVar(k); });
            localStorage.setItem('themeColors', JSON.stringify(saved));
            var msg = document.getElementById('cpSavedMsg');
            msg.style.display = 'inline';
            setTimeout(function(){ msg.style.display = 'none'; }, 2000);
        });

        document.getElementById('cpResetBtn').addEventListener('click', function () {
            localStorage.removeItem('themeColors');
            location.reload();
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && isOpen) toggleColorPicker();
        });
    });
})();
</script>
