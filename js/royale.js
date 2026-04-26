/**
 * ROYALE VISTA — Global Luxury JS Framework
 * Anime.js powered · Real-time · World-class UX
 */
'use strict';

// ═══════════════════════════════════════════════════════════
//  CORE ANIMATION ENGINE
// ═══════════════════════════════════════════════════════════
const RV_Anim = {

  // Page enter: stagger elements with golden timing
  pageEnter(selector = '.rv-animate', delay = 0) {
    if (!window.anime) return;
    const els = document.querySelectorAll(selector);
    if (!els.length) return;
    anime({
      targets: els,
      opacity: [0, 1],
      translateY: [32, 0],
      duration: 800,
      easing: 'easeOutExpo',
      delay: anime.stagger(100, { start: delay })
    });
  },

  // Counter animation (for stats)
  counter(el, to, duration = 2200, prefix = '', suffix = '') {
    if (!el) return;
    const obj = { val: 0 };
    anime({
      targets: obj,
      val: to,
      round: 1,
      duration,
      easing: 'easeOutExpo',
      update() {
        el.textContent = prefix + Math.floor(obj.val).toLocaleString() + suffix;
      }
    });
  },

  // Magnetic button effect
  magnetic(el, strength = 0.3) {
    if (!el) return;
    el.addEventListener('mousemove', e => {
      const r = el.getBoundingClientRect();
      const x = (e.clientX - r.left - r.width / 2) * strength;
      const y = (e.clientY - r.top - r.height / 2) * strength;
      anime({ targets: el, translateX: x, translateY: y, duration: 300, easing: 'easeOutExpo' });
    });
    el.addEventListener('mouseleave', () => {
      anime({ targets: el, translateX: 0, translateY: 0, duration: 600, easing: 'easeOutElastic(1,.5)' });
    });
  },

  // Morph underline
  underline(el) {
    const line = el.querySelector('::after') || el;
    anime({ targets: line, scaleX: [0, 1], duration: 400, easing: 'easeOutExpo' });
  },

  // Smooth number reveal
  reveal(el) {
    anime({
      targets: el,
      opacity: [0, 1],
      translateY: [20, 0],
      duration: 600,
      easing: 'easeOutExpo'
    });
  },

  // Card hover 3D tilt
  init3DTilt(selector = '.rv-tilt') {
    document.querySelectorAll(selector).forEach(card => {
      card.addEventListener('mousemove', e => {
        const r    = card.getBoundingClientRect();
        const xPct = (e.clientX - r.left) / r.width  - 0.5;
        const yPct = (e.clientY - r.top)  / r.height - 0.5;
        anime({
          targets: card,
          rotateY: xPct * 12,
          rotateX: -yPct * 10,
          translateZ: 20,
          duration: 200,
          easing: 'linear'
        });
      });
      card.addEventListener('mouseleave', () => {
        anime({ targets: card, rotateY: 0, rotateX: 0, translateZ: 0, duration: 600, easing: 'easeOutElastic(1,.5)' });
      });
      card.style.transformStyle = 'preserve-3d';
    });
  },

  // Parallax scroll
  parallax(selector = '[data-parallax]') {
    const els = [...document.querySelectorAll(selector)];
    if (!els.length) return;
    const handler = () => {
      els.forEach(el => {
        const factor = parseFloat(el.dataset.parallax || '.3');
        const rect   = el.getBoundingClientRect();
        const center = rect.top + rect.height / 2 - window.innerHeight / 2;
        el.style.transform = `translateY(${center * factor}px)`;
      });
    };
    window.addEventListener('scroll', handler, { passive: true });
    handler();
  },

  // Stagger grid appearance
  staggerGrid(selector, stagger = 80) {
    const els = document.querySelectorAll(selector);
    if (!els.length) return;
    const obs = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const items = [...entry.target.querySelectorAll(selector + ' > *')].length
            ? entry.target.querySelectorAll(':scope > *')
            : [entry.target];
          anime({ targets: [...items], opacity: [0, 1], translateY: [24, 0], scale: [.97, 1], duration: 600, easing: 'easeOutExpo', delay: anime.stagger(stagger) });
          obs.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1 });
    document.querySelectorAll(selector).forEach(el => obs.observe(el));
  },

  // Text split reveal (char by char)
  splitText(el, delay = 0) {
    if (!el || !window.anime) return;
    const text = el.textContent;
    el.innerHTML = text.split('').map(c => c === ' ' ? '<span style="display:inline">&nbsp;</span>' : `<span style="display:inline-block;opacity:0;transform:translateY(20px)">${c}</span>`).join('');
    anime({
      targets: el.querySelectorAll('span'),
      opacity: [0, 1],
      translateY: [20, 0],
      duration: 600,
      easing: 'easeOutExpo',
      delay: anime.stagger(30, { start: delay })
    });
  },

  // Loading bar
  loadBar(width = '100%', cb) {
    const bar = document.getElementById('pg-bar');
    if (!bar) return cb?.();
    anime({ targets: bar, width: width, duration: 400, easing: 'easeOutExpo', complete: cb });
  }
};

