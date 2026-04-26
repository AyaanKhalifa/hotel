/* ============================================================
   ROYALE VISTA v2.1 — Main Application JavaScript
   GSAP Animations • Cart • Chatbot • Loyalty • Reviews
   ============================================================ */
'use strict';

// ── GSAP ScrollReveal ────────────────────────────────────────
const revealObserver = new IntersectionObserver((entries) => {
  entries.forEach((entry, i) => {
    if (entry.isIntersecting) {
      const el = entry.target;
      const delay = el.dataset.delay || (el.classList.contains('stagger-1') ? '100ms' : el.classList.contains('stagger-2') ? '200ms' : el.classList.contains('stagger-3') ? '300ms' : el.classList.contains('stagger-4') ? '400ms' : '0ms');
      el.style.transitionDelay = delay;
      el.classList.add('revealed');
      revealObserver.unobserve(el);
    }
  });
}, { threshold: 0.08 });

document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-scale').forEach(el => revealObserver.observe(el));

// ── Particle Canvas ─────────────────────────────────────────
class ParticleSystem {
  constructor(canvasId) {
    this.canvas = document.getElementById(canvasId);
    if (!this.canvas) return;
    this.ctx = this.canvas.getContext('2d');
    this.particles = [];
    this.resize();
    this.init();
    this.animate();
    window.addEventListener('resize', () => this.resize());
  }

  resize() {
    if (!this.canvas) return;
    this.canvas.width  = this.canvas.parentElement.offsetWidth;
    this.canvas.height = this.canvas.parentElement.offsetHeight;
  }

  init() {
    const count = Math.floor(this.canvas.width / 10);
    for (let i = 0; i < count; i++) {
      this.particles.push({
        x: Math.random() * this.canvas.width,
        y: Math.random() * this.canvas.height,
        r: Math.random() * 1.5 + 0.3,
        vx: (Math.random() - 0.5) * 0.3,
        vy: (Math.random() - 0.5) * 0.3,
        alpha: Math.random() * 0.5 + 0.1,
      });
    }
  }

  animate() {
    if (!this.canvas) return;
    const isDark = document.documentElement.getAttribute('data-theme') !== 'light';
    this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
    const color = isDark ? '212,175,55' : '180,140,40';

    this.particles.forEach(p => {
      p.x += p.vx; p.y += p.vy;
      if (p.x < 0 || p.x > this.canvas.width)  p.vx *= -1;
      if (p.y < 0 || p.y > this.canvas.height) p.vy *= -1;
      this.ctx.beginPath();
      this.ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
      this.ctx.fillStyle = `rgba(${color}, ${p.alpha})`;
      this.ctx.fill();
    });

    // Draw connections
    this.particles.forEach((a, i) => {
      this.particles.slice(i+1).forEach(b => {
        const dist = Math.hypot(a.x - b.x, a.y - b.y);
        if (dist < 100) {
          this.ctx.beginPath();
          this.ctx.moveTo(a.x, a.y);
          this.ctx.lineTo(b.x, b.y);
          this.ctx.strokeStyle = `rgba(${color}, ${(1 - dist/100) * 0.12})`;
          this.ctx.lineWidth = 0.5;
          this.ctx.stroke();
        }
      });
    });

    requestAnimationFrame(() => this.animate());
  }
}

// ── Number Counter ───────────────────────────────────────────
function animateCounter(el) {
  const target = parseInt(el.dataset.target || el.textContent.replace(/[^0-9]/g, ''));
  if (!target) return;
  const duration = 1800;
  const start    = performance.now();
  const startVal = 0;
  const suffix   = el.dataset.suffix || '';
  const prefix   = el.dataset.prefix || '';

  function update(now) {
    const elapsed  = now - start;
    const progress = Math.min(elapsed / duration, 1);
    const eased    = 1 - Math.pow(1 - progress, 3);
    const current  = Math.floor(startVal + (target - startVal) * eased);
    el.textContent = prefix + current.toLocaleString() + suffix;
    if (progress < 1) requestAnimationFrame(update);
  }
  requestAnimationFrame(update);
}

const counterObserver = new IntersectionObserver((entries) => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      e.target.querySelectorAll('.counter[data-target]').forEach(animateCounter);
      counterObserver.unobserve(e.target);
    }
  });
}, { threshold: 0.3 });
document.querySelectorAll('.counter-section').forEach(el => counterObserver.observe(el));

