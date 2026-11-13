/**
 * 多语言字段自动翻译插件前端脚本
 * 覆盖所有需要填写中英双语的界面：Blade 多语言输入组、Element UI 语言弹窗/表单、
 * 富文本编辑器与设计器多语言弹窗。支持自动/手动翻译与异常提示。
 */
(function () {
  'use strict';

  var cfg = window.__AUTO_TRANSLATE_CONFIG__;
  if (!cfg || window.__AUTO_TRANSLATE_BOOTED__) {
    return;
  }
  window.__AUTO_TRANSLATE_BOOTED__ = true;

  var T = cfg.texts || {};
  var SRC = cfg.from;
  var DST = cfg.to;
  var AUTO_ATTR = 'data-at-auto';
  var MANUAL_ATTR = 'data-at-manual';
  var BOUND_ATTR = 'data-at-bound';
  var UI_ATTR = 'data-at-ui';
  var UID_ATTR = 'data-at-uid';
  var STORAGE_KEY = 'at_auto_' + SRC + '_' + DST;

  var uidSeed = 0;

  var state = {
    auto: readAutoFlag(),
    timers: new WeakMap(),
    lastSource: new WeakMap(),
    applying: new WeakSet(),
    bound: new WeakSet(),
    editorBound: new WeakMap(),
    pairMap: {},
    pairs: [],
    runningAll: false,
    refreshing: false,
    refreshTimer: null,
    panel: null
  };

  function readAutoFlag() {
    try {
      var saved = window.localStorage.getItem(STORAGE_KEY);
      if (saved !== null) {
        return saved === '1';
      }
    } catch (e) {
      // localStorage 不可用时使用插件配置
    }

    return !!cfg.autoEnabled;
  }

  function saveAutoFlag(value) {
    try {
      window.localStorage.setItem(STORAGE_KEY, value ? '1' : '0');
    } catch (e) {
      // 忽略存储异常
    }
  }

  function trim(value) {
    return String(value == null ? '' : value).replace(/^\s+|\s+$/g, '');
  }

  function hasChinese(value) {
    return /[\u3400-\u4dbf\u4e00-\u9fff]/.test(String(value || ''));
  }

  function escapeHtml(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function escapeAttr(value) {
    return String(value == null ? '' : value).replace(/["\\]/g, '\\$&');
  }

  function format(template, data) {
    var text = String(template || '');
    Object.keys(data || {}).forEach(function (key) {
      text = text.replace(new RegExp(':' + key, 'g'), data[key]);
    });

    return text;
  }

  function toast(message, type) {
    if (!message) {
      return;
    }

    if (window.layer && typeof window.layer.msg === 'function') {
      var icon = type === 'error' ? 2 : (type === 'warn' ? 0 : 1);
      window.layer.msg(message, { icon: icon, time: 3500 });
      return;
    }

    var el = document.createElement('div');
    el.className = 'at-toast at-toast--' + (type || 'ok');
    el.textContent = message;
    document.body.appendChild(el);
    window.setTimeout(function () {
      if (el.parentNode) {
        el.parentNode.removeChild(el);
      }
    }, 3500);
  }

  function nativeSetValue(el, value) {
    var proto = el.tagName === 'TEXTAREA' ? window.HTMLTextAreaElement.prototype : window.HTMLInputElement.prototype;
    var descriptor = Object.getOwnPropertyDescriptor(proto, 'value');
    if (descriptor && descriptor.set) {
      descriptor.set.call(el, value);
    } else {
      el.value = value;
    }
  }

  function csrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');

    return meta ? meta.getAttribute('content') : '';
  }

  function domUid(el, prefix) {
    var uid = el.getAttribute(UID_ATTR);
    if (!uid) {
      uid = prefix + (++uidSeed);
      el.setAttribute(UID_ATTR, uid);
    }

    return uid;
  }

  function isVisible(el) {
    return !!el && el.offsetParent !== null;
  }

  function skipElement(el) {
    if (!el || !el.tagName) {
      return true;
    }

    var tag = el.tagName;
    if (tag !== 'INPUT' && tag !== 'TEXTAREA') {
      return true;
    }

    var type = (el.getAttribute('type') || '').toLowerCase();
    return el.disabled || el.readOnly || type === 'hidden' || type === 'file' || type === 'submit' || type === 'button';
  }

  function getEditor(textarea) {
    if (!window.tinymce || !window.tinymce.editors) {
      return null;
    }

    var name = textarea.getAttribute('name');
    for (var i = 0; i < window.tinymce.editors.length; i++) {
      var editor = window.tinymce.editors[i];
      if (!editor || editor.removed) {
        continue;
      }

      var element = editor.getElement ? editor.getElement() : editor.targetElm;
      if (element === textarea) {
        return editor;
      }

      if (!element && name && editor.id === name) {
        return editor;
      }
    }

    return null;
  }

  function isTinyMce(el) {
    if (!el || el.tagName !== 'TEXTAREA') {
      return false;
    }

    return el.classList.contains('tinymce') || el.hasAttribute('data-tinymce-height') || !!getEditor(el);
  }

  function readValue(pair) {
    if (pair.html) {
      var editor = getEditor(pair.src);
      if (editor) {
        return editor.getContent();
      }
    }

    return pair.src.value || '';
  }

  function readTarget(pair) {
    if (pair.html) {
      var editor = getEditor(pair.dst);
      if (editor) {
        return editor.getContent();
      }
    }

    return pair.dst.value || '';
  }

  function writeValue(pair, value) {
    if (pair.html) {
      var editor = getEditor(pair.dst);
      if (editor) {
        state.applying.add(pair.dst);
        editor.setContent(value);
        try {
          editor.save();
          editor.nodeChanged();
        } catch (e) {
          // 忽略编辑器事件异常
        }
        window.setTimeout(function () {
          state.applying.delete(pair.dst);
        }, 0);

        return;
      }
    }

    state.applying.add(pair.dst);
    nativeSetValue(pair.dst, value);
    try {
      pair.dst.dispatchEvent(new Event('input', { bubbles: true }));
      pair.dst.dispatchEvent(new Event('change', { bubbles: true }));
    } catch (e) {
      // 忽略事件派发异常
    }
    window.setTimeout(function () {
      state.applying.delete(pair.dst);
    }, 0);
  }

  function setBusy(pair, busy) {
    if (!pair.btn) {
      return;
    }

    pair.btn.disabled = !!busy;
    pair.btn.classList.toggle('is-busy', !!busy);
  }

  function setStatus(pair, text, type) {
    if (!pair.statusEl) {
      return;
    }

    pair.statusEl.textContent = text || '';
    pair.statusEl.className = 'at-status' + (type ? ' at-status--' + type : '');
  }

  function setPanelStatus(text) {
    if (state.panel) {
      var el = state.panel.querySelector('.at-panel__status');
      if (el) {
        el.textContent = text || '';
      }
    }
  }

  function requestTranslate(text, html) {
    var body = [
      'from=' + encodeURIComponent(SRC),
      'to=' + encodeURIComponent(DST),
      'text=' + encodeURIComponent(text),
      'html=' + (html ? '1' : '0')
    ].join('&');

    return window.fetch(cfg.endpoint, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken()
      },
      body: body
    }).then(function (response) {
      return response.text().then(function (raw) {
        var data = null;
        try {
          data = JSON.parse(raw);
        } catch (e) {
          data = null;
        }

        if (!response.ok || !data || data.status !== 'success') {
          var message = (data && data.message) ? data.message : (T.request_failed + ' (' + response.status + ')');
          throw new Error(message);
        }

        var result = data.data && data.data.result ? data.data.result : '';
        if (!result) {
          throw new Error(T.request_failed);
        }

        return result;
      });
    });
  }

  /**
   * 按名称配对：descriptions[zh_cn][name] -> descriptions[en][name]
   * 同时覆盖 hot_keywords[zh_cn]、content[zh_cn] 等所有多语言命名字段
   */
  function collectNamedPairs(pairs) {
    var nodes = document.querySelectorAll('input[name], textarea[name]');
    Array.prototype.forEach.call(nodes, function (src) {
      if (skipElement(src)) {
        return;
      }

      var name = src.getAttribute('name') || '';
      if (!name || name.indexOf('[' + SRC + ']') === -1) {
        return;
      }

      var dstName = name.replace('[' + SRC + ']', '[' + DST + ']');
      var dst = document.querySelector('[name="' + escapeAttr(dstName) + '"]');
      if (!dst || dst === src || skipElement(dst)) {
        return;
      }

      pairs.push(makePair('n:' + name, src, dst, false));
    });

    // 兜底：通过 input-locale 组件的 class 配对（仅用于名称中不含语言代码的字段，避免与名称配对重复）
    Array.prototype.forEach.call(document.querySelectorAll('input.input-' + SRC + ', textarea.input-' + SRC), function (src) {
      if (skipElement(src)) {
        return;
      }

      var srcName = src.getAttribute('name') || '';
      if (srcName.indexOf('[' + SRC + ']') !== -1) {
        return;
      }

      var wrap = src.closest('.input-locale-wrap');
      if (!wrap) {
        return;
      }

      var dst = wrap.querySelector('.input-' + DST);
      if (!dst || dst === src || skipElement(dst)) {
        return;
      }

      var id = 'c:' + (src.getAttribute('name') || '') + '|' + (dst.getAttribute('name') || '');
      pairs.push(makePair(id, src, dst, false));
    });
  }

  function codeByLocaleName(name) {
    var target = trim(name);
    if (!target) {
      return '';
    }

    var names = cfg.langNames || {};
    var codes = Object.keys(names);
    for (var i = 0; i < codes.length; i++) {
      if (trim(names[codes[i]]) === target) {
        return codes[i];
      }
    }

    for (var j = 0; j < codes.length; j++) {
      if (trim(names[codes[j]]).toLowerCase() === target.toLowerCase()) {
        return codes[j];
      }
    }

    return '';
  }

  /**
   * Element UI 语言输入组：el-form-item.language-inputs 内通过 prepend 标签识别语言
   */
  function collectLanguageInputPairs(pairs) {
    if (!cfg.translateVariants) {
      return;
    }

    Array.prototype.forEach.call(document.querySelectorAll('.language-inputs'), function (group) {
      if (!isVisible(group)) {
        return;
      }

      var map = {};
      Array.prototype.forEach.call(group.querySelectorAll('.el-input-group'), function (inputGroup) {
        var label = inputGroup.querySelector('.el-input-group__prepend');
        var input = inputGroup.querySelector('input.el-input__inner, textarea.el-textarea__inner');
        if (!label || !input || skipElement(input)) {
          return;
        }

        var code = codeByLocaleName(label.textContent);
        if (code && !map[code]) {
          map[code] = input;
        }
      });

      if (!map[SRC] || !map[DST]) {
        return;
      }

      var id = 'el:' + domUid(group, 'g') + ':' + SRC;
      pairs.push(makePair(id, map[SRC], map[DST], true));
    });
  }

  /**
   * 设计器富文本多语言弹窗：textarea[data-code]
   */
  function collectDataCodePairs(pairs) {
    if (!cfg.translateVariants) {
      return;
    }

    Array.prototype.forEach.call(document.querySelectorAll('textarea[data-code="' + SRC + '"]'), function (src) {
      if (skipElement(src)) {
        return;
      }

      var dialog = src.closest('.el-dialog');
      if (!dialog || !isVisible(dialog)) {
        return;
      }

      var dst = dialog.querySelector('textarea[data-code="' + DST + '"]');
      if (!dst || skipElement(dst)) {
        return;
      }

      // 编辑器尚未初始化时无法安全读写，等待下一次扫描
      if (!getEditor(src) || !getEditor(dst)) {
        return;
      }

      var id = 'dc:' + domUid(dialog, 'd') + ':' + SRC;
      pairs.push(makePair(id, src, dst, true));
    });
  }

  function paneMatchesCode(pane, code) {
    var id = pane.id || '';
    var label = pane.getAttribute('aria-labelledby') || '';

    return id.endsWith('-' + code) || label.endsWith('-' + code);
  }

  /**
   * 设计器 text-i18n 组件：按 el-tabs 的 pane 语言分区配对
   */
  function collectTabPanePairs(pairs) {
    if (!cfg.translateVariants) {
      return;
    }

    var selector = 'input.el-input__inner, textarea.el-textarea__inner, textarea.tinymce';

    Array.prototype.forEach.call(document.querySelectorAll('.el-tab-pane'), function (pane) {
      if (!paneMatchesCode(pane, SRC)) {
        return;
      }

      var tabs = pane.closest('.el-tabs');
      if (!tabs) {
        return;
      }

      var src = pane.querySelector(selector);
      // 富文本 data-code 由 collectDataCodePairs 统一处理
      if (!src || skipElement(src) || src.hasAttribute('data-code')) {
        return;
      }

      var dstPane = null;
      Array.prototype.forEach.call(tabs.querySelectorAll('.el-tab-pane'), function (item) {
        if (!dstPane && paneMatchesCode(item, DST)) {
          dstPane = item;
        }
      });
      if (!dstPane) {
        return;
      }

      var dst = dstPane.querySelector(selector);
      if (!dst || dst === src || skipElement(dst)) {
        return;
      }

      var id = 'tab:' + domUid(tabs, 't') + ':' + SRC;
      pairs.push(makePair(id, src, dst, true));
    });
  }

  function makePair(id, src, dst, dialog) {
    var html = isTinyMce(src) || isTinyMce(dst);
    var existing = state.pairMap[id];

    if (existing) {
      if (existing.src !== src) {
        existing.uiAttached = false;
      }
      existing.src = src;
      existing.dst = dst;
      existing.html = html;
      existing.dialog = !!dialog;

      return existing;
    }

    var pair = {
      id: id,
      src: src,
      dst: dst,
      html: html,
      dialog: !!dialog,
      btn: null,
      statusEl: null,
      uiAttached: false
    };
    state.pairMap[id] = pair;

    return pair;
  }

  function schedule(pair, delay) {
    if (!state.auto) {
      return;
    }

    var previous = state.timers.get(pair.src);
    if (previous) {
      window.clearTimeout(previous);
    }

    var wait = typeof delay === 'number' ? delay : cfg.debounce;
    var timer = window.setTimeout(function () {
      state.timers.delete(pair.src);
      translatePair(pair, false);
    }, wait);

    state.timers.set(pair.src, timer);
  }

  function translatePair(pair, manual) {
    var text = readValue(pair);
    var trimmed = trim(text);

    if (!trimmed) {
      if (!manual && readTarget(pair) && pair.dst.getAttribute(AUTO_ATTR) === '1') {
        writeValue(pair, '');
        pair.dst.removeAttribute(AUTO_ATTR);
      }

      return Promise.resolve({ skipped: true, message: T.empty_source });
    }

    if (!manual && !hasChinese(text)) {
      return Promise.resolve({ skipped: true });
    }

    if (!manual && state.lastSource.get(pair.src) === text) {
      return Promise.resolve({ skipped: true });
    }

    var existing = targetValueOf(pair);
    var autoGenerated = pair.dst.getAttribute(AUTO_ATTR) === '1';
    var manualEdited = pair.dst.getAttribute(MANUAL_ATTR) === '1';

    if (!manual && !cfg.overwrite && trim(existing) && !autoGenerated) {
      var skipMessage = manualEdited ? T.skipped_manual : T.skipped_existing;
      setStatus(pair, skipMessage, 'warn');

      return Promise.resolve({ skipped: true, message: skipMessage });
    }

    setBusy(pair, true);
    setStatus(pair, T.translating, 'busy');

    return requestTranslate(text, pair.html).then(function (result) {
      writeValue(pair, result);
      pair.dst.setAttribute(AUTO_ATTR, '1');
      pair.dst.removeAttribute(MANUAL_ATTR);
      state.lastSource.set(pair.src, text);
      setStatus(pair, T.translated, 'ok');

      return { ok: true, result: result };
    }).catch(function (error) {
      setStatus(pair, T.translate_failed, 'error');
      toast((error && error.message) || T.network_error, 'error');

      return { ok: false, error: (error && error.message) || T.network_error };
    }).then(function (result) {
      setBusy(pair, false);

      return result;
    });
  }

  function targetValueOf(pair) {
    var value = readTarget(pair);

    return trim(value.replace(/<[^>]*>/g, ' '));
  }

  function bindPair(pair) {
    if (!state.bound.has(pair.src)) {
      state.bound.add(pair.src);

      var composing = false;
      pair.src.addEventListener('compositionstart', function () {
        composing = true;
      });
      pair.src.addEventListener('compositionend', function () {
        composing = false;
        schedule(pair);
      });
      pair.src.addEventListener('input', function () {
        if (!composing) {
          schedule(pair);
        }
      });
      pair.src.addEventListener('blur', function () {
        if (state.auto) {
          schedule(pair, 0);
        }
      });
      pair.src.setAttribute(BOUND_ATTR, '1');
    }

    if (!state.bound.has(pair.dst)) {
      state.bound.add(pair.dst);
      pair.dst.addEventListener('input', function () {
        if (state.applying.has(pair.dst)) {
          return;
        }
        pair.dst.setAttribute(MANUAL_ATTR, '1');
        pair.dst.removeAttribute(AUTO_ATTR);
      });
    }
  }

  function bindEditors(pairs) {
    var pending = 0;

    pairs.forEach(function (pair) {
      if (!pair.html) {
        return;
      }

      var editor = getEditor(pair.src);
      if (!editor) {
        pending++;
        return;
      }

      var bound = state.editorBound.get(editor) || {};
      if (bound[pair.id]) {
        return;
      }
      bound[pair.id] = true;
      state.editorBound.set(editor, bound);

      editor.on('input change undo redo', function () {
        if (!state.applying.has(pair.dst)) {
          schedule(pair);
        }
      });
      editor.on('blur', function () {
        if (state.auto) {
          schedule(pair, 0);
        }
      });
    });

    if (pending && (state.editorRetries || 0) < 5) {
      state.editorRetries = (state.editorRetries || 0) + 1;
      scheduleRefresh(800);
    } else if (!pending) {
      state.editorRetries = 0;
    }
  }

  function makeButton(pair, label, extraClass) {
    var button = document.createElement('button');
    button.type = 'button';
    button.className = 'at-btn ' + (extraClass || '');
    button.setAttribute('data-at-btn', '1');
    button.setAttribute('title', T.translate_field);
    button.textContent = label || T.translate_field;
    button.addEventListener('click', function (event) {
      event.preventDefault();
      event.stopPropagation();
      translatePair(pair, true);
    });

    pair.btn = button;

    return button;
  }

  function makeStatus(pair) {
    var status = document.createElement('span');
    status.className = 'at-status';
    pair.statusEl = status;

    return status;
  }

  function directChild(parent, selector) {
    for (var i = 0; i < parent.children.length; i++) {
      if (parent.children[i].matches(selector)) {
        return parent.children[i];
      }
    }

    return null;
  }

  function appendPairUi(pair, scope, appendFn) {
    var selector = '[data-at-for="' + escapeAttr(pair.id) + '"]';
    var button = scope.querySelector('.at-btn' + selector);
    if (button) {
      pair.btn = button;
      pair.statusEl = scope.querySelector('.at-status' + selector);
      pair.uiAttached = true;

      return;
    }

    var btn = makeButton(pair, pair.html ? T.translate_desc : T.translate_field, pair.html ? 'at-btn--small' : 'at-btn--mini');
    btn.setAttribute('data-at-for', pair.id);
    var status = makeStatus(pair);
    status.setAttribute('data-at-for', pair.id);
    appendFn(btn, status);
    pair.uiAttached = true;
  }

  function attachUi(pair) {
    var src = pair.src;
    if (!src || !document.body.contains(src)) {
      pair.uiAttached = false;
      return;
    }

    if (pair.uiAttached && pair.btn && document.body.contains(pair.btn)) {
      return;
    }
    pair.uiAttached = false;

    // Blade 多语言输入行
    var bladeGroup = src.closest('.input-for-group');
    if (bladeGroup) {
      bladeGroup.setAttribute(UI_ATTR, '1');
      appendPairUi(pair, bladeGroup, function (btn, status) {
        bladeGroup.appendChild(btn);
        bladeGroup.appendChild(status);
      });

      return;
    }

    // Element UI 语言输入
    var elGroup = src.closest('.el-input-group');
    if (elGroup && elGroup.parentNode) {
      var elParent = elGroup.parentNode;
      var actions = directChild(elParent, '.at-el-actions[data-at-for="' + escapeAttr(pair.id) + '"]');
      if (!actions) {
        actions = document.createElement('div');
        actions.className = 'at-el-actions';
        actions.setAttribute('data-at-for', pair.id);
        elParent.insertBefore(actions, elGroup.nextSibling);
      }
      appendPairUi(pair, actions, function (btn, status) {
        actions.appendChild(btn);
        actions.appendChild(status);
      });

      return;
    }

    // 普通输入框 / 富文本
    var host = src.closest('.input-group') || src.parentNode;
    if (!host || !host.parentNode) {
      return;
    }

    var hostParent = host.parentNode;
    var marker = directChild(hostParent, '.at-bar[data-at-for="' + escapeAttr(pair.id) + '"], .at-inline[data-at-for="' + escapeAttr(pair.id) + '"]');
    if (!marker) {
      marker = document.createElement('div');
      marker.className = pair.html ? 'at-bar' : 'at-inline';
      marker.setAttribute('data-at-for', pair.id);
      if (pair.html) {
        hostParent.insertBefore(marker, host);
      } else {
        hostParent.insertBefore(marker, host.nextSibling);
      }
    }
    appendPairUi(pair, marker, function (btn, status) {
      marker.appendChild(btn);
      marker.appendChild(status);
    });
  }

  /**
   * 去重：同一组源/目标字段只保留一个配对，避免多个识别规则叠加出重复按钮
   */
  function dedupePairs(pairs) {
    var seen = new Map();

    return pairs.filter(function (pair) {
      var targets = seen.get(pair.src);
      if (!targets) {
        targets = [];
        seen.set(pair.src, targets);
      }
      if (targets.indexOf(pair.dst) !== -1) {
        return false;
      }
      targets.push(pair.dst);

      return true;
    });
  }

  function refresh() {
    if (state.refreshing || state.runningAll) {
      return;
    }
    state.refreshing = true;

    try {
      var pairs = [];
      collectNamedPairs(pairs);
      collectLanguageInputPairs(pairs);
      collectDataCodePairs(pairs);
      collectTabPanePairs(pairs);
      pairs = dedupePairs(pairs);

      state.pairs = pairs;
      pairs.forEach(function (pair) {
        bindPair(pair);
        attachUi(pair);
      });
      bindEditors(pairs);
      togglePanel(pairs.length > 0);
    } finally {
      state.refreshing = false;
    }
  }

  function scheduleRefresh(delay) {
    if (state.refreshTimer) {
      window.clearTimeout(state.refreshTimer);
    }
    state.refreshTimer = window.setTimeout(function () {
      state.refreshTimer = null;
      refresh();
    }, typeof delay === 'number' ? delay : 120);
  }

  function togglePanel(visible) {
    if (state.panel) {
      state.panel.classList.toggle('is-hidden', !visible);
    }
  }

  function translateAll() {
    if (state.runningAll) {
      return;
    }

    var pairs = state.pairs.filter(function (pair) {
      return pair.src && document.body.contains(pair.src);
    });

    if (!pairs.length) {
      toast(T.no_fields, 'warn');
      return;
    }

    var queue = pairs.filter(function (pair) {
      return trim(readValue(pair)) !== '';
    });

    if (!queue.length) {
      toast(T.empty_source, 'warn');
      return;
    }

    state.runningAll = true;
    var total = queue.length;
    var finished = 0;
    var success = 0;
    var failed = 0;

    setPanelStatus(format(T.progress, { current: 0, total: total }));

    function next() {
      if (!queue.length) {
        return Promise.resolve();
      }

      var pair = queue.shift();
      return translatePair(pair, true).then(function (result) {
        finished++;
        if (result && result.ok) {
          success++;
        } else if (!(result && result.skipped)) {
          failed++;
        }
        setPanelStatus(format(T.progress, { current: finished, total: total }));

        return next();
      });
    }

    var workers = [];
    for (var i = 0; i < 2; i++) {
      workers.push(next());
    }

    Promise.all(workers).then(function () {
      state.runningAll = false;
      var message = format(T.done, { success: success, failed: failed });
      setPanelStatus(message);
      toast(message, failed ? 'warn' : 'ok');
      scheduleRefresh(200);
    });
  }

  function buildPanel() {
    var panel = document.createElement('div');
    panel.className = 'at-panel is-hidden';
    panel.innerHTML =
      '<div class="at-panel__head">' +
        '<span class="at-panel__title">' + escapeHtml(T.panel_title) + '</span>' +
        '<button type="button" class="at-panel__collapse">−</button>' +
      '</div>' +
      '<div class="at-panel__body">' +
        '<label class="at-switch">' +
          '<input type="checkbox" class="at-switch__input">' +
          '<span>' + escapeHtml(T.auto_mode) + '</span>' +
        '</label>' +
        '<button type="button" class="at-btn at-btn--primary at-panel__all">' + escapeHtml(T.translate_all) + '</button>' +
        '<div class="at-panel__status"></div>' +
      '</div>';

    var checkbox = panel.querySelector('.at-switch__input');
    checkbox.checked = state.auto;
    checkbox.addEventListener('change', function () {
      state.auto = checkbox.checked;
      saveAutoFlag(state.auto);
      setPanelStatus(state.auto ? T.auto_mode : '');
    });

    panel.querySelector('.at-panel__all').addEventListener('click', translateAll);

    var collapsed = false;
    try {
      collapsed = window.localStorage.getItem('at_panel_collapsed') === '1';
    } catch (e) {
      collapsed = false;
    }
    if (collapsed) {
      panel.classList.add('is-collapsed');
    }
    panel.querySelector('.at-panel__collapse').addEventListener('click', function () {
      collapsed = !panel.classList.contains('is-collapsed');
      panel.classList.toggle('is-collapsed', collapsed);
      try {
        window.localStorage.setItem('at_panel_collapsed', collapsed ? '1' : '0');
      } catch (e) {
        // 忽略存储异常
      }
    });

    document.body.appendChild(panel);
    state.panel = panel;
  }

  function injectStyle() {
    if (document.getElementById('at-style')) {
      return;
    }

    var style = document.createElement('style');
    style.id = 'at-style';
    style.textContent = [
      '.at-btn{display:inline-flex;align-items:center;justify-content:center;border:1px solid #c9ced6;background:#fff;color:#3a4250;border-radius:6px;cursor:pointer;font-size:12px;line-height:1;padding:6px 10px;white-space:nowrap;transition:all .15s ease;margin-left:6px;flex:0 0 auto;z-index:2}',
      '.at-btn:hover{background:#f2f6ff;border-color:#7aa2ff;color:#1e63ff}',
      '.at-btn:disabled{opacity:.6;cursor:not-allowed}',
      '.at-btn.is-busy{background:#eef4ff;border-color:#9db9ff;color:#1e63ff}',
      '.at-btn--mini{padding:4px 8px;font-size:12px}',
      '.at-btn--small{padding:5px 12px}',
      '.at-btn--primary{background:#1e63ff;border-color:#1e63ff;color:#fff;padding:7px 12px}',
      '.at-btn--primary:hover{background:#1754d8;border-color:#1754d8;color:#fff}',
      '.at-status{font-size:12px;line-height:1.4;margin-left:6px;color:#98a2b3;white-space:nowrap}',
      '.at-status--ok{color:#079455}',
      '.at-status--busy{color:#1e63ff}',
      '.at-status--warn{color:#b54708}',
      '.at-status--error{color:#d92d20}',
      '.at-bar{display:flex;align-items:center;gap:10px;justify-content:flex-end;margin-bottom:8px}',
      '.at-inline{display:flex;align-items:center;gap:6px;margin-top:4px}',
      '.at-el-actions{display:flex;align-items:center;gap:6px;margin-top:4px}',
      '.at-toast{position:fixed;top:24px;left:50%;transform:translateX(-50%);z-index:3000;padding:10px 18px;border-radius:8px;color:#fff;font-size:14px;box-shadow:0 8px 24px rgba(0,0,0,.16);background:#12b76a}',
      '.at-toast--error{background:#d92d20}',
      '.at-toast--warn{background:#b54708}',
      '.at-panel{position:fixed;right:20px;bottom:20px;z-index:2000;width:250px;background:#fff;border:1px solid #e4e7ec;border-radius:12px;box-shadow:0 12px 32px rgba(16,24,40,.14);font-size:13px;color:#344054;overflow:hidden}',
      '.at-panel.is-hidden{display:none}',
      '.at-panel__head{display:flex;align-items:center;justify-content:space-between;padding:10px 12px;background:#f8fafc;border-bottom:1px solid #eef2f6;font-weight:600}',
      '.at-panel__title{font-size:12px;color:#475467}',
      '.at-panel__collapse{border:0;background:transparent;font-size:16px;line-height:1;cursor:pointer;color:#667085;padding:0 4px}',
      '.at-panel__body{padding:12px;display:flex;flex-direction:column;gap:10px}',
      '.at-panel.is-collapsed .at-panel__body{display:none}',
      '.at-switch{display:flex;align-items:center;gap:8px;cursor:pointer;margin:0;font-weight:400}',
      '.at-switch__input{width:16px;height:16px;margin:0;accent-color:#1e63ff}',
      '.at-panel .at-btn{margin-left:0;justify-content:center}',
      '.at-panel__all{width:100%}',
      '.at-panel__status{min-height:16px;font-size:12px;color:#667085;word-break:break-all}',
      '.el-dialog .at-btn{margin-left:8px}',
      '.el-dialog .at-status{display:inline-block;margin-top:4px}',
      '.el-form-item .at-el-actions .at-btn{margin-left:0}'
    ].join('');

    document.head.appendChild(style);
  }

  function init() {
    injectStyle();
    buildPanel();
    refresh();

    var observer = new MutationObserver(function () {
      scheduleRefresh();
    });
    observer.observe(document.body, {
      childList: true,
      subtree: true,
      attributes: true,
      attributeFilter: ['style', 'class']
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
