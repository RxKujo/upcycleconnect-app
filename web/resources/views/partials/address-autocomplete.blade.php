{{-- Autocomplétion d'adresse (géocodeur BAN geopf).
     window.initAddressAutocomplete(inputEl, { city, postcode }). Repli : input libre si API KO. --}}
{{-- === Styles === --}}
<style>
    .addr-ac-wrap { position: relative; }
    .addr-ac-suggestions {
        position: absolute; z-index: 60; left: 0; right: 0; top: 100%;
        background: #fff; border: 2px solid var(--coffee, #120309); border-top: none;
        max-height: 230px; overflow-y: auto; display: none;
        box-shadow: 3px 3px 0 rgba(18, 3, 9, 0.2);
    }
    .addr-ac-item { padding: 9px 12px; cursor: pointer; font-size: 0.9rem; line-height: 1.3; }
    .addr-ac-item:hover, .addr-ac-item.active { background: var(--wheat, #f0e0c0); }
</style>
{{-- === Script === --}}
<script>
(function () {
    if (window.initAddressAutocomplete) return;
    window.initAddressAutocomplete = function (input, opts) {
        opts = opts || {};
        if (!input || input.dataset.acInit) return;
        input.dataset.acInit = '1';

        var wrap = document.createElement('div');
        wrap.className = 'addr-ac-wrap';
        input.parentNode.insertBefore(wrap, input);
        wrap.appendChild(input);

        var box = document.createElement('div');
        box.className = 'addr-ac-suggestions';
        wrap.appendChild(box);

        var timer = null;
        function close() { box.style.display = 'none'; box.innerHTML = ''; }

        input.addEventListener('input', function () {
            var v = input.value.trim();
            clearTimeout(timer);
            if (v.length < 3) { close(); return; }
            timer = setTimeout(async function () {
                try {
                    var r = await fetch('https://data.geopf.fr/geocodage/search/?q=' + encodeURIComponent(v) + '&limit=5');
                    if (!r.ok) throw new Error();
                    var d = await r.json();
                    var fs = d.features || [];
                    if (!fs.length) { close(); return; }
                    box.innerHTML = '';
                    fs.forEach(function (f) {
                        var p = f.properties;
                        var it = document.createElement('div');
                        it.className = 'addr-ac-item';
                        it.textContent = p.label;
                        it.addEventListener('mousedown', function (e) {
                            e.preventDefault();
                            input.value = p.name || p.label;
                            if (opts.city) opts.city.value = p.city || '';
                            if (opts.postcode) opts.postcode.value = p.postcode || '';
                            close();
                            input.dispatchEvent(new Event('change', { bubbles: true }));
                        });
                        box.appendChild(it);
                    });
                    box.style.display = 'block';
                } catch (_e) {
                    close(); // repli : saisie manuelle
                }
            }, 300);
        });

        input.addEventListener('blur', function () { setTimeout(close, 150); });
    };
})();
</script>
