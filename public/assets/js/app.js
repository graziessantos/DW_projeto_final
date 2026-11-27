/* Entre Abas – comportamento do site (JavaScript puro). */
(function () {
  'use strict';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

  // ---- Menu recolhível (celular) ----
  var menuBtn = $('.menu-btn'), menu = $('#menu-principal');
  if (menuBtn && menu) {
    menuBtn.addEventListener('click', function () {
      var aberto = menu.classList.toggle('aberto');
      menuBtn.setAttribute('aria-expanded', aberto ? 'true' : 'false');
      menuBtn.setAttribute('aria-label', aberto ? 'Fechar menu' : 'Abrir menu');
    });
  }

  // ---- Janelas de confirmação (Sair, Editar, Remover): Esc fecha, foco preso ----
  $$('[data-abrir]').forEach(function (b) {
    b.addEventListener('click', function () {
      var d = document.getElementById(b.getAttribute('data-abrir'));
      if (d && d.showModal) d.showModal();
    });
  });
  $$('dialog [data-fechar]').forEach(function (b) {
    b.addEventListener('click', function () { b.closest('dialog').close(); });
  });

  // ---- Perfil: janela Editar (pastas da obra) ----
  var dlgEditar = $('#dlg-editar');
  if (dlgEditar) {
    var caixas = $$('input[type=checkbox]', dlgEditar), erroEd = $('#ed-erro');
    $$('[data-editar]').forEach(function (b) {
      b.addEventListener('click', function () {
        var id = b.getAttribute('data-editar');
        var atuais = (b.getAttribute('data-estantes') || '').split(',');
        $('#ed-id').value = id;
        $('#ed-dados').href = '/editar_obra.php?id=' + encodeURIComponent(id);
        caixas.forEach(function (c) { c.checked = atuais.indexOf(c.value) >= 0; });
        erroEd.hidden = true;
        dlgEditar.showModal();
      });
    });
    $('form', dlgEditar).addEventListener('submit', function (ev) {
      var marcada = caixas.some(function (c) { return c.checked; });
      if (!marcada) { ev.preventDefault(); erroEd.hidden = false; caixas[0].focus(); }
    });
  }

  // ---- Perfil: janela Remover ----
  var dlgRemover = $('#dlg-remover');
  if (dlgRemover) {
    $$('[data-remover]').forEach(function (b) {
      b.addEventListener('click', function () {
        $('#rm-id').value = b.getAttribute('data-remover');
        $('#rm-titulo').textContent = b.getAttribute('data-titulo');
        dlgRemover.showModal();
      });
    });
  }

  // ---- Perfil: resumo calculado (porcentagem de lidas e barra de progresso) ----
  var resumo = $('#resumo');
  if (resumo) {
    var total = +resumo.dataset.total || 0, lidas = +resumo.dataset.lido || 0;
    var pct = total > 0 ? Math.round((lidas / total) * 100) : 0;
    $('#st-pct').textContent = pct + '%';
    $('#prog').setAttribute('aria-valuenow', pct);
    $('#prog-fill').style.width = pct + '%';
  }

  // ---- Formulários de obra: exige ao menos uma estante em "Guardar em" ----
  $$('form').forEach(function (form) {
    var grupo = $('fieldset.grupo input[name="estantes[]"]', form);
    if (!grupo) return;
    form.addEventListener('submit', function (ev) {
      var caixas = $$('input[name="estantes[]"]', form);
      if (!caixas.some(function (c) { return c.checked; })) {
        ev.preventDefault();
        var fs = caixas[0].closest('fieldset'), msg = $('.erro', fs);
        if (!msg) {
          msg = document.createElement('p'); msg.className = 'erro'; msg.setAttribute('role', 'alert');
          msg.textContent = 'Marque pelo menos uma estante.'; fs.appendChild(msg);
        }
        caixas[0].focus();
        return;
      }
      // Compatibilidade com o back-end atual, que ainda lê um único "estante_id":
      // envia a primeira estante marcada. Quando o back-end passar a ler "estantes[]",
      // este trecho pode ser removido.
      var oculto = $('input[name="estante_id"]', form);
      if (!oculto) {
        oculto = document.createElement('input'); oculto.type = 'hidden'; oculto.name = 'estante_id';
        form.appendChild(oculto);
      }
      oculto.value = caixas.filter(function (c) { return c.checked; })[0].value;
    });
  });

  // ---- Sobre: abas Missão / Visão / Valores (setas do teclado) ----
  var sobre = $('[data-abas="sobre"]');
  if (sobre) {
    var tabs = $$('[role=tab]', sobre), paineis = $$('[role=tabpanel]', sobre);
    var ativar = function (i, foco) {
      tabs.forEach(function (t, k) { t.setAttribute('aria-selected', k === i ? 'true' : 'false'); t.tabIndex = k === i ? 0 : -1; });
      paineis.forEach(function (p, k) { p.hidden = k !== i; });
      if (foco) tabs[i].focus();
    };
    tabs.forEach(function (t, i) {
      t.addEventListener('click', function () { ativar(i); });
      t.addEventListener('keydown', function (ev) {
        if (ev.key === 'ArrowRight') { ev.preventDefault(); ativar((i + 1) % tabs.length, true); }
        if (ev.key === 'ArrowLeft') { ev.preventDefault(); ativar((i + tabs.length - 1) % tabs.length, true); }
      });
    });
    ativar(0);
  }
})();
