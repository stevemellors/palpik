(function(){
  var KEY='pp-theme';
  function getTheme(){ try{return localStorage.getItem(KEY)}catch(e){return null} }
  function setTheme(t){
    t=(t==='light'||t==='dark')?t:'dark';
    var root=document.documentElement, body=document.body;
    root.setAttribute('data-theme',t);
    if(body){ body.classList.toggle('is-light',t==='light'); body.classList.toggle('is-dark',t!=='light'); }
    try{localStorage.setItem(KEY,t)}catch(e){}
    var btn=document.getElementById('admThemeBtn');
    if(btn){ btn.querySelector('.label').textContent=(t==='light')?'Light':'Dark';
             btn.querySelector('.icon').textContent =(t==='light')?'☀️':'🌙'; }
  }
  function toggle(){ setTheme((document.documentElement.getAttribute('data-theme')==='light')?'dark':'light'); }
  function mount(){
    var brand=document.querySelector(".admin-topbar .brand, header.admin .brand, .admin-header .brand, a.brand");
    var host = brand?brand.parentNode:document.querySelector(".admin-topbar, header.admin, .admin-header, nav, header");
    if(!host||document.getElementById('admThemeBtn')) return;
    var btn=document.createElement('button');
    btn.id='admThemeBtn'; btn.className='nav-theme-toggle'; btn.type='button';
    btn.style.cssText="margin-left:12px;display:inline-flex;align-items:center;gap:6px;padding:4px 8px;border-radius:6px;border:1px solid var(--line);background:var(--card);color:var(--ink);cursor:pointer;";
    btn.innerHTML='<span class="icon">🌙</span><span class="label">Dark</span>';
    btn.addEventListener('click',toggle);
    if(brand&&brand.nextSibling){ brand.parentNode.insertBefore(btn,brand.nextSibling); } else { host.appendChild(btn); }
    setTheme(getTheme() || (window.matchMedia&&window.matchMedia('(prefers-color-scheme: light)').matches?'light':'dark'));
  }
  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',mount); else mount();
})();