// ── 3D Tilt Cards ────────────────────────────────────────────
document.querySelectorAll('.tilt-card').forEach(card => {
  card.addEventListener('mousemove', e => {
    const rect   = card.getBoundingClientRect();
    const x      = e.clientX - rect.left - rect.width / 2;
    const y      = e.clientY - rect.top - rect.height / 2;
    const rotX   = (-y / rect.height) * 12;
    const rotY   = (x / rect.width)  * 12;
    card.style.transform = `perspective(800px) rotateX(${rotX}deg) rotateY(${rotY}deg) scale(1.02)`;
  });
  card.addEventListener('mouseleave', () => {
    card.style.transform = '';
  });
});

// ── Cursor Glow ──────────────────────────────────────────────
const glow = document.querySelector('.cursor-glow');
if (glow && window.matchMedia('(pointer: fine)').matches) {
  document.addEventListener('mousemove', e => {
    glow.style.left = (e.clientX - 10) + 'px';
    glow.style.top  = (e.clientY - 10) + 'px';
  });
  document.querySelectorAll('.btn-gold, .room-card, .room-card-v2, .gallery-item').forEach(el => {
    el.addEventListener('mouseenter', () => {
      glow.style.width = '60px'; glow.style.height = '60px';
      glow.style.marginLeft = '-20px'; glow.style.marginTop = '-20px';
    });
    el.addEventListener('mouseleave', () => {
      glow.style.width = '20px'; glow.style.height = '20px';
      glow.style.marginLeft = '0'; glow.style.marginTop = '0';
    });
  });
}

// ── ===== CART SYSTEM ===== ──────────────────────────────────
const Cart = {
  items: [],
  checkin:  '',
  checkout: '',

  async load() {
    try {
      const res  = await fetch(window.RV?.base + '/api/cart.php?action=get');
      const data = await res.json();
      this.items    = data.items || [];
      this.checkin  = data.checkin || '';
      this.checkout = data.checkout || '';
      this.updateUI();
    } catch(e) {}
  },

  async add(roomTypeId, name, priceUsd, qty, checkin, checkout, guests) {
    const fd = new FormData();
    fd.append('action', 'add');
    fd.append('room_type_id', roomTypeId);
    fd.append('quantity', qty);
    fd.append('check_in', checkin);
    fd.append('check_out', checkout);
    fd.append('guests', guests);
    try {
      const res  = await fetch(window.RV?.base + '/api/cart.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success) {
        this.items    = data.items;
        this.checkin  = checkin;
        this.checkout = checkout;
        this.updateUI();
        this.open();
        toast('Room added to booking cart!', 'success');
      }
    } catch(e) { toast('Failed to add room', 'error'); }
  },

  async remove(roomTypeId) {
    const fd = new FormData();
    fd.append('action', 'remove');
    fd.append('room_type_id', roomTypeId);
    try {
      const res  = await fetch(window.RV?.base + '/api/cart.php', { method: 'POST', body: fd });
      const data = await res.json();
      this.items = data.items;
      this.updateUI();
    } catch(e) {}
  },

  updateUI() {
    const sidebar = document.getElementById('cartSidebar');
    const badge   = document.getElementById('cartBadge');
    const list    = document.getElementById('cartItemsList');
    const total   = document.getElementById('cartTotal');
    const count   = document.getElementById('cartCount');
    const emptyEl = document.getElementById('cartEmpty');
    const checkoutBtn = document.getElementById('cartCheckout');

    const totalQty = this.items.reduce((s, i) => s + (i.quantity || 1), 0);
    const nights   = this.checkin && this.checkout ? Math.max(1, Math.round((new Date(this.checkout) - new Date(this.checkin)) / 86400000)) : 1;
    const totalUsd = this.items.reduce((s, i) => s + (i.price_usd * (i.quantity || 1) * nights), 0);

    if (badge) { badge.textContent = totalQty; badge.classList.toggle('visible', totalQty > 0); }
    if (count) count.textContent = totalQty + ' item' + (totalQty !== 1 ? 's' : '');
    if (total) total.textContent = fmtPrice(totalUsd);
    if (emptyEl) emptyEl.style.display = this.items.length ? 'none' : 'block';
    if (checkoutBtn) checkoutBtn.style.display = this.items.length ? 'block' : 'none';

    if (list) {
      list.innerHTML = this.items.map(item => {
        const itemTotal = fmtPrice(item.price_usd * (item.quantity || 1) * nights);
        return `<div class="cart-item" data-id="${item.room_type_id}">
          <div class="cart-item-img">${item.emoji || '🏨'}</div>
          <div>
            <div class="cart-item-name">${item.name}</div>
            <div class="cart-item-meta">${nights} night${nights!==1?'s':''} · ${item.quantity || 1} room${(item.quantity||1)!==1?'s':''}</div>
            <div class="cart-qty">
              <button class="cart-qty-btn" onclick="cartQty(${item.room_type_id},-1)">−</button>
              <span class="cart-qty-num">${item.quantity || 1}</span>
              <button class="cart-qty-btn" onclick="cartQty(${item.room_type_id},1)">+</button>
            </div>
          </div>
          <div>
            <div class="cart-item-price">${itemTotal}</div>
            <button onclick="Cart.remove(${item.room_type_id})" style="background:none;border:none;color:var(--muted);cursor:pointer;font-size:18px;margin-top:4px" title="Remove">×</button>
          </div>
        </div>`;
      }).join('');
    }
  },

  open()  { document.getElementById('cartSidebar')?.classList.add('open'); document.getElementById('cartOverlay')?.classList.add('open'); },
  close() { document.getElementById('cartSidebar')?.classList.remove('open'); document.getElementById('cartOverlay')?.classList.remove('open'); },

  checkout() {
    if (!this.items.length) { toast('Your cart is empty', 'error'); return; }
    if (!this.checkin || !this.checkout) { toast('Please select dates first', 'error'); return; }
    const url = `${window.RV?.base}/booking.php?cart=1&checkin=${this.checkin}&checkout=${this.checkout}`;
    window.location.href = url;
  }
};

