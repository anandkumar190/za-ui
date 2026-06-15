document.addEventListener('DOMContentLoaded', () => {
  // === COUNTDOWN TIMER ===
  let totalSeconds = 29 * 60 + 59;
  const countMinEl = document.getElementById('countMin');
  const countSecEl = document.getElementById('countSec');

  function updateCountdown() {
    if (!countMinEl || !countSecEl) return;
    const m = Math.floor(totalSeconds / 60);
    const s = totalSeconds % 60;
    countMinEl.textContent = String(m).padStart(2, '0');
    countSecEl.textContent = String(s).padStart(2, '0');
    if (totalSeconds > 0) {
      totalSeconds--;
    } else {
      totalSeconds = 29 * 60 + 59; // reset
    }
  }
  updateCountdown();
  setInterval(updateCountdown, 1000);

  // === SCROLL ANIMATIONS ===
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        e.target.classList.add('visible');
        // stagger children
        const children = e.target.querySelectorAll('.fade-up');
        children.forEach((c, i) => {
          setTimeout(() => c.classList.add('visible'), i * 100);
        });
      }
    });
  }, { threshold: 0.1 });

  document.querySelectorAll('.fade-up').forEach(el => observer.observe(el));

  // === HIDE STICKY BAR WHEN FORM IN VIEW ===
  const formSection = document.getElementById('order-form');
  const stickyBar = document.getElementById('stickyBar');
  if (formSection && stickyBar) {
    const formObserver = new IntersectionObserver((entries) => {
      entries.forEach(e => {
        stickyBar.style.display = e.isIntersecting ? 'none' : 'flex';
      });
    }, { threshold: 0.1 });
    formObserver.observe(formSection);
  }

  // === FAQ ACCORDION ===
  document.querySelectorAll('.faq-question').forEach(button => {
    button.addEventListener('click', () => {
      const faqItem = button.parentElement;
      const isActive = faqItem.classList.contains('active');
      
      // Close all other FAQ items
      document.querySelectorAll('.faq-item').forEach(item => {
        item.classList.remove('active');
        const ans = item.querySelector('.faq-answer');
        if (ans) ans.style.maxHeight = null;
      });
      
      // Toggle current FAQ item
      if (!isActive) {
        faqItem.classList.add('active');
        const answer = faqItem.querySelector('.faq-answer');
        if (answer) {
          answer.style.maxHeight = answer.scrollHeight + 'px';
        }
      }
    });
  });

  // === INTERACTIVE HERB CARDS ===
  document.querySelectorAll('.herb-card').forEach(card => {
    // Append click indicator dynamically if not present
    const badge = document.createElement('div');
    badge.className = 'herb-detail-badge';
    badge.innerText = '💡 क्लिक करें विवरण के लिए';
    card.appendChild(badge);

    card.addEventListener('click', () => {
      // Toggle active state
      const isActive = card.classList.contains('active');
      document.querySelectorAll('.herb-card').forEach(c => c.classList.remove('active'));
      if (!isActive) {
        card.classList.add('active');
      }
    });
  });

  // === FORM SUBMIT & DATABASE STORAGE ===
  const orderForm = document.getElementById('orderForm');
  if (orderForm) {
    const nameInput = document.getElementById('orderName');
    const telInput = document.getElementById('orderPhone');
    const addressInput = document.getElementById('orderAddress');
    const pinInput = document.getElementById('orderPin');
    const errorMsg = document.getElementById('errorMsg');
    const successMsg = document.getElementById('successMsg');

    // Helper functions for messages
    function showError(message) {
      if (errorMsg) {
        errorMsg.innerHTML = message;
        errorMsg.style.display = 'block';
      }
      if (successMsg) {
        successMsg.style.display = 'none';
      }
    }

    function hideError() {
      if (errorMsg) {
        errorMsg.style.display = 'none';
        errorMsg.innerHTML = '';
      }
    }

    // Clear invalid state on typing
    [nameInput, telInput, addressInput, pinInput].forEach(input => {
      if (input) {
        input.addEventListener('input', () => {
          input.classList.remove('invalid');
          const anyInvalid = orderForm.querySelectorAll('input.invalid').length > 0;
          if (!anyInvalid) {
            hideError();
          }
        });
      }
    });

    orderForm.addEventListener('submit', function(e) {
      e.preventDefault();
      
      hideError();
      let hasError = false;

      // Validate inputs
      const name = nameInput ? nameInput.value.trim() : '';
      const phone = telInput ? telInput.value.trim() : '';
      const address = addressInput ? addressInput.value.trim() : '';
      const pin = pinInput ? pinInput.value.trim() : '';

      // Highlight empty inputs
      if (!name) {
        if (nameInput) nameInput.classList.add('invalid');
        hasError = true;
      }
      if (!phone) {
        if (telInput) telInput.classList.add('invalid');
        hasError = true;
      }
      if (!address) {
        if (addressInput) addressInput.classList.add('invalid');
        hasError = true;
      }
      if (!pin) {
        if (pinInput) pinInput.classList.add('invalid');
        hasError = true;
      }

      if (hasError) {
        showError('कृपया सभी आवश्यक फ़ील्ड भरें।');
        return;
      }
      
      // Phone format validation (6-9 followed by 9 digits)
      if (!/^[6-9]\d{9}$/.test(phone)) {
        if (telInput) telInput.classList.add('invalid');
        showError('कृपया एक वैध 10 अंकों का मोबाइल नंबर दर्ज करें (6-9 से शुरू होने वाला)।');
        return;
      }

      // PIN format validation (6 digits)
      if (!/^\d{6}$/.test(pin)) {
        if (pinInput) pinInput.classList.add('invalid');
        showError('कृपया एक वैध 6 अंकों का PIN कोड दर्ज करें।');
        return;
      }

      const btn = this.querySelector('.form-submit');
      if (btn) {
        btn.textContent = '⏳ बुकिंग हो रही है...';
        btn.disabled = true;
      }

      // AJAX Submit to submit.php
      const params = new URLSearchParams();
      params.append('name', name);
      params.append('phone', phone);
      params.append('address', address);
      params.append('pin', pin);

      fetch('submit.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: params
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          if (btn) btn.style.display = 'none';
          if (successMsg) {
            successMsg.innerHTML = data.message;
            successMsg.style.display = 'block';
          }
          hideError();
          this.reset();
        } else {
          showError(data.message || 'त्रुटि: ऑर्डर बुक करने में समस्या आई।');
          if (btn) {
            btn.textContent = '✅ ऑर्डर बुक करें — ₹1,499';
            btn.disabled = false;
          }
        }
      })
      .catch(error => {
        console.error('Submission error:', error);
        showError('सर्वर त्रुटि: कृपया इंटरनेट कनेक्शन की जांच करें और पुनः प्रयास करें।');
        if (btn) {
          btn.textContent = '✅ ऑर्डर बुक करें — ₹1,499';
          btn.disabled = false;
        }
      });
    });
  }

  // === ADMIN REDIRECT ===
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('admin') === 'true') {
    window.location.href = 'admin.php';
    return;
  }

  // === AGE VERIFICATION POPUP ===
  const confirmPopup = document.getElementById('confirmPopup');
  const confirmYesBtn = document.getElementById('confirmYesBtn');
  const confirmNoBtn = document.getElementById('confirmNoBtn');

  if (confirmPopup) {
    const isConfirmed = localStorage.getItem('ojas_age_confirmed');
    if (isConfirmed === 'true') {
      confirmPopup.classList.add('hidden');
    } else {
      document.body.classList.add('modal-open');
    }

    confirmYesBtn.addEventListener('click', () => {
      localStorage.setItem('ojas_age_confirmed', 'true');
      confirmPopup.classList.add('hidden');
      document.body.classList.remove('modal-open');
    });

    confirmNoBtn.addEventListener('click', () => {
      confirmPopup.innerHTML = `
        <div class="confirm-card" style="max-width: 400px;">
          <div class="confirm-logo">🛑</div>
          <h2 style="color: #c93b2b; font-family: 'Playfair Display', serif; margin-bottom: 16px;">प्रवेश वर्जित (Access Denied)</h2>
          <p style="color: rgba(255, 255, 255, 0.8); font-size: 14px; margin-bottom: 24px; line-height: 1.6;">क्षमा करें, आप इस वेबसाइट पर प्रवेश नहीं कर सकते क्योंकि आपकी आयु 18 वर्ष से कम है।</p>
          <a href="https://www.google.com" class="confirm-btn yes" style="text-decoration:none; display:inline-block; text-align:center; padding: 14px 28px; width: 100%;">Google पर जाएं</a>
        </div>
      `;
    });
  }
});
