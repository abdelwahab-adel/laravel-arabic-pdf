'use strict';

/* ─── NAVBAR SCROLL ─── */
const nb = document.getElementById('navbar');
if(nb) window.addEventListener('scroll',()=>nb.classList.toggle('scrolled',scrollY>40),{passive:true});

/* ─── HAMBURGER ─── */
const ham = document.getElementById('hamburger');
const mNav = document.querySelector('.mobile-nav');
if(ham && mNav){
  ham.addEventListener('click',()=>{
    const open = mNav.classList.toggle('open');
    ham.setAttribute('aria-expanded',open);
    const [a,b,c] = ham.querySelectorAll('span');
    if(open){a.style.transform='rotate(45deg) translate(5px,5px)';b.style.opacity=0;c.style.transform='rotate(-45deg) translate(5px,-5px)'}
    else{a.style.transform=b.style.opacity=c.style.transform=''}
  });
  mNav.querySelectorAll('a').forEach(l=>l.addEventListener('click',()=>{mNav.classList.remove('open');ham.querySelectorAll('span').forEach(s=>s.style.transform=s.style.opacity='')}));
}

/* ─── REVEAL ON SCROLL ─── */
if('IntersectionObserver' in window){
  const io = new IntersectionObserver(es=>{
    es.forEach((e,i)=>{if(e.isIntersecting){setTimeout(()=>e.target.classList.add('in'),i%5*80);io.unobserve(e.target)}})
  },{threshold:.12,rootMargin:'0px 0px -40px 0px'});
  document.querySelectorAll('.reveal').forEach(el=>io.observe(el));
}

/* ─── COUNTER ─── */
function animCount(el){
  const t=+el.dataset.target,s=el.dataset.suffix||'',d=1600,start=performance.now();
  (function tick(now){
    const p=Math.min((now-start)/d,1),v=Math.round((1-Math.pow(1-p,3))*t);
    el.textContent=v.toLocaleString()+s;
    p<1&&requestAnimationFrame(tick);
  })(start);
}
if('IntersectionObserver' in window){
  const co=new IntersectionObserver(es=>{es.forEach(e=>{if(e.isIntersecting){animCount(e.target);co.unobserve(e.target)}})},{threshold:.5});
  document.querySelectorAll('[data-target]').forEach(el=>co.observe(el));
}

/* ─── FORM ─── */
const form = document.getElementById('bookForm');
if(form){
  form.addEventListener('submit',e=>{
    e.preventDefault();
    const btn=form.querySelector('.form-submit');
    const orig=btn.innerHTML;
    btn.innerHTML='✓ &nbsp;Request Sent Successfully!';
    btn.style.background='var(--green)';
    setTimeout(()=>{btn.innerHTML=orig;btn.style.background='';form.reset();},3000);
  });
}

/* ─── ACTIVE NAV ─── */
const sections=document.querySelectorAll('section[id]');
const navAs=document.querySelectorAll('.nav-a');
if(sections.length&&'IntersectionObserver' in window){
  const sio=new IntersectionObserver(es=>{es.forEach(e=>{if(e.isIntersecting)navAs.forEach(a=>a.classList.toggle('active',a.getAttribute('href')==='#'+e.target.id))})},{threshold:.4});
  sections.forEach(s=>sio.observe(s));
}

/* ─── LANG BTNS ─── */
document.querySelectorAll('.lang-btn').forEach(b=>{
  b.addEventListener('click',()=>{document.querySelectorAll('.lang-btn').forEach(x=>x.classList.remove('active'));b.classList.add('active')});
});

/* ─── SMOOTH HASH LINKS ─── */
document.querySelectorAll('a[href^="#"]').forEach(a=>{
  a.addEventListener('click',e=>{
    const t=document.querySelector(a.getAttribute('href'));
    if(t){e.preventDefault();t.scrollIntoView({behavior:'smooth',block:'start'})}
  });
});