window.Cart = Cart;
window.cartQty = async (id, delta) => {
  const fd = new FormData();
  fd.append('action', 'qty');
  fd.append('room_type_id', id);
  fd.append('delta', delta);
  const res  = await fetch(window.RV?.base + '/api/cart.php', { method: 'POST', body: fd });
  const data = await res.json();
  Cart.items = data.items;
  Cart.updateUI();
};

// Load cart on page load
document.addEventListener('DOMContentLoaded', () => {
  if (typeof RV !== 'undefined') Cart.load();
});

// ── ===== CHATBOT ===== ──────────────────────────────────────
const Chatbot = {
  open: false,
  sessionId: null,
  messages: [],

  toggle() {
    this.open = !this.open;
    const win = document.getElementById('chatbotWindow');
    const btn = document.getElementById('chatBubble');
    if (!win || !btn) return;
    win.classList.toggle('open', this.open);
    btn.classList.toggle('open', this.open);
    if (this.open && !this.sessionId) this.start();
  },

  async start() {
    this.sessionId = 'cs_' + Date.now() + '_' + Math.random().toString(36).substr(2,6);
    this.addMessage('bot', 'Hello! 👋 I\'m <strong>Aria</strong>, your Royale Vista concierge. How can I help you today?', [
      '🛏 View Rooms', '💰 Check Prices', '📅 Make Booking', '🏊 Hotel Facilities', '📞 Contact Us'
    ]);
  },

  addMessage(role, text, quickReplies = []) {
    const container = document.getElementById('chatMessages');
    if (!container) return;

    // Remove typing indicator
    document.getElementById('chatTyping')?.remove();

    const div = document.createElement('div');
    div.className = `chat-msg ${role}`;
    div.innerHTML = `
      ${role === 'bot' ? `<div class="chat-msg-av">🏨</div>` : `<div class="chat-msg-av">👤</div>`}
      <div class="chat-bubble-msg">${text}</div>
    `;
    container.appendChild(div);

    if (quickReplies.length > 0) {
      const qr = document.getElementById('chatQuickReplies');
      if (qr) {
        qr.innerHTML = quickReplies.map(r =>
          `<button class="chat-quick-btn" onclick="Chatbot.sendQuick('${r.replace(/'/g,"\\\'")}')">${r}</button>`
        ).join('');
        qr.style.display = 'flex';
      }
    } else {
      const qr = document.getElementById('chatQuickReplies');
      if (qr) qr.innerHTML = '';
    }

    container.scrollTop = container.scrollHeight;
  },

  showTyping() {
    const container = document.getElementById('chatMessages');
    if (!container) return;
    const div = document.createElement('div');
    div.id = 'chatTyping';
    div.className = 'chat-msg bot';
    div.innerHTML = `<div class="chat-msg-av">🏨</div><div class="chat-bubble-msg chat-typing"><span></span><span></span><span></span></div>`;
    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
  },

  sendQuick(text) { this.send(text); },

  async send(text) {
    text = text.trim();
    if (!text) return;

    const input = document.getElementById('chatInput');
    if (input) input.value = '';

    this.addMessage('user', text);
    this.showTyping();

    try {
      const fd = new FormData();
      fd.append('message', text);
      fd.append('session_id', this.sessionId || '');
      const res  = await fetch(window.RV?.base + '/api/chat.php', { method: 'POST', body: fd });
      const data = await res.json();
      this.addMessage('bot', data.reply || 'I\'m here to help!', data.quick_replies || []);
    } catch(e) {
      this.addMessage('bot', 'Sorry, I\'m having a moment. Please try again or <a href="/contact.php" style="color:var(--gold)">contact us</a> directly!');
    }
  }
};
window.Chatbot = Chatbot;