// ═══════════════════════════════════════════════════════════
//  SCROLL REVEAL SYSTEM
// ═══════════════════════════════════════════════════════════
const revObs = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (!entry.isIntersecting) return;
    const el  = entry.target;
    const dir = el.dataset.reveal || 'up';
    const del = parseInt(el.dataset.delay || '0');

    const from = {
      up:    { translateY: 40 },
      down:  { translateY: -40 },
      left:  { translateX: -50 },
      right: { translateX: 50 },
      scale: { scale: .88 },
      fade:  {}
    }[dir] || { translateY: 40 };

    if (window.anime) {
      anime({
        targets: el,
        opacity: [0, 1],
        ...from,
        translateY: from.translateY ?? 0,
        translateX: from.translateX ?? 0,
        scale: from.scale ?? 1,
        duration: 800,
        easing: 'easeOutExpo',
        delay: del
      });
    } else {
      el.style.opacity   = '1';
      el.style.transform = 'none';
    }
    revObs.unobserve(el);
  });
}, { threshold: 0.07 });



// ═══════════════════════════════════════════════════════════
//  SHAMA — AI CONCIERGE CHATBOT
// ═══════════════════════════════════════════════════════════
const Habibi = {
  open: false,
  session: null,
  msgCount: 0,

  toggle() {
    this.open = !this.open;
    const win  = document.getElementById('habibiWin');
    const btn  = document.getElementById('habibiBubble');
    if (!win) return;
    win.classList.toggle('open', this.open);
    if (btn) {
      btn.innerHTML = this.open ? '×' : '🌙';
      btn.style.fontSize = this.open ? '22px' : '24px';
      if (window.anime) anime({ targets: btn, rotate: this.open ? [0,180] : [180,0], duration: 400, easing: 'easeOutExpo' });
    }
    if (this.open && !this.session) this.start();
  },

  async start() {
    this.session = 'habibi_' + Date.now();
    this.addMsg('bot', 
      'Salaam! 🌙 I\'m <strong>Habibi</strong>, your personal concierge at Royale Vista.<br><br>It\'s a pleasure to assist you. Whether you\'re planning a stay, seeking a restaurant recommendation, or need to arrange something extraordinary — I\'m here for you.',
      ['🛏 Explore Rooms', '🌍 Our Properties', '🍽 Reserve Dining', '💎 Membership', '📅 Book Now']
    );
  },

  addMsg(role, html, quickReplies = []) {
    const container = document.getElementById('habibiMsgs');
    if (!container) return;
    document.getElementById('habibiTyping')?.remove();

    const wrap = document.createElement('div');
    wrap.className = `habibi-msg ${role}`;
    wrap.innerHTML = role === 'bot'
      ? `<div class="habibi-av"><img src="https://images.unsplash.com/photo-1530268729831-4b0b9e170218?w=60&q=80" alt="Habibi" onerror="this.outerHTML='🌙'"></div><div class="habibi-bubble">${html}</div>`
      : `<div class="habibi-bubble">${html}</div><div class="habibi-av" style="background:var(--gold)">👤</div>`;
    container.appendChild(wrap);

    if (window.anime) {
      anime({ targets: wrap, opacity: [0,1], translateY: [12,0], duration: 350, easing: 'easeOutExpo' });
    }

    if (quickReplies.length) {
      const qr = document.getElementById('habibiQR');
      if (qr) {
        qr.innerHTML = quickReplies.map(r =>
          `<button class="habibi-qr-btn" onclick="Habibi.sendQuick('${r.replace(/'/g,"\\'")}')"><span>${r}</span></button>`
        ).join('');
        if (window.anime) anime({ targets: '.habibi-qr-btn', opacity: [0,1], translateY: [8,0], duration: 300, delay: anime.stagger(50), easing: 'easeOutExpo' });
      }
    } else {
      const qr = document.getElementById('habibiQR');
      if (qr) qr.innerHTML = '';
    }

    container.scrollTop = container.scrollHeight;
    this.msgCount++;
  },

  showTyping() {
    const c = document.getElementById('habibiMsgs');
    if (!c) return;
    const d = document.createElement('div');
    d.id = 'habibiTyping';
    d.className = 'habibi-msg bot';
    d.innerHTML = `<div class="habibi-av"><img src="https://images.unsplash.com/photo-1530268729831-4b0b9e170218?w=60&q=80" onerror="this.outerHTML='🌙'"></div><div class="habibi-bubble habibi-typing"><span></span><span></span><span></span></div>`;
    c.appendChild(d);
    c.scrollTop = c.scrollHeight;
  },

  sendQuick(text) { this.send(text); },

  async send(text) {
    text = text?.trim();
    if (!text) return;
    const input = document.getElementById('habibiInput');
    if (input) input.value = '';
    const qr = document.getElementById('habibiQR');
    if (qr) qr.innerHTML = '';

    this.addMsg('user', text);
    this.showTyping();

    try {
      const fd = new FormData();
      fd.append('message', text);
      fd.append('session_id', this.session || '');
      fd.append('bot_name', 'Habibi');
      const res  = await fetch((window.RV?.base || '') + '/api/chat.php', { method: 'POST', body: fd });
      const data = await res.json();
      this.addMsg('bot', data.reply || 'I\'m here to assist you!', data.quick_replies || []);
    } catch(e) {
      this.addMsg('bot', 'My sincerest apologies, I seem to be momentarily unavailable. Please <a href="/contact.php" style="color:var(--gold)">contact our team</a> directly.');
    }
  }
};
window.Habibi = Habibi;

