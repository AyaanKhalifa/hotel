/**
 * SHAMA — Royale Vista AI Concierge
 * Intelligent hotel chatbot with context-aware responses
 */
(function () {
  'use strict';

  const BASE = window.BASE || '';

  /* ── Knowledge Base ─────────────────────────────────────── */
  const QUICK_REPLIES = [
    { label: '🏨 How many rooms?', msg: 'How many rooms do you have available?' },
    { label: '💰 Room Prices', msg: 'What are your room prices?' },
    { label: '📅 Check Availability', msg: 'I want to reserve a room' },
    { label: '⭐ Loyalty Points', msg: 'Tell me about loyalty rewards' },
    { label: '🎁 Gift Cards', msg: 'Do you sell gift cards?' }
  ];

  const GREETINGS = ['Hi there! 👋', 'Hello! 😊', 'Good day! ✨', 'Welcome! 🌟'];

  /* ── State ─────────────────────────────────────────────── */
  let isOpen = false, msgCount = 0, fallbackIdx = 0, askingName = false;

  /* ── DOM Helpers ────────────────────────────────────────── */
  const $id = id => document.getElementById(id);

  function resolveAvatarSrc(raw) {
    const img = (raw || '').trim();
    if (!img) return '';
    if (/^https?:\/\//i.test(img)) return img;
    if (img.startsWith('/')) return `${BASE}${img}`;
    if (img.includes('/')) return `${BASE}/${img.replace(/^\/+/, '')}`;
    return `${BASE}/uploads/avatars/${img}`;
  }

  function getReply(text) {
    // Deprecated for the new async api fetch server-side logic
    return "";
  }

  function addMsg(html, isUser = false) {
    const msgs = $id('habibiMsgs');
    const wrap = document.createElement('div');
    wrap.style.cssText = `display:flex;align-items:flex-end;gap:8px;margin-bottom:12px;${isUser ? 'flex-direction:row-reverse' : ''}`;

    if (!isUser) {
      const av = document.createElement('div');
      av.style.cssText = 'width:30px;height:30px;border-radius:50%;background:var(--gold);display:flex;align-items:center;justify-content:center;font-size:14px;color:#000;flex-shrink:0;box-shadow:0 3px 10px rgba(192,155,91,.2);border:1.5px solid rgba(255,255,255,.1)';
      av.innerHTML = '<i class="fas fa-concierge-bell"></i>';
      wrap.appendChild(av);
    } else {
      const av = document.createElement('div');
      av.style.cssText = 'width:30px;height:30px;border-radius:50%;background:var(--gold);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#000;flex-shrink:0;overflow:hidden;box-shadow:0 3px 10px rgba(0,0,0,.15)';
      if (window.HabibiUser && window.HabibiUser.img) {
         const src = resolveAvatarSrc(window.HabibiUser.img);
         av.innerHTML = `<img src="${src}" style="width:100%;height:100%;object-fit:cover;border-radius:50%" onerror="this.style.display='none'">`;
      } else {
         const nm = (window.HabibiUser ? window.HabibiUser.name : (localStorage.getItem('habibi_guest_name') || 'G'));
         av.textContent = nm.charAt(0).toUpperCase();
      }
      wrap.appendChild(av);
    }

    const bubble = document.createElement('div');
    bubble.style.cssText = `max-width:80%;padding:10px 14px;border-radius:${isUser ? '18px 18px 4px 18px' : '18px 18px 18px 4px'};font-size:13.5px;line-height:1.6;${isUser ? 'background:var(--habibi-gold);color:#000' : 'background:var(--habibi-bubble);color:var(--habibi-txt)'}`;
    bubble.innerHTML = html;
    wrap.appendChild(bubble);
    msgs.appendChild(wrap);
    msgs.scrollTop = msgs.scrollHeight;
    msgCount++;
  }

  function showTyping() {
    const msgs = $id('habibiMsgs');
    const el = document.createElement('div');
    el.id = 'habibiTyping';
    el.style.cssText = 'display:flex;align-items:flex-end;gap:8px;margin-bottom:12px';
    el.innerHTML = `
      <div style="width:30px;height:30px;border-radius:50%;background:var(--gold);display:flex;align-items:center;justify-content:center;font-size:14px;color:#000;flex-shrink:0;box-shadow:0 3px 10px rgba(192,155,91,.2);border:1.5px solid rgba(255,255,255,.1)">
        <i class="fas fa-concierge-bell"></i>
      </div>
      <div style="background:var(--habibi-bubble);border-radius:18px 18px 18px 4px;padding:12px 18px;display:flex;gap:5px;align-items:center">
        <span class="dot" style="width:6px;height:6px;border-radius:50%;background:var(--gold);animation:dotBounce .8s ease-in-out infinite"></span>
        <span class="dot" style="width:6px;height:6px;border-radius:50%;background:var(--gold);animation:dotBounce .8s ease-in-out .15s infinite"></span>
        <span class="dot" style="width:6px;height:6px;border-radius:50%;background:var(--gold);animation:dotBounce .8s ease-in-out .3s infinite"></span>
      </div>`;
    msgs.appendChild(el);
    msgs.scrollTop = msgs.scrollHeight;
    return el;
  }

  function renderQuickReplies() {
    const qr = $id('habibiQR');
    if (!qr) return;
    qr.innerHTML = '';
    QUICK_REPLIES.forEach(q => {
      const btn = document.createElement('button');
      btn.className = 'habibi-qr-btn';
      btn.textContent = q.label;
      btn.onclick = () => { Habibi.send(q.msg); };
      qr.appendChild(btn);
    });
  }

  function welcome() {
    const greeting = GREETINGS[Math.floor(Math.random() * GREETINGS.length)];
    
    // 1. Logged in user
    if (window.HabibiUser && window.HabibiUser.id) {
       const n = window.HabibiUser.name.split(' ')[0];
       addMsg(`${greeting} <b>${n}</b>! I'm <strong>Habibi</strong>, your AI concierge at Royale Vista! 🌟<br><br>I can help you with <b>rooms, live availability, dining, spa, check-in times</b> and much more. What can I do for you today?`);
       renderQuickReplies();
       return;
    }

    // 2. Returning guest (localStorage)
    const savedName = localStorage.getItem('habibi_guest_name');
    if (savedName) {
       addMsg(`${greeting} <b>${savedName}</b>! Welcome back to Royale Vista! ✨ I'm <strong>Habibi</strong>. How can I assist you today?`);
       renderQuickReplies();
       return;
    }

    // 3. New guest - ask for name
    askingName = true;
    addMsg(`${greeting} Welcome to Royale Vista! ✨ I'm <strong>Habibi</strong>, your personal AI concierge.<br><br>To serve you better in our first luxury experience, may I please have your first name?`);
  }

  function renderRichMeta(meta) {
    if (!meta || !Array.isArray(meta)) return '';
    let html = '';
    meta.forEach(item => {
      if (item.type === 'room_card') {
        html += `
          <div class="habibi-card">
            <img src="${item.img}" class="habibi-card-img" alt="${item.name}">
            <div class="habibi-card-body">
              <div class="habibi-card-title">${item.name}</div>
              <div class="habibi-card-price">${item.price}<span>/night</span></div>
              <div class="habibi-card-desc">${item.desc}</div>
              <a href="${BASE}/rooms.php?type=${item.id}" class="habibi-card-btn">View Details</a>
            </div>
          </div>
        `;
      } else if (item.type === 'navigate') {
        const pageName = item.url.replace('.php', '').replace('-', ' ').replace(/\b\w/g, l => l.toUpperCase());
        const fullUrl = item.url.startsWith('http') ? item.url : `${BASE}/${item.url.replace(/^\//,'')}`;
        html += `
          <div class="habibi-card nav-card" style="border-left:4px solid var(--habibi-gold)">
            <div class="habibi-card-body" style="display:flex;align-items:center;justify-content:space-between;gap:10px">
              <div>
                <div style="font-size:10px;color:var(--gold);text-transform:uppercase;letter-spacing:1px">Quick Link</div>
                <div style="font-size:13.5px;font-weight:600">${pageName}</div>
              </div>
              <a href="${fullUrl}" class="habibi-card-btn" style="width:auto;padding:8px 14px">Go Now</a>
            </div>
          </div>
        `;
      }
    });
    return html;
  }

  /* ── CSS Injection ──────────────────────────────────────── */
  const style = document.createElement('style');
  style.textContent = `
:root {
  --habibi-gold: #c09b5b;
  --habibi-bubble: rgba(255,255,255,.07);
  --habibi-txt: rgba(255,255,255,.9);
}
[data-theme="light"] { --habibi-bubble: rgba(0,0,0,.06); --habibi-txt: #1a1612; }
.habibi-win {
  position:fixed;bottom:90px;right:24px;width:360px;max-width:calc(100vw - 32px);
  height:520px;background:linear-gradient(160deg,#1c1813,#2a1f14);
  border:1px solid rgba(192,155,91,.3);border-radius:20px;
  box-shadow:0 20px 80px rgba(0,0,0,.5);z-index:9990;
  display:flex;flex-direction:column;opacity:0;pointer-events:none;
  transform:translateY(20px) scale(.95);transition:all .3s cubic-bezier(.34,1.3,.64,1);
  overflow:hidden;
}
.habibi-win.open{opacity:1;pointer-events:all;transform:none}
.habibi-header{background:linear-gradient(135deg,#2d2218,#1c1813);padding:14px 18px;display:flex;align-items:center;gap:12px;border-bottom:1px solid rgba(192,155,91,.2);flex-shrink:0}
.habibi-header-av{width:42px;height:42px;border-radius:50%;border:2px solid var(--habibi-gold);overflow:hidden;flex-shrink:0}
.habibi-header-av img{width:100%;height:100%;object-fit:cover}
.habibi-header-name{font-family:var(--cinzel,serif);font-size:14px;color:#fff;letter-spacing:1px}
.habibi-header-title{font-size:10px;color:rgba(255,255,255,.5);margin-top:2px}
.habibi-status{display:flex;align-items:center;gap:5px;font-size:10px;color:rgba(255,255,255,.4);margin-top:3px}
.habibi-status-dot{width:6px;height:6px;border-radius:50%;background:#22c55e;flex-shrink:0;animation:pulse 2s ease-in-out infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}
.habibi-close-btn{margin-left:auto;background:rgba(255,255,255,.08);border:none;color:rgba(255,255,255,.6);width:30px;height:30px;border-radius:50%;font-size:18px;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .2s}
.habibi-close-btn:hover{background:rgba(220,50,50,.25);color:#fff}
.habibi-msgs{flex:1;overflow-y:auto;padding:16px;scroll-behavior:smooth}
.habibi-msgs img{max-width:100%;height:auto;display:block;border-radius:10px;object-fit:cover}
.habibi-msgs::-webkit-scrollbar{width:4px}
.habibi-msgs::-webkit-scrollbar-thumb{background:rgba(192,155,91,.3);border-radius:2px}
.habibi-qr{display:flex;flex-wrap:wrap;gap:6px;padding:8px 14px;border-top:1px solid rgba(255,255,255,.05);flex-shrink:0;overflow-x:auto}
.habibi-qr-btn{padding:5px 12px;background:rgba(192,155,91,.15);border:1px solid rgba(192,155,91,.3);border-radius:20px;color:var(--habibi-gold);font-size:11px;cursor:pointer;white-space:nowrap;transition:all .2s;font-family:var(--sans,sans-serif)}
.habibi-qr-btn:hover{background:rgba(192,155,91,.3)}
.habibi-input-area{display:flex;gap:8px;padding:12px 14px;border-top:1px solid rgba(255,255,255,.07);background:rgba(0,0,0,.2);flex-shrink:0}
.habibi-input{flex:1;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);border-radius:24px;padding:9px 16px;color:#fff;font-size:13px;font-family:var(--sans,sans-serif);outline:none;transition:border-color .2s}
.habibi-input:focus{border-color:rgba(192,155,91,.5)}
.habibi-input::placeholder{color:rgba(255,255,255,.3)}
.habibi-send{width:38px;height:38px;border-radius:50%;background:var(--habibi-gold);border:none;color:#000;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .2s}
.habibi-send:hover{background:#a07a3a;transform:scale(1.05)}
.habibi-bubble-btn{position:fixed;bottom:24px;right:24px;width:58px;height:58px;border-radius:50%;background:none;border:none;cursor:pointer;z-index:9991;transition:all .3s;display:flex;align-items:center;justify-content:center;padding:0}.habibi-bubble-inner{width:58px;height:58px;border-radius:50%;background:linear-gradient(135deg,#c09b5b,#8a6a3a);display:flex;align-items:center;justify-content:center;box-shadow:0 4px 28px rgba(192,155,91,.55);position:relative;transition:all .3s}.habibi-bubble-btn:hover .habibi-bubble-inner{transform:scale(1.08);box-shadow:0 8px 36px rgba(192,155,91,.75)}.habibi-bubble-icon{font-size:22px;color:#000;transition:transform .3s}.habibi-bubble-btn:hover .habibi-bubble-icon{transform:rotate(-15deg) scale(1.1)}.habibi-bubble-pulse{position:absolute;inset:-4px;border-radius:50%;border:2px solid rgba(192,155,91,.4);animation:habPulse 2.5s ease-out infinite}.habibi-header-av-inner{width:100%;height:100%;background:linear-gradient(135deg,#c09b5b,#a07a3a);display:flex;align-items:center;justify-content:center}@keyframes habPulse{0%{transform:scale(1);opacity:.7}100%{transform:scale(1.5);opacity:0}}
.habibi-bubble-btn:hover{transform:scale(1.1);box-shadow:0 8px 32px rgba(192,155,91,.7)}
.habibi-deep-link{color:var(--habibi-gold)!important;text-decoration:underline}
.habibi-action-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:10px}
.habibi-action-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:7px 10px;border:1px solid rgba(192,155,91,.4);border-radius:18px;color:var(--habibi-gold);text-decoration:none;font-size:12px}
@keyframes dotBounce{0%,100%{transform:translateY(0)}50%{transform:translateY(-5px)}}

/* Rich Cards */
.habibi-card { background:rgba(255,255,255,.05); border:1px solid rgba(192,155,91,.2); border-radius:12px; margin-top:12px; overflow:hidden; transition:all .3s; }
[data-theme="light"] .habibi-card { background:rgba(0,0,0,.03); }
.habibi-card-img { width:100%; height:120px; object-fit:cover; border-bottom:1px solid rgba(192,155,91,.1); }
.habibi-card-body { padding:12px; }
.habibi-card-title { font-family:var(--serif); font-size:15px; color:var(--habibi-gold); margin-bottom:4px; }
.habibi-card-price { font-size:14px; font-weight:700; color:#fff; }
[data-theme="light"] .habibi-card-price { color:#000; }
.habibi-card-price span { font-size:10px; color:var(--muted); font-weight:400; margin-left:4px; }
.habibi-card-desc { font-size:11px; color:var(--muted); line-height:1.4; margin:8px 0; }
.habibi-card-btn { display:block; text-align:center; padding:8px; background:var(--habibi-gold); color:#000; text-decoration:none; border-radius:6px; font-size:11px; font-weight:700; transition:opacity .2s; }
.habibi-card-btn:hover { opacity:.9; }
`;
  document.head.appendChild(style);

  /* ── Public API ─────────────────────────────────────────── */
  window.Habibi = {
    toggle() {
      const win = $id('habibiWin');
      if (!win) return;
      isOpen = !isOpen;
      win.classList.toggle('open', isOpen);
      if (isOpen && msgCount === 0) welcome();
      if (isOpen) {
        const input = $id('habibiInput');
        if (input) {
          // Use requestAnimationFrame to ensure CSS transition completes or timeout
          setTimeout(() => input.focus(), 350);
        }
      }
    },
    async send(text) {
      text = (text || '').trim();
      if (!text) return;
      const input = $id('habibiInput');
      if (input) input.value = '';
      addMsg(text, true);

      // Clear quick replies after first interaction
      const qr = $id('habibiQR');
      if (qr) qr.innerHTML = '';

      // Typing indicator
      const typingEl = showTyping();

      // If asking name logic
      if (askingName) {
         setTimeout(() => {
           typingEl.remove();
           askingName = false;
           // Grab the first word as the name and capitalize it
           let n = text.split(' ')[0].replace(/[^a-zA-Z]/g, '');
           if (!n) n = 'Guest';
           n = n.charAt(0).toUpperCase() + n.slice(1).toLowerCase();
           
           localStorage.setItem('habibi_guest_name', n);
           addMsg(`It is a pleasure to meet you, <b>${n}</b>! 🌟<br><br>I can tap directly into the hotel's database to check live room counts, pricing, loyalty, and gift cards. What can I do for you today?`);
           renderQuickReplies();
         }, 800 + Math.random() * 400);
         return;
      }

      // Local advanced shortcuts for faster concierge actions
      if (/^\/help$/i.test(text)) {
        typingEl.remove();
        addMsg(`Here are quick commands you can use:<br><b>/rooms</b> · <b>/prices</b> · <b>/offers</b> · <b>/invoice</b> · <b>/giftcards</b>`);
        renderQuickReplies();
        return;
      }
      if (/^\/rooms$/i.test(text)) {
        typingEl.remove();
        addMsg(`Live room search is ready.<div class="habibi-action-grid"><a class="habibi-action-btn" href="${BASE}/rooms.php">🏨 View Rooms</a><a class="habibi-action-btn" href="${BASE}/offers.php">🎁 Offers</a></div>`);
        renderQuickReplies();
        return;
      }
      if (/^\/invoice$/i.test(text)) {
        typingEl.remove();
        addMsg(`Open your reservations to view invoice(s).<div class="habibi-action-grid"><a class="habibi-action-btn" href="${BASE}/bookings.php">🧾 My Bookings</a><a class="habibi-action-btn" href="${BASE}/contact.php">☎ Support</a></div>`);
        renderQuickReplies();
        return;
      }

      // Fetch from API
      try {
        const formData = new FormData();
        formData.append('message', text);
        formData.append('session_id', localStorage.getItem('habibi_sid') || '');
        formData.append('page_title', document.title || 'Royale Vista');
        formData.append('page_url', window.location.pathname);
        formData.append('guest_name', localStorage.getItem('habibi_guest_name') || '');
        
        const res = await fetch(`${BASE}/api/habibi-ai.php`, {
          method: 'POST',
          body: formData
        });
        const data = await res.json();
        
        if (data.session_id) localStorage.setItem('habibi_sid', data.session_id);
        
        setTimeout(() => {
          typingEl.remove();
          
          let cleanReply = data.reply.replace(/\[[A-Z_]+:\s*.*?\]/gi, '').trim();
          addMsg(cleanReply);
          
          // Render rich cards if any
          if (data.rich_meta && data.rich_meta.length > 0) {
            const richHtml = renderRichMeta(data.rich_meta);
            const msgs = $id('habibiMsgs');
            // Append as a new bubble for rich content
            const richBubbleWrap = document.createElement('div');
            richBubbleWrap.style.cssText = 'margin-bottom:12px;margin-left:38px;';
            richBubbleWrap.innerHTML = richHtml;
            msgs.appendChild(richBubbleWrap);
            msgs.scrollTop = msgs.scrollHeight;
          }
          
          renderQuickReplies();
        }, 600);
        
      } catch (err) {
        setTimeout(() => {
          typingEl.remove();
          addMsg('Sorry, I am having trouble connecting to the concierge servers right now.');
          renderQuickReplies();
        }, 1200);
      }
    }
  };

  // Legacy support in case any code calls Chatbot.toggle()
  window.Chatbot = window.Habibi;

  // Initialize bubble click
  document.addEventListener('DOMContentLoaded', () => {
    try {
      const hd = document.querySelector('.habibi-header-av-inner');
      if (hd && window.HabibiUser && window.HabibiUser.img) {
        const src = resolveAvatarSrc(window.HabibiUser.img);
        hd.innerHTML = `<img src="${src}" alt="Profile" style="width:100%;height:100%;object-fit:cover;border-radius:50%" onerror="this.style.display='none'">`;
      }
    } catch (e) {}
    const bubble = $id('habibiBubble');
    if (bubble) {
      bubble.onclick = () => Habibi.toggle();
      bubble.title = 'Chat with Habibi — AI Concierge';
    }
    const closeBtn = document.querySelector('.habibi-close-btn');
    if (closeBtn) closeBtn.onclick = () => Habibi.toggle();

    // Enter key in input
    const input = $id('habibiInput');
    if (input) input.addEventListener('keydown', e => {
      if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); Habibi.send(input.value); }
    });
    // Send button
    const sendBtn = document.querySelector('.habibi-send');
    if (sendBtn) sendBtn.onclick = () => Habibi.send($id('habibiInput')?.value);
  });

})();