// ── Review Stars ─────────────────────────────────────────────
const ratingLabels = ['', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];
document.querySelectorAll('.star-input').forEach(wrap => {
  const label = wrap.parentElement.querySelector('.star-hover-label');
  wrap.querySelectorAll('label').forEach(lbl => {
    lbl.addEventListener('mouseenter', () => {
      if (label) { label.textContent = ratingLabels[parseInt(lbl.getAttribute('for').replace('star',''))] || ''; label.style.color = 'var(--gold)'; }
    });
    lbl.addEventListener('mouseleave', () => {
      if (label) { label.textContent = ''; }
    });
  });
});

// ── Lightbox ─────────────────────────────────────────────────
let lightboxImages = [];
let lightboxIndex  = 0;

window.openLightbox = function(idx, images) {
  lightboxImages = images || [];
  lightboxIndex  = idx || 0;
  const lb = document.getElementById('lightbox');
  if (!lb) return;
  lb.classList.add('open');
  updateLightbox();
};

function updateLightbox() {
  const img = document.getElementById('lightboxImg');
  const cap = document.getElementById('lightboxCaption');
  if (img && lightboxImages[lightboxIndex]) {
    img.src = lightboxImages[lightboxIndex].url || lightboxImages[lightboxIndex];
    if (cap) cap.textContent = lightboxImages[lightboxIndex].title || '';
  }
}

window.lightboxNav = function(dir) {
  lightboxIndex = (lightboxIndex + dir + lightboxImages.length) % lightboxImages.length;
  updateLightbox();
};

document.addEventListener('keydown', e => {
  const lb = document.getElementById('lightbox');
  if (!lb?.classList.contains('open')) return;
  if (e.key === 'ArrowRight') lightboxNav(1);
  if (e.key === 'ArrowLeft')  lightboxNav(-1);
  if (e.key === 'Escape') lb.classList.remove('open');
});

// ── Page load animations ─────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  // Stagger hero elements
  const heroEls = document.querySelectorAll('.hero-stagger');
  heroEls.forEach((el, i) => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(30px)';
    setTimeout(() => {
      el.style.transition = 'opacity .7s ease, transform .7s ease';
      el.style.opacity = '1';
      el.style.transform = 'translateY(0)';
    }, 200 + i * 150);
  });

  // Init particles
  if (document.getElementById('particles-canvas')) {
    new ParticleSystem('particles-canvas');
  }

  // Init cursor glow
  const glowEl = document.createElement('div');
  glowEl.className = 'cursor-glow';
  document.body.appendChild(glowEl);
});

// ── Smooth number animation for stats ───────────────────────
window.animateValue = function(el, to, duration = 2000) {
  let start = 0;
  const step = timestamp => {
    if (!start) start = timestamp;
    const progress = Math.min((timestamp - start) / duration, 1);
    const eased = 1 - Math.pow(1 - progress, 4);
    el.textContent = Math.floor(eased * to).toLocaleString();
    if (progress < 1) requestAnimationFrame(step);
  };
  requestAnimationFrame(step);
};