// ═══════════════════════════════════════════════════════════
//  WISHLIST
// ═══════════════════════════════════════════════════════════
async function toggleWishlist(roomTypeId, btn) {
  if (!window._isLoggedIn) { window.toast?.('Please login to save to your wishlist', 'info'); return; }
  btn.classList.toggle('wished');
  if (window.anime) anime({ targets: btn, scale: [1, 1.4, 1], duration: 400, easing: 'easeOutElastic(1,.5)' });
  try {
    const fd = new FormData(); fd.append('room_type_id', roomTypeId);
    const res  = await fetch((window.RV?.base || '') + '/api/wishlist.php', { method: 'POST', body: fd });
    const data = await res.json();
    window.toast?.(data.wishlisted ? '❤ Added to wishlist' : 'Removed from wishlist', 'success');
  } catch(e) { btn.classList.toggle('wished'); }
}
window.toggleWishlist = toggleWishlist;

// ═══════════════════════════════════════════════════════════
//  TOAST SYSTEM
// ═══════════════════════════════════════════════════════════
function toast(msg, type = 'info', dur = 4000) {
  const stack = document.getElementById('toastStack');
  if (!stack) return;
  const el = document.createElement('div');
  el.className = `rv-toast rv-toast--${type}`;
  el.innerHTML = `
    <div class="rv-toast__icon">${{success:'✓',error:'✗',info:'◆',warning:'⚠'}[type]||'◆'}</div>
    <div class="rv-toast__text">${msg}</div>
    <button onclick="this.parentElement.remove()" class="rv-toast__close">×</button>
  `;
  stack.appendChild(el);
  if (window.anime) anime({ targets: el, opacity: [0,1], translateX: [20,0], duration: 350, easing: 'easeOutExpo' });
  setTimeout(() => {
    if (window.anime) anime({ targets: el, opacity: 0, translateX: 20, duration: 300, easing: 'easeInExpo', complete: () => el?.remove() });
    else el?.remove();
  }, dur);
}
window.toast = toast;

// ═══════════════════════════════════════════════════════════
//  INIT
// ═══════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', () => {
  // Observe all rv-animate elements
  document.querySelectorAll('[data-reveal]').forEach(el => {
    el.style.opacity = '0';
    revObs.observe(el);
  });



  // Init 3D tilt on cards
  RV_Anim.init3DTilt('.rv-tilt');

  // Init parallax
  RV_Anim.parallax();

  // Magnetic buttons
  document.querySelectorAll('.btn-gold.btn-xl, .btn-gold.btn-lg').forEach(el => {
    RV_Anim.magnetic(el, 0.25);
  });

  // Animate counter sections
  const counterObs = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (!e.isIntersecting) return;
      e.target.querySelectorAll('[data-count]').forEach(el => {
        const to     = parseFloat(el.dataset.count);
        const pre    = el.dataset.pre || '';
        const suf    = el.dataset.suf || '';
        RV_Anim.counter(el, to, 2200, pre, suf);
      });
      counterObs.unobserve(e.target);
    });
  }, { threshold: 0.1 });
  document.querySelectorAll('.rv-counter-section').forEach(el => counterObs.observe(el));

  // Page load bar
  RV_Anim.loadBar('100%');

  // Expose for other scripts
  window.RV_Anim = RV_Anim;
});

// Export
window.RV_Anim = RV_Anim;

