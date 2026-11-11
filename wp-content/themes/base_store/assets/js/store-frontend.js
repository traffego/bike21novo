/**
 * Comportamentos interativos agnósticos do Design System da Loja.
 * Consome configurações globais injetadas via wp_localize_script (`window.StoreConfig`).
 */
(function () {
  'use strict';

  var cfg = window.StoreConfig || {};

  function ajaxPost(action, dataObj) {
    var body = new URLSearchParams();
    body.append('action', action);
    if (cfg.nonce) {
      body.append('nonce', cfg.nonce);
    }
    Object.keys(dataObj || {}).forEach(function (k) {
      if (dataObj[k] !== undefined && dataObj[k] !== null) {
        body.append(k, String(dataObj[k]));
      }
    });

    return fetch(cfg.ajax_url || '/wp-admin/admin-ajax.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: body.toString(),
    }).then(function (r) {
      return r.json();
    });
  }

  function formatBrl(value) {
    var num = Math.max(0, Number(value) || 0);
    return (
      'R$ ' +
      num.toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      })
    );
  }

  function onlyDigits(str) {
    return String(str || '').replace(/\D+/g, '');
  }

  function applyMask(value, type) {
    var d = onlyDigits(value);
    if (type === 'cep') {
      d = d.slice(0, 8);
      return d.length > 5 ? d.slice(0, 5) + '-' + d.slice(5) : d;
    }
    if (type === 'cpf') {
      d = d.slice(0, 11);
      if (d.length > 9) {
        return d.replace(/^(\d{3})(\d{3})(\d{3})(\d{1,2}).*/, '$1.$2.$3-$4');
      }
      if (d.length > 6) {
        return d.replace(/^(\d{3})(\d{3})(\d{1,3}).*/, '$1.$2.$3');
      }
      if (d.length > 3) {
        return d.replace(/^(\d{3})(\d{1,3}).*/, '$1.$2');
      }
      return d;
    }
    if (type === 'cnpj') {
      d = d.slice(0, 14);
      if (d.length > 12) {
        return d.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{1,2}).*/, '$1.$2.$3/$4-$5');
      }
      if (d.length > 8) {
        return d.replace(/^(\d{2})(\d{3})(\d{3})(\d{1,4}).*/, '$1.$2.$3/$4');
      }
      if (d.length > 5) {
        return d.replace(/^(\d{2})(\d{3})(\d{1,3}).*/, '$1.$2.$3');
      }
      if (d.length > 2) {
        return d.replace(/^(\d{2})(\d{1,3}).*/, '$1.$2');
      }
      return d;
    }
    if (type === 'phone') {
      d = d.slice(0, 11);
      if (d.length > 10) {
        return d.replace(/^(\d{2})(\d{5})(\d{4}).*/, '($1) $2-$3');
      }
      if (d.length > 6) {
        return d.replace(/^(\d{2})(\d{4})(\d{0,4}).*/, '($1) $2-$3');
      }
      if (d.length > 2) {
        return d.replace(/^(\d{2})(\d{0,5}).*/, '($1) $2');
      }
      return d;
    }
    return value;
  }

  /* ========================================================================
     1. BADGES DINÂMICOS DO CABEÇALHO (FAVORITOS & CARRINHO)
     ======================================================================== */
  function ensureHeaderBadges() {
    var favLink = document.querySelector('.elementor-element-wfv9a2a1 a');
    if (favLink) {
      var favBadge = favLink.querySelector('[data-store-wishlist-badge]');
      if (!favBadge) {
        favBadge = document.createElement('span');
        favBadge.className = 'ds-header-badge ds-header-badge--floating';
        favBadge.setAttribute('data-store-wishlist-badge', '');
        favLink.appendChild(favBadge);
      }
      setWishlistBadgeCount(parseInt(cfg.wishlist_count || 0, 10) || 0);
    }

    var cartLink = document.querySelector('.elementor-element-wct0e0fb a');
    if (cartLink) {
      var cartWrapper = cartLink.querySelector('.elementor-button-content-wrapper') || cartLink;
      var cartBadge = cartLink.querySelector('[data-store-cart-badge]');
      if (!cartBadge) {
        cartBadge = document.createElement('span');
        cartBadge.className = 'ds-header-badge';
        cartBadge.setAttribute('data-store-cart-badge', '');
        cartWrapper.appendChild(cartBadge);
      }
      setCartBadgeCount(parseInt(cfg.cart_count || 0, 10) || 0);
    }
  }

  function setWishlistBadgeCount(count) {
    var n = Math.max(0, parseInt(count, 10) || 0);
    document.querySelectorAll('[data-store-wishlist-badge]').forEach(function (el) {
      el.textContent = String(n);
      el.hidden = n <= 0;
    });
    document.querySelectorAll('[data-wishlist-total]').forEach(function (el) {
      el.textContent = String(n);
    });
  }

  function setCartBadgeCount(count) {
    var n = Math.max(0, parseInt(count, 10) || 0);
    document.querySelectorAll('[data-store-cart-badge]').forEach(function (el) {
      el.textContent = String(n);
      el.hidden = n <= 0;
    });
  }

  function syncCartBadgeFromFragments(fragments) {
    if (!fragments || typeof fragments !== 'object') {
      return;
    }
    var fragHtml = fragments['[data-store-cart-badge]'];
    if (typeof fragHtml === 'string') {
      var tmp = document.createElement('div');
      tmp.innerHTML = fragHtml;
      var node = tmp.querySelector('[data-store-cart-badge]');
      if (node) {
        setCartBadgeCount(parseInt(node.textContent || '0', 10) || 0);
      }
    }
  }

  /* ========================================================================
     2. TABS DE VITRINE ([store_products]) & WISHLIST AJAX
     ======================================================================== */
  function initShowcaseTabs() {
    document.addEventListener('click', function (e) {
      var tab = e.target.closest('.ds-showcase-tab');
      if (!tab) return;

      var showcase = tab.closest('.ds-products-showcase');
      var grid = showcase ? showcase.querySelector('.ds-product-grid') : null;
      if (!showcase || !grid) return;

      showcase.querySelectorAll('.ds-showcase-tab').forEach(function (btn) {
        btn.classList.remove('is-active');
        btn.setAttribute('aria-selected', 'false');
      });
      tab.classList.add('is-active');
      tab.setAttribute('aria-selected', 'true');
      grid.classList.add('is-loading');

      ajaxPost('store_filter_products', {
        category: tab.getAttribute('data-category') || 'all',
        limit: showcase.getAttribute('data-limit') || 8,
        featured: showcase.getAttribute('data-featured') || 'false',
        orderby: showcase.getAttribute('data-orderby') || 'date',
        order: showcase.getAttribute('data-order') || 'DESC',
        cta_mode: showcase.getAttribute('data-cta-mode') || 'primary',
        empty_text: showcase.getAttribute('data-empty-text') || '',
      })
        .then(function (res) {
          if (res && res.success && res.data && typeof res.data.html === 'string') {
            grid.innerHTML = res.data.html;
          }
        })
        .finally(function () {
          grid.classList.remove('is-loading');
        });
    });
  }

  function initWishlist() {
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('.ds-wishlist-btn');
      if (!btn) return;
      e.preventDefault();

      var productId = btn.getAttribute('data-product-id');
      if (!productId || btn.dataset.busy === '1') return;

      btn.dataset.busy = '1';
      ajaxPost('store_toggle_wishlist', { product_id: productId })
        .then(function (res) {
          if (!res || !res.success || !res.data) return;
          var active = !!res.data.active;
          document
            .querySelectorAll('.ds-wishlist-btn[data-product-id="' + productId + '"]')
            .forEach(function (target) {
              target.classList.toggle('is-active', active);
              target.setAttribute('aria-pressed', active ? 'true' : 'false');
              var label = active ? 'Remover dos favoritos' : 'Salvar nos favoritos';
              target.setAttribute('aria-label', label);
              target.setAttribute('title', label);
            });

          setWishlistBadgeCount(res.data.count || 0);

          if (!active && btn.getAttribute('data-remove-card') === '1') {
            var card = btn.closest('.ds-product-card');
            var wlWrap = btn.closest('.ds-wishlist-wrap');
            if (card) card.remove();
            if (wlWrap) {
              var remaining = wlWrap.querySelectorAll('.ds-wishlist-grid .ds-product-card').length;
              if (remaining === 0) {
                var wlGrid = wlWrap.querySelector('.ds-wishlist-grid');
                var wlEmpty = wlWrap.querySelector('.ds-wishlist-empty');
                if (wlGrid) wlGrid.hidden = true;
                if (wlEmpty) wlEmpty.hidden = false;
              }
            }
          }
        })
        .finally(function () {
          btn.dataset.busy = '0';
        });
    });
  }

  /* ========================================================================
     3. GUIA DE MEDIDAS INTERATIVO & MODAL COMPARTILHADO
     ======================================================================== */
  function updateSizeGuide(container, rawVal) {
    var rawJson = container.getAttribute('data-size-config');
    if (!rawJson) return;

    var sgCfg;
    try {
      sgCfg = JSON.parse(rawJson);
    } catch (err) {
      return;
    }

    var val = parseFloat(rawVal);
    if (isNaN(val)) return;

    var divider = parseFloat(sgCfg.scale_divider) || 1;
    var unit = sgCfg.unit || '';
    var scaled = divider > 1 ? (val / divider).toFixed(2).replace('.', ',') : String(val);
    var dispEl = container.querySelector('[data-sg-display]');
    if (dispEl) {
      dispEl.textContent = (scaled + (unit ? ' ' + unit : '')).trim();
    }

    var rows = Array.isArray(sgCfg.rows) ? sgCfg.rows : [];
    var matchIdx = -1;
    rows.forEach(function (row, idx) {
      if (matchIdx === -1 && val >= parseFloat(row.min) && val <= parseFloat(row.max)) {
        matchIdx = idx;
      }
    });
    if (matchIdx === -1 && rows.length > 0) {
      matchIdx = val < parseFloat(rows[0].min) ? 0 : rows.length - 1;
    }

    var matched = rows[matchIdx];
    if (matched) {
      var titleEl = container.querySelector('[data-sg-rec-title]');
      var noteEl = container.querySelector('[data-sg-rec-note]');
      var prefix = sgCfg.recommendation_prefix || 'Recomendação:';
      if (titleEl) {
        titleEl.textContent = (prefix + ' ' + (matched.label || '')).trim();
      }
      if (noteEl && matched.note !== undefined) {
        noteEl.textContent = matched.note || '';
      }
    }

    container.querySelectorAll('[data-sg-row-idx]').forEach(function (tr) {
      var idx = parseInt(tr.getAttribute('data-sg-row-idx'), 10);
      tr.classList.toggle('is-matched', idx === matchIdx);
    });
  }

  function initSizeGuide() {
    document.addEventListener('input', function (e) {
      var input = e.target.closest('[data-sg-input]');
      if (!input) return;
      var container = input.closest('.ds-size-guide');
      if (container) updateSizeGuide(container, input.value);
    });

    document.addEventListener('click', function (e) {
      var openBtn = e.target.closest('[data-open-size-guide]');
      if (openBtn) {
        e.preventDefault();
        var modal = document.getElementById('storeSizeGuideModal');
        if (modal) {
          modal.hidden = false;
          modal.setAttribute('aria-hidden', 'false');
          document.body.style.overflow = 'hidden';
        }
        return;
      }

      var closeBtn = e.target.closest('[data-store-close-modal]');
      if (closeBtn) {
        var activeModal = closeBtn.closest('.ds-modal');
        if (activeModal) {
          activeModal.hidden = true;
          activeModal.setAttribute('aria-hidden', 'true');
          document.body.style.overflow = '';
        }
      }
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        document.querySelectorAll('.ds-modal:not([hidden])').forEach(function (modal) {
          modal.hidden = true;
          modal.setAttribute('aria-hidden', 'true');
          document.body.style.overflow = '';
        });
      }
    });
  }

  /* ========================================================================
     4. MÁSCARAS DE ENTRADA, VIACEP & SIMULADOR DE FRETE
     ======================================================================== */
  function initMasks() {
    document.addEventListener('input', function (e) {
      var el = e.target;
      if (!el || el.tagName !== 'INPUT') return;

      var mask = el.getAttribute('data-mask');
      if (!mask && (el.id === 'billing_postcode' || el.id === 'shipping_postcode' || el.classList.contains('ds-cep-input'))) {
        mask = 'cep';
      }
      if (!mask) return;

      var formatted = applyMask(el.value, mask);
      if (el.value !== formatted) {
        el.value = formatted;
      }

      if (mask === 'cep' && el.hasAttribute('data-viacep') && onlyDigits(formatted).length === 8) {
        var prefix = el.getAttribute('data-viacep');
        if (el.dataset.lastCep !== onlyDigits(formatted)) {
          performViaCep(prefix, formatted);
        }
      }
    });
  }

  function performViaCep(prefix, cepVal) {
    var digits = onlyDigits(cepVal);
    var statusEl = document.querySelector('[data-viacep-status="' + prefix + '"]');
    var cepInput = document.getElementById(prefix + '_postcode');
    if (digits.length !== 8) {
      if (statusEl) statusEl.textContent = 'Informe um CEP válido com 8 dígitos.';
      return;
    }

    if (cepInput) cepInput.dataset.lastCep = digits;
    if (statusEl) statusEl.textContent = 'Consultando endereço...';

    ajaxPost('store_viacep_lookup', { cep: digits }).then(function (res) {
      if (!res || !res.success || !res.data) {
        if (statusEl) statusEl.textContent = 'CEP não localizado automaticamente; preencha abaixo.';
        return;
      }

      var info = res.data;
      var addr1 = document.getElementById(prefix + '_address_1');
      var neigh = document.getElementById(prefix + '_neighborhood');
      var city = document.getElementById(prefix + '_city');
      var state = document.getElementById(prefix + '_state');
      var num = document.getElementById(prefix + '_number');

      if (addr1 && info.logradouro) addr1.value = info.logradouro;
      if (neigh && info.bairro) neigh.value = info.bairro;
      if (city && info.cidade) city.value = info.cidade;
      if (state && info.uf) {
        state.value = info.uf;
        if (window.jQuery) {
          window.jQuery(state).trigger('change');
        }
      }

      if (statusEl) {
        statusEl.textContent = 'Localizado: ' + [info.logradouro, info.bairro, info.cidade + '/' + info.uf].filter(Boolean).join(' — ');
      }

      if (window.jQuery && document.querySelector('form.checkout')) {
        window.jQuery(document.body).trigger('update_checkout');
      }

      if (num && !num.value) {
        num.focus();
      }
    });
  }

  function initViaCepButtons() {
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-viacep-trigger]');
      if (!btn) return;
      e.preventDefault();
      var prefix = btn.getAttribute('data-viacep-trigger') || 'billing';
      var cepInput = document.getElementById(prefix + '_postcode');
      if (cepInput) {
        performViaCep(prefix, cepInput.value);
      }
    });
  }

  function initShippingCalculator() {
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-shipping-submit]');
      if (!btn) return;
      e.preventDefault();

      var box = btn.closest('[data-store-shipping-calc]');
      if (!box) return;
      var input = box.querySelector('[data-shipping-cep]');
      var results = box.querySelector('[data-shipping-results]');
      if (!input || !results) return;

      var digits = onlyDigits(input.value);
      if (digits.length !== 8) {
        results.hidden = false;
        results.innerHTML = '<div class="ds-alert ds-alert--error">Informe um CEP válido com 8 dígitos.</div>';
        return;
      }

      var productId = box.getAttribute('data-product-id') || '0';
      var qtyInput = document.querySelector('form.cart input.qty');
      var quantity = qtyInput ? parseInt(qtyInput.value || '1', 10) || 1 : 1;

      btn.disabled = true;
      results.hidden = false;
      results.innerHTML = '<div class="ds-shipping-rate-card"><span class="ds-shipping-rate-card__name">Calculando opções de entrega...</span></div>';

      ajaxPost('store_calculate_shipping', {
        cep: digits,
        product_id: productId,
        quantity: quantity,
      })
        .then(function (res) {
          if (!res || !res.success || !res.data || !Array.isArray(res.data.rates)) {
            var errMsg = (res && res.data && res.data.message) ? res.data.message : 'Não foi possível calcular o frete.';
            results.innerHTML = '<div class="ds-alert ds-alert--error">' + errMsg + '</div>';
            return;
          }

          var addr = res.data.address || {};
          var destStr = addr.cidade ? (addr.cidade + ' - ' + addr.uf + ' (CEP ' + res.data.cep + ')') : ('CEP ' + res.data.cep);
          var html = '<div class="ds-shipping-dest-note">Entrega para: <strong>' + destStr + '</strong></div>';

          res.data.rates.forEach(function (rate) {
            html +=
              '<div class="ds-shipping-rate-card">' +
              '<span class="ds-shipping-rate-card__name">' + String(rate.label) + '</span>' +
              '<span class="ds-shipping-rate-card__price' + (rate.is_free ? ' is-free' : '') + '">' + String(rate.cost_label) + '</span>' +
              '</div>';
          });
          results.innerHTML = html;

          if (productId === '0' && window.jQuery && document.querySelector('form.woocommerce-cart-form')) {
            window.jQuery(document.body).trigger('wc_update_cart');
          }
        })
        .finally(function () {
          btn.disabled = false;
        });
    });
  }

  /* ========================================================================
     5. STEPPER DE QUANTIDADE (PDP E CARRINHO)
     ======================================================================== */
  var cartUpdateTimer = null;

  function initQuantitySteppers() {
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('.ds-qty-stepper__btn');
      if (!btn) return;
      e.preventDefault();

      var stepper = btn.closest('.ds-qty-stepper');
      var input = stepper ? stepper.querySelector('input.qty') : null;
      if (!input) return;

      var step = parseFloat(input.getAttribute('step')) || 1;
      var min = input.getAttribute('min') !== null && input.getAttribute('min') !== '' ? parseFloat(input.getAttribute('min')) : 1;
      var max = input.getAttribute('max') !== null && input.getAttribute('max') !== '' ? parseFloat(input.getAttribute('max')) : Infinity;
      var current = parseFloat(input.value) || min;
      var delta = btn.getAttribute('data-qty-step') === 'down' ? -step : step;
      var next = Math.max(min, Math.min(max, current + delta));

      if (next !== current) {
        input.value = String(next);
        input.dispatchEvent(new Event('change', { bubbles: true }));
      }
    });

    document.addEventListener('change', function (e) {
      var qtyInput = e.target;
      if (!qtyInput || !qtyInput.classList.contains('qty')) return;
      var cartForm = qtyInput.closest('form.woocommerce-cart-form');
      if (!cartForm) return;

      var updateBtn = cartForm.querySelector('button[name="update_cart"]');
      if (!updateBtn) return;

      updateBtn.disabled = false;
      clearTimeout(cartUpdateTimer);
      cartUpdateTimer = setTimeout(function () {
        updateBtn.click();
      }, 320);
    });
  }

  /* ========================================================================
     6. CHECKOUT BR: ALTERNÂNCIA PF/PJ, ISENTO IE E DESCONTO PIX EM TEMPO REAL
     ======================================================================== */
  function syncPersonType() {
    var form = document.querySelector('form.checkout');
    if (!form) return;

    var checked = form.querySelector('input[name="billing_person_type"]:checked');
    var personType = checked ? checked.value : 'pf';

    form.querySelectorAll('.ds-person-type-row label.radio').forEach(function (lbl) {
      var forId = lbl.getAttribute('for');
      var radio = forId ? document.getElementById(forId) : null;
      lbl.classList.toggle('is-selected', !!(radio && radio.checked));
    });

    var toggleGroup = function (selector, show) {
      form.querySelectorAll(selector).forEach(function (row) {
        row.classList.toggle('ds-is-hidden-field', !show);
        row.querySelectorAll('input, select, textarea').forEach(function (inp) {
          if (!show) {
            if (inp.hasAttribute('required')) {
              inp.dataset.dsWasRequired = '1';
              inp.removeAttribute('required');
            }
          } else if (inp.dataset.dsWasRequired === '1' || row.classList.contains('validate-required')) {
            inp.setAttribute('required', 'required');
          }
        });
      });
    };

    toggleGroup('.ds-pf-field', personType === 'pf');
    toggleGroup('.ds-pj-field', personType === 'pj');
  }

  function syncIeExempt() {
    var chk = document.getElementById('billing_ie_exempt');
    var ie = document.getElementById('billing_ie');
    if (!chk || !ie) return;

    if (chk.checked) {
      ie.value = 'ISENTO';
      ie.readOnly = true;
      ie.classList.add('is-readonly');
    } else {
      if (ie.value === 'ISENTO') {
        ie.value = '';
      }
      ie.readOnly = false;
      ie.classList.remove('is-readonly');
    }
  }

  var lastPaymentMethod = '';

  function initCheckoutBr() {
    if (!document.querySelector('form.checkout')) return;

    syncPersonType();
    syncIeExempt();

    var initialPm = document.querySelector('form.checkout input[name="payment_method"]:checked');
    if (initialPm) {
      lastPaymentMethod = initialPm.value;
    }

    document.addEventListener('click', function (e) {
      var lbl = e.target.closest('.ds-person-type-row label.radio');
      if (lbl) {
        var forId = lbl.getAttribute('for');
        var radio = forId ? document.getElementById(forId) : null;
        if (radio) {
          radio.checked = true;
          syncPersonType();
          if (window.jQuery) {
            window.jQuery(radio).trigger('change');
          }
        }
      }
    });

    document.addEventListener('change', function (e) {
      if (e.target && e.target.name === 'billing_person_type') {
        syncPersonType();
      } else if (e.target && e.target.id === 'billing_ie_exempt') {
        syncIeExempt();
      }
    });


    if (window.jQuery) {
      var $ = window.jQuery;
      $(document.body).on('payment_method_selected', function () {
        var currentPm = document.querySelector('form.checkout input[name="payment_method"]:checked');
        var val = currentPm ? currentPm.value : '';
        if (val && val !== lastPaymentMethod) {
          lastPaymentMethod = val;
          $(document.body).trigger('update_checkout');
        }
      });

      $(document.body).on('updated_checkout checkout_error', function () {
        syncPersonType();
        syncIeExempt();
        var cur = document.querySelector('form.checkout input[name="payment_method"]:checked');
        if (cur) lastPaymentMethod = cur.value;
      });
    }
  }

  /* ========================================================================
     6.1 MULTI-STEP CHECKOUT (FunnelKit / Shopify Pattern em 3 Etapas)
     ======================================================================== */
  function isValidCpfJs(val) {
    var digits = String(val || '').replace(/\D+/g, '');
    if (digits.length !== 11 || /^(\d)\1{10}$/.test(digits)) return false;
    var sum = 0, rem;
    for (var i = 1; i <= 9; i++) sum += parseInt(digits.substring(i - 1, i), 10) * (11 - i);
    rem = (sum * 10) % 11;
    if (rem === 10 || rem === 11) rem = 0;
    if (rem !== parseInt(digits.substring(9, 10), 10)) return false;
    sum = 0;
    for (var j = 1; j <= 10; j++) sum += parseInt(digits.substring(j - 1, j), 10) * (12 - j);
    rem = (sum * 10) % 11;
    if (rem === 10 || rem === 11) rem = 0;
    return rem === parseInt(digits.substring(10, 11), 10);
  }

  function isValidCnpjJs(val) {
    var digits = String(val || '').replace(/\D+/g, '');
    if (digits.length !== 14 || /^(\d)\1{13}$/.test(digits)) return false;
    var length = digits.length - 2;
    var numbers = digits.substring(0, length);
    var checkDigits = digits.substring(length);
    var sum = 0;
    var pos = length - 7;
    for (var i = length; i >= 1; i--) {
      sum += numbers.charAt(length - i) * pos--;
      if (pos < 2) pos = 9;
    }
    var result = sum % 11 < 2 ? 0 : 11 - (sum % 11);
    if (result !== parseInt(checkDigits.charAt(0), 10)) return false;
    length += 1;
    numbers = digits.substring(0, length);
    sum = 0;
    pos = length - 7;
    for (var j = length; j >= 1; j--) {
      sum += numbers.charAt(length - j) * pos--;
      if (pos < 2) pos = 9;
    }
    result = sum % 11 < 2 ? 0 : 11 - (sum % 11);
    return result === parseInt(checkDigits.charAt(1), 10);
  }

  function initMultiStepCheckout() {
    var form = document.querySelector('form.ds-multi-step-checkout');
    if (!form) return;

    var currentStep = 1;
    var maxUnlockedStep = 1;

    function setFieldError(fieldId, errorMsg) {
      var el = document.getElementById(fieldId);
      if (!el) return;
      el.classList.add('ds-input-error');
      var p = el.closest('.form-row');
      if (p) {
        p.classList.add('ds-row-has-error');
        var err = p.querySelector('.ds-field-error-msg');
        if (!err) {
          err = document.createElement('span');
          err.className = 'ds-field-error-msg';
          p.appendChild(err);
        }
        err.textContent = errorMsg;
      }
    }

    function clearFieldErrors() {
      form.querySelectorAll('.ds-input-error').forEach(function (el) {
        el.classList.remove('ds-input-error');
      });
      form.querySelectorAll('.ds-row-has-error').forEach(function (el) {
        el.classList.remove('ds-row-has-error');
      });
      form.querySelectorAll('.ds-field-error-msg').forEach(function (el) {
        el.remove();
      });
    }

    form.addEventListener('input', function (e) {
      if (e.target && e.target.classList.contains('ds-input-error')) {
        e.target.classList.remove('ds-input-error');
        var p = e.target.closest('.form-row');
        if (p) {
          p.classList.remove('ds-row-has-error');
          var err = p.querySelector('.ds-field-error-msg');
          if (err) err.remove();
        }
      }
    });

    function validateStep1() {
      clearFieldErrors();
      var firstInvalid = null;
      var markError = function (id, msg) {
        setFieldError(id, msg);
        if (!firstInvalid) {
          firstInvalid = document.getElementById(id);
        }
      };

      // E-mail
      var emailEl = document.getElementById('billing_email');
      var email = emailEl ? emailEl.value.trim() : '';
      if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        markError('billing_email', 'Informe um e-mail válido para confirmação.');
      }

      // WhatsApp / Celular
      var phoneEl = document.getElementById('billing_phone');
      var phone = phoneEl ? phoneEl.value.replace(/\D+/g, '') : '';
      if (phone.length < 10) {
        markError('billing_phone', 'Informe seu WhatsApp / celular com DDD.');
      }

      // PF / PJ
      var isPjEl = document.getElementById('billing_person_type_pj');
      var isPj = isPjEl ? isPjEl.checked : false;
      if (isPj) {
        var cnpjEl = document.getElementById('billing_cnpj');
        var cnpj = cnpjEl ? cnpjEl.value.trim() : '';
        if (!isValidCnpjJs(cnpj)) {
          markError('billing_cnpj', 'Informe um CNPJ válido.');
        }
        var compEl = document.getElementById('billing_company');
        var comp = compEl ? compEl.value.trim() : '';
        if (!comp) {
          markError('billing_company', 'Informe a Razão Social da empresa.');
        }
        var isExempt = document.getElementById('billing_ie_exempt')?.checked;
        var ieEl = document.getElementById('billing_ie');
        var ie = ieEl ? ieEl.value.trim() : '';
        if (!isExempt && !ie) {
          markError('billing_ie', 'Informe a Inscrição Estadual ou marque Isento.');
        }
        var respEl = document.getElementById('billing_responsible_name');
        var resp = respEl ? respEl.value.trim() : '';
        if (!resp) {
          markError('billing_responsible_name', 'Informe o nome do responsável pelo recebimento.');
        }
      } else {
        var fnameEl = document.getElementById('billing_first_name');
        var fname = fnameEl ? fnameEl.value.trim() : '';
        if (!fname) {
          markError('billing_first_name', 'Informe seu primeiro nome.');
        }
        var lnameEl = document.getElementById('billing_last_name');
        var lname = lnameEl ? lnameEl.value.trim() : '';
        if (!lname) {
          markError('billing_last_name', 'Informe seu sobrenome.');
        }
        var cpfEl = document.getElementById('billing_cpf');
        var cpf = cpfEl ? cpfEl.value.trim() : '';
        if (!isValidCpfJs(cpf)) {
          markError('billing_cpf', 'Informe um CPF válido para emissão da Nota Fiscal.');
        }
      }

      // Endereço
      var cepEl = document.getElementById('billing_postcode');
      var cep = cepEl ? cepEl.value.replace(/\D+/g, '') : '';
      if (cep.length !== 8) {
        markError('billing_postcode', 'Informe um CEP válido com 8 dígitos.');
      }
      var ruaEl = document.getElementById('billing_address_1');
      var rua = ruaEl ? ruaEl.value.trim() : '';
      if (!rua) {
        markError('billing_address_1', 'Informe o logradouro / rua.');
      }
      var numEl = document.getElementById('billing_number');
      var num = numEl ? numEl.value.trim() : '';
      if (!num) {
        markError('billing_number', 'Informe o número do endereço.');
      }
      var bairroEl = document.getElementById('billing_neighborhood');
      var bairro = bairroEl ? bairroEl.value.trim() : '';
      if (!bairro) {
        markError('billing_neighborhood', 'Informe o bairro.');
      }
      var cidadeEl = document.getElementById('billing_city');
      var cidade = cidadeEl ? cidadeEl.value.trim() : '';
      if (!cidade) {
        markError('billing_city', 'Informe a cidade.');
      }
      var estadoEl = document.getElementById('billing_state');
      var estado = estadoEl ? estadoEl.value.trim() : '';
      if (!estado) {
        markError('billing_state', 'Selecione o estado.');
      }

      if (firstInvalid) {
        firstInvalid.focus();
        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return false;
      }

      return true;
    }

    function validateStep2() {
      var checked = document.querySelector('#order_review input[name^="shipping_method"]:checked') ||
                    document.querySelector('#ds-step2-shipping-mount input:checked');
      if (!checked) {
        alert('Por favor, selecione uma opção de frete para continuar.');
        return false;
      }
      return true;
    }

    function updateRecapBoxes() {
      var email = (document.getElementById('billing_email')?.value || '').trim();
      var phone = (document.getElementById('billing_phone')?.value || '').trim();
      var contactStr = email + (phone ? ' • ' + phone : '');

      var rua = (document.getElementById('billing_address_1')?.value || '').trim();
      var num = (document.getElementById('billing_number')?.value || '').trim();
      var comp = (document.getElementById('billing_address_2')?.value || '').trim();
      var bairro = (document.getElementById('billing_neighborhood')?.value || '').trim();
      var cidade = (document.getElementById('billing_city')?.value || '').trim();
      var estado = (document.getElementById('billing_state')?.value || '').trim();
      var cep = (document.getElementById('billing_postcode')?.value || '').trim();

      var addressParts = [];
      if (rua) addressParts.push(rua + (num ? ', ' + num : ''));
      if (comp) addressParts.push('(' + comp + ')');
      if (bairro) addressParts.push(bairro);
      if (cidade) addressParts.push(cidade + (estado ? '/' + estado : ''));
      if (cep) addressParts.push('CEP ' + cep);
      var addressStr = addressParts.join(' • ');

      // Passo 2 Recap
      var rContact2 = document.getElementById('ds-recap-contact-val');
      var rAddr2 = document.getElementById('ds-recap-address-val');
      if (rContact2) rContact2.textContent = contactStr || '—';
      if (rAddr2) rAddr2.textContent = addressStr || '—';

      // Passo 3 Recap
      var rContact3 = document.getElementById('ds-recap-contact-step3-val');
      var rAddr3 = document.getElementById('ds-recap-address-step3-val');
      var rShip3 = document.getElementById('ds-recap-shipping-step3-val');
      if (rContact3) rContact3.textContent = contactStr || '—';
      if (rAddr3) rAddr3.textContent = addressStr || '—';

      var checkedShipping = document.querySelector('#order_review input[name^="shipping_method"]:checked');
      if (checkedShipping) {
        var lbl = checkedShipping.closest('li') || checkedShipping.parentElement;
        var text = lbl ? lbl.innerText.replace(/\s+/g, ' ').trim() : checkedShipping.value;
        if (rShip3) rShip3.textContent = text;
      }
    }

    function syncStep2ShippingMethods() {
      var mount = document.getElementById('ds-step2-shipping-mount');
      if (!mount) return;

      var shippingItems = document.querySelectorAll('#order_review ul#shipping_method li');
      if (!shippingItems || shippingItems.length === 0) {
        mount.innerHTML = '<div class="ds-shipping-loading" style="padding:16px;text-align:center;color:var(--ds-slate-500);font-size:13px;">Carregando opções de envio para o seu CEP...</div>';
        return;
      }

      var html = '';
      shippingItems.forEach(function (li) {
        var radio = li.querySelector('input[type="radio"]');
        if (!radio) return;
        var isChecked = radio.checked;
        if (isChecked) {
          li.classList.add('is-selected');
        } else {
          li.classList.remove('is-selected');
        }
        var label = li.querySelector('label');
        var fullText = label ? label.textContent.trim() : radio.value;

        var parts = fullText.split(/:\s*(.*)/s);
        var title = parts[0] ? parts[0].trim() : fullText;
        var price = parts[1] ? parts[1].trim() : 'Grátis';

        html += '<div class="ds-shipping-card-option ' + (isChecked ? 'is-selected' : '') + '" data-shipping-id="' + radio.id + '" data-shipping-val="' + radio.value + '">';
        html += '  <div class="ds-shipping-card-option__left">';
        html += '    <input type="radio" name="ds_step2_shipping" class="ds-shipping-card-option__radio" ' + (isChecked ? 'checked' : '') + ' />';
        html += '    <div class="ds-shipping-card-option__info">';
        html += '      <div class="ds-shipping-card-option__title">' + title + '</div>';
        html += '    </div>';
        html += '  </div>';
        html += '  <div class="ds-shipping-card-option__price">' + price + '</div>';
        html += '</div>';
      });

      mount.innerHTML = html;
    }

    function syncSidebarShippingSelected() {
      var shippingItems = document.querySelectorAll('#order_review ul#shipping_method li');
      shippingItems.forEach(function (li) {
        var radio = li.querySelector('input[type="radio"]');
        if (radio && radio.checked) {
          li.classList.add('is-selected');
        } else {
          li.classList.remove('is-selected');
        }
      });
    }

    function updateFinalButton() {
      var btn = document.getElementById('place_order');
      if (!btn) return;
      btn.classList.add('ds-btn--place-order');
      var totalEl = document.querySelector('#order_review .order-total .amount');
      var totalText = totalEl ? totalEl.textContent.trim() : '';
      var lockIcon = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="margin-right:8px;vertical-align:-2px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>';
      btn.innerHTML = lockIcon + 'Finalizar Pedido' + (totalText ? ' • ' + totalText : '');
    }

    function goToStep(targetStep) {
      if (targetStep < 1 || targetStep > 3) return;

      currentStep = targetStep;
      if (targetStep > maxUnlockedStep) {
        maxUnlockedStep = targetStep;
      }

      // Atualiza Stepper
      var stepper = document.getElementById('ds-checkout-stepper');
      if (stepper) {
        var checkSvg = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
        for (var s = 1; s <= 3; s++) {
          var item = stepper.querySelector('[data-step-target="' + s + '"]');
          var circle = item ? item.querySelector('.ds-checkout-stepper__circle') : null;
          if (!item || !circle) continue;

          item.classList.remove('is-active', 'is-completed', 'is-pending', 'is-clickable');

          if (s === currentStep) {
            item.classList.add('is-active');
            circle.textContent = s;
          } else if (s < currentStep) {
            item.classList.add('is-completed', 'is-clickable');
            circle.innerHTML = checkSvg;
          } else {
            item.classList.add('is-pending');
            if (s <= maxUnlockedStep) item.classList.add('is-clickable');
            circle.textContent = s;
          }
        }

        var l1 = stepper.querySelector('[data-line="1"]');
        var l2 = stepper.querySelector('[data-line="2"]');
        if (l1) l1.classList.toggle('is-active', currentStep >= 2);
        if (l2) l2.classList.toggle('is-active', currentStep >= 3);
      }

      // Alterna Panes
      document.querySelectorAll('.ds-step-pane').forEach(function (pane) {
        var stepNum = parseInt(pane.getAttribute('data-step-pane'), 10);
        if (stepNum === currentStep) {
          pane.style.display = 'block';
          pane.classList.add('is-active');
        } else {
          pane.style.display = 'none';
          pane.classList.remove('is-active');
        }
      });

      updateRecapBoxes();
      if (currentStep === 2) {
        syncStep2ShippingMethods();
        if (window.jQuery) {
          window.jQuery(document.body).trigger('update_checkout');
        }
      } else if (currentStep === 3) {
        updateFinalButton();
      }

      var scrollAnchor = document.getElementById('ds-checkout-stepper') || form;
      if (scrollAnchor) {
        var y = scrollAnchor.getBoundingClientRect().top + window.pageYOffset - 90;
        window.scrollTo({ top: Math.max(0, y), behavior: 'smooth' });
      }
    }

    // Eventos de clique
    document.addEventListener('click', function (e) {
      var btnNext2 = e.target.closest('[data-next-step="2"]');
      if (btnNext2) {
        e.preventDefault();
        if (validateStep1()) {
          goToStep(2);
        }
        return;
      }

      var btnNext3 = e.target.closest('[data-next-step="3"]');
      if (btnNext3) {
        e.preventDefault();
        if (validateStep2()) {
          goToStep(3);
        }
        return;
      }

      var btnGoto = e.target.closest('[data-goto-step]');
      if (btnGoto) {
        e.preventDefault();
        var target = parseInt(btnGoto.getAttribute('data-goto-step'), 10);
        goToStep(target);
        return;
      }

      var stepItem = e.target.closest('.ds-checkout-stepper__item.is-clickable');
      if (stepItem) {
        var targetStep = parseInt(stepItem.getAttribute('data-step-target'), 10);
        if (targetStep < currentStep) {
          goToStep(targetStep);
        } else if (targetStep === 2 && validateStep1()) {
          goToStep(2);
        } else if (targetStep === 3 && validateStep1() && validateStep2()) {
          goToStep(3);
        }
        return;
      }

      var shippingCard = e.target.closest('.ds-shipping-card-option');
      if (shippingCard) {
        var rId = shippingCard.getAttribute('data-shipping-id');
        var underlyingRadio = document.getElementById(rId);
        if (underlyingRadio) {
          underlyingRadio.checked = true;
          if (window.jQuery) {
            window.jQuery(underlyingRadio).trigger('change');
          }
        }
        document.querySelectorAll('.ds-shipping-card-option').forEach(function (c) {
          c.classList.remove('is-selected');
          var r = c.querySelector('input[type="radio"]');
          if (r) r.checked = false;
        });
        shippingCard.classList.add('is-selected');
        var cardRadio = shippingCard.querySelector('input[type="radio"]');
        if (cardRadio) cardRadio.checked = true;
        syncSidebarShippingSelected();
        updateRecapBoxes();
      }
    });

    if (window.jQuery) {
      window.jQuery(document.body).on('updated_checkout', function () {
        if (currentStep === 2) {
          syncStep2ShippingMethods();
        }
        syncSidebarShippingSelected();
        updateRecapBoxes();
        updateFinalButton();
      });
    }

    syncSidebarShippingSelected();
    updateFinalButton();
    updateRecapBoxes();
  }

  /* ========================================================================
     7. PÁGINA DE PRODUTO (GALERIA, VARIAÇÕES EM PÍLULAS, ABAS & COMPRAR AGORA)
     ======================================================================== */
  function renderLivePdpPriceBox(priceCard, activePrice, regularPrice) {
    if (!priceCard) return;

    var base = Number(activePrice) || 0;
    var reg = Number(regularPrice) || base;
    var discountPct = reg > base && reg > 0 ? Math.round(((reg - base) / reg) * 100) : 0;
    var cashPct = parseFloat(cfg.cash_discount_percent || 0) || 0;
    var cashLabel = cfg.cash_discount_label || 'no PIX à vista';
    var maxInst = Math.max(1, parseInt(cfg.max_installments || 1, 10) || 1);
    var minInstVal = parseFloat(cfg.min_installment_value || 0) || 0;
    var instLabel = cfg.installments_label || '';

    var cashPrice = cashPct > 0 ? Math.round(base * (1 - cashPct / 100) * 100) / 100 : base;
    var instCount = maxInst;
    if (base > 0 && minInstVal > 0) {
      instCount = Math.max(1, Math.min(maxInst, Math.floor(base / minInstVal)));
    }
    var instValue = instCount > 0 ? Math.round((base / instCount) * 100) / 100 : base;

    var html = '';
    if (discountPct > 0) {
      html +=
        '<div class="ds-pdp-price-old-row">' +
        '<span class="ds-price-old">' + formatBrl(reg) + '</span>' +
        '<span class="ds-price-off">-' + discountPct + '% OFF</span>' +
        '</div>';
    }

    html +=
      '<div class="ds-pdp-price-main-row">' +
      '<strong class="ds-pdp-price-big">' + formatBrl(cashPct > 0 ? cashPrice : base) + '</strong>' +
      (cashPct > 0 ? '<span class="ds-pdp-price-cash-tag">' + cashLabel + '</span>' : '') +
      '</div>';

    if (cashPct > 0) {
      html +=
        '<div class="ds-pdp-price-installment">' +
        '<span>ou ' + formatBrl(base) + (instCount > 1 ? ' em <strong>' + instCount + 'x de ' + formatBrl(instValue) + '</strong> ' + instLabel : '') + '</span>' +
        '</div>';
    } else if (instCount > 1) {
      html +=
        '<div class="ds-pdp-price-installment">' +
        '<span>em até <strong>' + instCount + 'x de ' + formatBrl(instValue) + '</strong> ' + instLabel + '</span>' +
        '</div>';
    }

    priceCard.innerHTML = html;
  }

  function syncVariationPillsFromSelects(form) {
    form.querySelectorAll('table.variations select').forEach(function (select) {
      var attrName = select.getAttribute('name');
      var curVal = select.value;
      var enabledValues = {};
      Array.prototype.forEach.call(select.options, function (opt) {
        if (opt.value && !opt.disabled) {
          enabledValues[opt.value] = opt.text;
        }
      });

      var group = form.querySelector('.ds-variation-attr-group[data-attr-name="' + attrName + '"]');
      if (!group) return;

      var selText = '';
      group.querySelectorAll('.ds-var-pill').forEach(function (pill) {
        var pVal = pill.getAttribute('data-value') || '';
        var isMatch = curVal !== '' && curVal === pVal;
        var isAvailable = Object.prototype.hasOwnProperty.call(enabledValues, pVal);
        pill.classList.toggle('is-selected', isMatch);
        pill.disabled = !isAvailable && !isMatch;
        if (isMatch) {
          selText = pill.textContent.trim();
        }
      });

      var labelOut = group.querySelector('[data-attr-selected]');
      if (labelOut) {
        labelOut.textContent = selText ? ': ' + selText : '';
      }
    });
  }

  function initPdpInteractions() {
    var mainImg = document.getElementById('storePdpMainImg');
    if (mainImg && !mainImg.dataset.defaultSrc) {
      mainImg.dataset.defaultSrc = mainImg.getAttribute('src') || '';
      mainImg.dataset.defaultAlt = mainImg.getAttribute('alt') || '';
    }

    var priceCard = document.querySelector('.ds-pdp-price-card');
    if (priceCard && !priceCard.dataset.defaultHtml) {
      priceCard.dataset.defaultHtml = priceCard.innerHTML;
    }

    var skuEl = document.querySelector('.ds-pdp-sku [data-sku-value]');
    if (skuEl && !skuEl.dataset.defaultSku) {
      skuEl.dataset.defaultSku = skuEl.textContent.trim();
    }

    var mainMedia = document.querySelector('.ds-pdp-main-media');
    if (mainMedia && mainImg) {
      mainMedia.addEventListener('mousemove', function (e) {
        var rect = mainMedia.getBoundingClientRect();
        if (rect.width <= 0 || rect.height <= 0) return;
        var x = ((e.clientX - rect.left) / rect.width) * 100;
        var y = ((e.clientY - rect.top) / rect.height) * 100;
        x = Math.max(0, Math.min(100, x));
        y = Math.max(0, Math.min(100, y));
        mainImg.style.transformOrigin = x + '% ' + y + '%';
        mainImg.style.transform = 'scale(2.2)';
      });

      mainMedia.addEventListener('mouseleave', function () {
        mainImg.style.transformOrigin = 'center center';
        mainImg.style.transform = 'scale(1)';
      });
    }

    document.addEventListener('click', function (e) {
      var thumb = e.target.closest('.ds-pdp-thumb');
      if (thumb && mainImg) {
        e.preventDefault();
        var src = thumb.getAttribute('data-full-src');
        var srcset = thumb.getAttribute('data-full-srcset') || '';
        if (src) {
          mainImg.setAttribute('src', src);
          if (srcset) {
            mainImg.setAttribute('srcset', srcset);
          } else {
            mainImg.removeAttribute('srcset');
          }
          document.querySelectorAll('.ds-pdp-thumb').forEach(function (t) {
            t.classList.toggle('is-active', t === thumb);
          });
        }
        return;
      }

      var pill = e.target.closest('.ds-var-pill');
      if (pill && !pill.disabled) {
        e.preventDefault();
        var form = pill.closest('form.variations_form');
        var attrName = pill.getAttribute('data-attr');
        var attrVal = pill.getAttribute('data-value');
        var select = form ? form.querySelector('select[name="' + attrName + '"]') : null;
        if (select) {
          select.value = select.value === attrVal ? '' : attrVal;
          if (window.jQuery) {
            window.jQuery(select).trigger('change');
          } else {
            select.dispatchEvent(new Event('change', { bubbles: true }));
          }
          syncVariationPillsFromSelects(form);
        }
        return;
      }

      var resetBtn = e.target.closest('.ds-var-reset');
      if (resetBtn) {
        e.preventDefault();
        var varForm = resetBtn.closest('form.variations_form');
        var wcReset = varForm ? varForm.querySelector('.reset_variations') : null;
        if (wcReset) wcReset.click();
        return;
      }

      var buyNowBtn = e.target.closest('[data-buy-now]');
      if (buyNowBtn) {
        var cartForm = buyNowBtn.closest('form.cart');
        if (!cartForm) return;

        var wcBtn = cartForm.querySelector('.single_add_to_cart_button');
        if (wcBtn && wcBtn.classList.contains('disabled')) {
          e.preventDefault();
          wcBtn.click();
          return;
        }

        e.preventDefault();
        var flag = cartForm.querySelector('input[name="store_buy_now"]');
        if (!flag) {
          flag = document.createElement('input');
          flag.type = 'hidden';
          flag.name = 'store_buy_now';
          cartForm.appendChild(flag);
        }
        flag.value = '1';

        if (wcBtn && wcBtn.getAttribute('name') === 'add-to-cart' && wcBtn.value) {
          var hiddenAdd = cartForm.querySelector('input[name="add-to-cart"]');
          if (!hiddenAdd) {
            hiddenAdd = document.createElement('input');
            hiddenAdd.type = 'hidden';
            hiddenAdd.name = 'add-to-cart';
            hiddenAdd.value = wcBtn.value;
            cartForm.appendChild(hiddenAdd);
          }
        }
        cartForm.submit();
        return;
      }

      var addBtn = e.target.closest('.single_add_to_cart_button');
      if (addBtn) {
        var cForm = addBtn.closest('form.cart');
        var bnInput = cForm ? cForm.querySelector('input[name="store_buy_now"]') : null;
        if (bnInput) bnInput.remove();
        return;
      }

      var tabBtn = e.target.closest('.ds-tab-btn');
      if (tabBtn) {
        e.preventDefault();
        var nav = tabBtn.closest('.ds-pdp-tabs-card');
        var targetId = tabBtn.getAttribute('data-tab-target');
        if (!nav || !targetId) return;

        nav.querySelectorAll('.ds-tab-btn').forEach(function (b) {
          b.classList.toggle('is-active', b === tabBtn);
          b.setAttribute('aria-selected', b === tabBtn ? 'true' : 'false');
        });
        nav.querySelectorAll('.ds-tab-panel').forEach(function (panel) {
          panel.hidden = panel.id !== targetId;
        });
      }
    });

    if (window.jQuery) {
      var $ = window.jQuery;
      $('form.variations_form').each(function () {
        syncVariationPillsFromSelects(this);
      });

      $(document.body).on('woocommerce_update_variation_values check_variations', 'form.variations_form', function () {
        syncVariationPillsFromSelects(this);
      });

      $(document.body).on('found_variation', 'form.variations_form', function (e, variation) {
        syncVariationPillsFromSelects(this);
        if (!variation) return;

        if (mainImg && variation.image && variation.image.src) {
          mainImg.setAttribute('src', variation.image.src);
          mainImg.removeAttribute('srcset');
        }
        if (skuEl && variation.sku) {
          skuEl.textContent = variation.sku;
        }
        if (priceCard && variation.display_price !== undefined) {
          renderLivePdpPriceBox(priceCard, variation.display_price, variation.display_regular_price);
        }
      });

      $(document.body).on('reset_data', 'form.variations_form', function () {
        syncVariationPillsFromSelects(this);
        if (mainImg && mainImg.dataset.defaultSrc) {
          mainImg.setAttribute('src', mainImg.dataset.defaultSrc);
        }
        if (skuEl && skuEl.dataset.defaultSku) {
          skuEl.textContent = skuEl.dataset.defaultSku;
        }
        if (priceCard && priceCard.dataset.defaultHtml) {
          priceCard.innerHTML = priceCard.dataset.defaultHtml;
        }
      });
    }
  }

  /* ========================================================================
     8. DRAWER DE FILTROS MOBILE, PIX THANK YOU & FORMULÁRIO DE CONTATO
     ======================================================================== */
  function initArchiveFilters() {
    var openBtn = document.getElementById('storeFilterDrawerBtn');
    var closeBtn = document.getElementById('storeFilterDrawerClose');
    var sidebar = document.getElementById('storeCatalogSidebar');
    var backdrop = document.getElementById('storeFilterBackdrop');

    function setOpen(state) {
      if (!sidebar) return;
      sidebar.classList.toggle('is-open', state);
      if (backdrop) backdrop.hidden = !state;
      document.body.style.overflow = state ? 'hidden' : '';
    }

    if (openBtn) {
      openBtn.addEventListener('click', function () {
        setOpen(true);
      });
    }
    if (closeBtn) {
      closeBtn.addEventListener('click', function () {
        setOpen(false);
      });
    }
    if (backdrop) {
      backdrop.addEventListener('click', function () {
        setOpen(false);
      });
    }

    document.addEventListener('change', function (e) {
      var chk = e.target;
      if (chk && chk.type === 'checkbox') {
        var chip = chk.closest('.ds-filter-chip');
        if (chip) {
          chip.classList.toggle('is-checked', chk.checked);
        }
      }
    });
  }

  function initThankYouPix() {
    var copyBtn = document.getElementById('storePixCopyBtn');
    var codeInput = document.getElementById('storePixCode');
    if (copyBtn && codeInput) {
      copyBtn.addEventListener('click', function () {
        var text = codeInput.value;
        var orig = copyBtn.textContent;
        var finish = function () {
          copyBtn.textContent = 'Código Copiado!';
          setTimeout(function () {
            copyBtn.textContent = orig;
          }, 2600);
        };

        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(text).then(finish, function () {
            codeInput.select();
            document.execCommand('copy');
            finish();
          });
        } else {
          codeInput.select();
          document.execCommand('copy');
          finish();
        }
      });
    }

    var timerEl = document.getElementById('storePixTimer');
    if (timerEl) {
      var mins = parseInt(timerEl.getAttribute('data-minutes') || '15', 10) || 15;
      var secsLeft = mins * 60;
      var tick = function () {
        var m = String(Math.floor(secsLeft / 60)).padStart(2, '0');
        var s = String(secsLeft % 60).padStart(2, '0');
        timerEl.textContent = m + ':' + s;
        if (secsLeft > 0) {
          secsLeft -= 1;
        }
      };
      tick();
      setInterval(tick, 1000);
    }
  }

  /* ========================================================================
     TOPBAR MOBILE ULTRAFINO COM TICKER ROTATIVO (34px)
     ======================================================================== */
  function initMobileTopbarTicker() {
    var topbar = document.querySelector('.elementor-element-top5d5b6');
    if (!topbar) return;

    var desktopInner = topbar.querySelector('.elementor-element-tin78f87');
    if (!desktopInner) return;

    // Extrai dados reais já renderizados no desktop (zero hardcoded)
    var shippingWidget = desktopInner.querySelector('.elementor-element-wi1999c7');
    var shippingText = shippingWidget ? shippingWidget.innerText.trim() : 'Envio para todo o Brasil com seguro total';

    var paymentWidget = desktopInner.querySelector('.elementor-element-wi2dc245');
    var paymentText = paymentWidget ? paymentWidget.innerText.trim() : 'Até 12x sem juros ou 10% OFF no Pix';

    // Links de apoio
    var links = [];
    desktopInner.querySelectorAll('.elementor-element-trif77e1 a').forEach(function (a) {
      links.push({
        text: a.innerText.trim(),
        href: a.getAttribute('href') || '#'
      });
    });

    var existingTicker = document.getElementById('ds-mobile-topbar-ticker');
    if (existingTicker) existingTicker.remove();

    var ticker = document.createElement('div');
    ticker.id = 'ds-mobile-topbar-ticker';
    ticker.className = 'ds-mobile-topbar-ticker';

    var linksHtml = '';
    if (links.length > 0) {
      linksHtml = '<div class="ds-m-ticker-links">';
      links.forEach(function (l, idx) {
        var icon = idx === 0 ? '🔍' : (idx === 1 ? '🛡️' : '💬');
        var label = l.text.replace(' Pedido', '').replace(' de Fábrica', '');
        linksHtml += '<a href="' + l.href + '" class="ds-m-ticker-link">' + icon + ' ' + label + '</a>';
        if (idx < links.length - 1) {
          linksHtml += '<span class="ds-m-ticker-sep">•</span>';
        }
      });
      linksHtml += '</div>';
    }

    // Unidades de lojas do rotator
    var storeUnits = [];
    desktopInner.querySelectorAll('.ds-rotator-item .ds-rotator-text').forEach(function (el) {
      storeUnits.push(el.innerText.trim());
    });
    if (storeUnits.length === 0) {
      storeUnits.push('Lojas Físicas: Rio de Janeiro');
    }

    var html = '<div class="ds-m-ticker-track">';
    // Slide 0: Lojas
    html += '<div class="ds-m-ticker-slide is-active" data-m-slide="0">';
    html += '  <span class="ds-m-ticker-badge">📍 Lojas</span>';
    html += '  <span class="ds-m-ticker-text" id="ds-m-store-text">' + storeUnits[0] + '</span>';
    html += '</div>';

    // Slide 1: Frete
    html += '<div class="ds-m-ticker-slide" data-m-slide="1">';
    html += '  <span class="ds-m-ticker-icon">🚚</span>';
    html += '  <span class="ds-m-ticker-text">' + shippingText + '</span>';
    html += '</div>';

    // Slide 2: Pagamento / Pix
    html += '<div class="ds-m-ticker-slide" data-m-slide="2">';
    html += '  <span class="ds-m-ticker-icon">💳</span>';
    html += '  <span class="ds-m-ticker-text">' + paymentText + '</span>';
    html += '</div>';

    // Slide 3: Links de Rastreio, Garantia e Atendimento
    if (linksHtml) {
      html += '<div class="ds-m-ticker-slide" data-m-slide="3">';
      html += linksHtml;
      html += '</div>';
    }

    html += '</div>';
    ticker.innerHTML = html;
    topbar.appendChild(ticker);

    // Motor de rotação do ticker mobile
    var slides = ticker.querySelectorAll('.ds-m-ticker-slide');
    if (slides.length <= 1) return;

    var currentSlide = 0;
    var currentStoreIdx = 0;
    var storeTextEl = document.getElementById('ds-m-store-text');

    setInterval(function () {
      slides[currentSlide].classList.remove('is-active');
      currentSlide = (currentSlide + 1) % slides.length;
      slides[currentSlide].classList.add('is-active');

      // Se entrou no slide das lojas, avança para a próxima loja da lista
      if (currentSlide === 0 && storeUnits.length > 1 && storeTextEl) {
        currentStoreIdx = (currentStoreIdx + 1) % storeUnits.length;
        storeTextEl.textContent = storeUnits[currentStoreIdx];
      }
    }, 3600);
  }

  function initContactForm() {
    document.addEventListener('submit', function (e) {
      var form = e.target.closest('.ds-contact-form');
      if (!form) return;
      e.preventDefault();

      var feedback = form.querySelector('.ds-contact-feedback');
      var submitBtn = form.querySelector('button[type="submit"]');
      if (submitBtn) submitBtn.disabled = true;

      var fd = new FormData(form);
      var payload = {};
      fd.forEach(function (v, k) {
        payload[k] = v;
      });

      ajaxPost('store_submit_contact', payload)
        .then(function (res) {
          if (!feedback) return;
          if (res && res.success) {
            feedback.innerHTML = '<div class="ds-alert ds-alert--success">' + String(res.data.message || 'Mensagem enviada.') + '</div>';
            form.reset();
          } else {
            var err = (res && res.data && res.data.message) ? res.data.message : 'Erro ao enviar mensagem.';
            feedback.innerHTML = '<div class="ds-alert ds-alert--error">' + err + '</div>';
          }
        })
        .catch(function () {
          if (feedback) {
            feedback.innerHTML = '<div class="ds-alert ds-alert--error">Falha na comunicação. Tente novamente.</div>';
          }
        })
        .finally(function () {
          if (submitBtn) submitBtn.disabled = false;
        });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    ensureHeaderBadges();
    initShowcaseTabs();
    initWishlist();
    initSizeGuide();
    initMasks();
    initViaCepButtons();
    initShippingCalculator();
    initQuantitySteppers();
    initCheckoutBr();
    initMultiStepCheckout();
    initPdpInteractions();
    initArchiveFilters();
    initThankYouPix();
    initContactForm();
    initMobileTopbarTicker();

    if (window.jQuery) {
      window.jQuery(document.body).on('added_to_cart removed_from_cart wc_fragments_refreshed wc_fragments_loaded', function (e, fragments) {
        ensureHeaderBadges();
        syncCartBadgeFromFragments(fragments);
      });
      window.jQuery(document.body).on('wc_cart_emptied', function () {
        setCartBadgeCount(0);
      });
    }
  });
})();
