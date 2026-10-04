<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Tickets IA</title>
<style>
:root{--bg:#0f172a;--card:#1e293b;--line:#334155;--txt:#e2e8f0;--mut:#94a3b8;--acc:#6366f1;--acc2:#818cf8}
*{box-sizing:border-box}
body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:var(--bg);color:var(--txt);line-height:1.5}
header{padding:28px 20px 8px;max-width:960px;margin:auto}
h1{margin:0;font-size:1.8rem}
header p{color:var(--mut);margin:.3rem 0 0}
main{max-width:960px;margin:auto;padding:16px 20px 60px;display:grid;gap:20px;grid-template-columns:1fr}
@media(min-width:860px){main{grid-template-columns:340px 1fr;align-items:start}}
.card{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:18px}
label{display:block;font-size:.85rem;color:var(--mut);margin:12px 0 4px}
input,textarea,select{width:100%;padding:10px;border-radius:8px;border:1px solid var(--line);background:#0b1220;color:var(--txt);font:inherit}
textarea{min-height:110px;resize:vertical}
button{cursor:pointer;border:0;border-radius:8px;padding:10px 16px;background:var(--acc);color:#fff;font:inherit;font-weight:600}
button:hover{background:var(--acc2)}
button:disabled{opacity:.6;cursor:wait}
button.sec{background:transparent;border:1px solid var(--line);color:var(--txt)}
.full{width:100%;margin-top:16px}
.bar{display:flex;gap:10px;margin-bottom:14px}
.ticket{margin-bottom:14px}
.ticket h3{margin:0 0 4px;font-size:1.05rem}
.meta{font-size:.8rem;color:var(--mut)}
.body{white-space:pre-wrap;margin:10px 0;color:#cbd5e1}
.badge{display:inline-block;padding:2px 10px;border-radius:999px;font-size:.75rem;font-weight:600;margin-right:6px;background:#334155}
.alta{background:#b91c1c}.media{background:#b45309}.baja{background:#15803d}
.ia{margin-top:12px;padding:12px;border-radius:8px;background:#0b1220;border-left:3px solid var(--acc)}
.ia b{color:var(--acc2)}
.ia p{margin:.3rem 0 .6rem}
.msg{margin-top:10px;font-size:.9rem}.err{color:#fca5a5}.ok{color:#86efac}
.empty{color:var(--mut);text-align:center;padding:30px}
</style>
</head>
<body>
<header>
  <h1>🎫 Tickets IA</h1>
  <p>Crea un ticket de soporte y deja que la IA lo resuma, lo clasifique y proponga una respuesta.</p>
</header>
<main>
  <section class="card">
    <h2 style="margin:0 0 4px;font-size:1.1rem">Nuevo ticket</h2>
    <form id="form">
      <label for="subject">Asunto</label>
      <input id="subject" required maxlength="255" placeholder="No puedo entrar a mi cuenta">
      <label for="body">Descripción</label>
      <textarea id="body" required maxlength="10000" placeholder="Cuéntanos qué te pasa..."></textarea>
      <label for="email">Correo del cliente (opcional)</label>
      <input id="email" type="email" maxlength="255" placeholder="cliente@ejemplo.com">
      <button class="full" id="send">Crear ticket</button>
      <div id="formMsg" class="msg"></div>
    </form>
  </section>
  <section>
    <div class="bar">
      <select id="fCat"><option value="">Todas las categorías</option><option>facturacion</option><option>tecnico</option><option>cuenta</option><option>envio</option><option>otro</option></select>
      <select id="fPri"><option value="">Todas las prioridades</option><option>alta</option><option>media</option><option>baja</option></select>
    </div>
    <div id="list"></div>
  </section>
</main>
<script>
const $ = s => document.querySelector(s);
const api = async (url, opts = {}) => {
  const r = await fetch(url, {headers: {'Accept': 'application/json', 'Content-Type': 'application/json'}, ...opts});
  const j = await r.json().catch(() => ({}));
  if (!r.ok) throw new Error(j.message || 'Error ' + r.status);
  return j;
};
const el = (tag, cls, text) => { const e = document.createElement(tag); if (cls) e.className = cls; if (text !== undefined) e.textContent = text; return e; };

function iaBlock(t) {
  const d = el('div', 'ia');
  const head = el('div');
  head.append(el('span', 'badge', t.category), el('span', 'badge ' + t.priority, 'prioridad ' + t.priority));
  d.append(head);
  const add = (label, text) => { const p = el('p'); p.append(el('b', '', label + ': '), document.createTextNode(text || '')); d.append(p); };
  add('Resumen', t.summary);
  add('Respuesta sugerida', t.suggested_reply);
  return d;
}

function render(t) {
  const c = el('article', 'card ticket');
  c.append(el('h3', '', t.subject));
  c.append(el('div', 'meta', '#' + t.id + (t.customer_email ? ' · ' + t.customer_email : '')));
  c.append(el('div', 'body', t.body));
  const holder = el('div');
  if (t.analyzed_at) holder.append(iaBlock(t));
  const btn = el('button', 'sec', t.analyzed_at ? 'Volver a analizar' : '✨ Analizar con IA');
  const msg = el('div', 'msg');
  btn.onclick = async () => {
    btn.disabled = true; btn.textContent = 'Analizando...'; msg.textContent = '';
    try {
      const r = await api('/api/tickets/' + t.id + '/analyze', {method: 'POST'});
      holder.replaceChildren(iaBlock(r));
      btn.textContent = 'Volver a analizar';
    } catch (e) { msg.className = 'msg err'; msg.textContent = e.message; btn.textContent = '✨ Analizar con IA'; }
    btn.disabled = false;
  };
  c.append(btn, msg, holder);
  return c;
}

async function load() {
  const q = new URLSearchParams();
  if ($('#fCat').value) q.set('category', $('#fCat').value);
  if ($('#fPri').value) q.set('priority', $('#fPri').value);
  const list = $('#list');
  try {
    const r = await api('/api/tickets?' + q);
    list.replaceChildren(...(r.data.length ? r.data.map(render) : [el('div', 'empty', 'No hay tickets todavía. Crea el primero.')]));
  } catch (e) { list.replaceChildren(el('div', 'empty err', e.message)); }
}

$('#form').onsubmit = async ev => {
  ev.preventDefault();
  const b = $('#send'), m = $('#formMsg');
  b.disabled = true; m.textContent = '';
  try {
    const body = {subject: $('#subject').value, body: $('#body').value};
    if ($('#email').value) body.customer_email = $('#email').value;
    await api('/api/tickets', {method: 'POST', body: JSON.stringify(body)});
    ev.target.reset(); m.className = 'msg ok'; m.textContent = 'Ticket creado.';
    load();
  } catch (e) { m.className = 'msg err'; m.textContent = e.message; }
  b.disabled = false;
};
$('#fCat').onchange = $('#fPri').onchange = load;
load();
</script>
</body>
</html>
