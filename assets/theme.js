(function(){
  const KEY = 'pp-theme';
  const root = document.documentElement;

  function systemPrefers() {
    return (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches) ? 'light' : 'dark';
  }
  function applyTheme(theme) {
    const t = (theme === 'light' || theme === 'dark') ? theme : 'dark';
    root.setAttribute('data-theme', t);
    try { localStorage.setItem(KEY, t); } catch(e) {}
    updateButton(t);
  }
  function currentTheme() {
    try { return localStorage.getItem(KEY); } catch(e) { return null; }
  }
  function toggle() {
    applyTheme(root.getAttribute('data-theme') === 'light' ? 'dark' : 'light');
  }
  function updateButton(t) {
    const btn = document.querySelector('.nav-theme-toggle');
    if (!btn) return;
    btn.querySelector('.label').textContent = (t === 'light') ? 'Light' : 'Dark';
    btn.querySelector('.icon').textContent  = (t === 'light') ? '☀️' : '🌙';
  }
  function mountButton() {
    if (document.querySelector('.nav-theme-toggle')) return;
    const nav = document.querySelector('nav') || document.querySelector('header');
    if (!nav) return;
    const btn = document.createElement('button');
    btn.className = 'nav-theme-toggle';
    btn.type = 'button';
    btn.innerHTML = '<span class="icon">🌙</span><span class="label">Dark</span>';
    btn.addEventListener('click', toggle);
    nav.appendChild(btn);
    updateButton(root.getAttribute('data-theme') || 'dark');
  }
  const saved = currentTheme();
  applyTheme(saved || systemPrefers());
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', mountButton);
  else mountButton();
})();
