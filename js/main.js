/* ==========================================================================
   Imoratok Safaris - Core JavaScript
   Uganda, East Africa
   ========================================================================== */

document.addEventListener('DOMContentLoaded', () => {
  // 1. Header scroll effect
  const header = document.querySelector('.site-header');
  const handleScroll = () => {
    if (window.scrollY > 40) {
      header.classList.add('scrolled');
    } else {
      header.classList.remove('scrolled');
    }
  };
  window.addEventListener('scroll', handleScroll, { passive: true });
  handleScroll();

  // 2. Mobile navigation drawer toggle
  const mobileToggle = document.querySelector('.mobile-toggle');
  const navWrapper = document.querySelector('.nav-wrapper');
  const navLinks = document.querySelectorAll('.nav-link, .nav-cta');

  if (mobileToggle && navWrapper) {
    const toggleMenu = () => {
      const isOpen = navWrapper.classList.toggle('open');
      mobileToggle.classList.toggle('active', isOpen);
      mobileToggle.setAttribute('aria-expanded', isOpen);
      document.body.style.overflow = isOpen ? 'hidden' : '';
    };

    mobileToggle.addEventListener('click', toggleMenu);

    // Close mobile nav when clicking any link
    navLinks.forEach(link => {
      link.addEventListener('click', () => {
        if (navWrapper.classList.contains('open')) {
          toggleMenu();
        }
      });
    });

    // Close when clicking outside
    document.addEventListener('click', (e) => {
      if (navWrapper.classList.contains('open') &&
        !navWrapper.contains(e.target) &&
        !mobileToggle.contains(e.target)) {
        toggleMenu();
      }
    });

    // Close on escape key
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && navWrapper.classList.contains('open')) {
        toggleMenu();
      }
    });
  }

  // 3. Active Nav Link on Scroll (IntersectionObserver)
  const sections = document.querySelectorAll('section[id], footer[id]');
  const navItems = document.querySelectorAll('.nav-links .nav-link, .quick-nav-links a');

  const observerOptions = {
    root: null,
    rootMargin: '-20% 0px -60% 0px',
    threshold: 0
  };

  const sectionObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const id = entry.target.getAttribute('id');
        navItems.forEach(item => {
          if (item.getAttribute('href') === `#${id}`) {
            item.classList.add('active');
          } else {
            item.classList.remove('active');
          }
        });
      }
    });
  }, observerOptions);

  sections.forEach(sec => sectionObserver.observe(sec));

  // 4. Automatic Copyright Year in Footer
  const yearElement = document.getElementById('currentYear');
  if (yearElement) {
    yearElement.textContent = new Date().getFullYear();
  }

  // 4b. Duplicate Gallery Track Items for Seamless Infinite Marquee
  const galleryTrack = document.getElementById('galleryTrack');
  if (galleryTrack) {
    const items = Array.from(galleryTrack.children);
    items.forEach(item => {
      const clone = item.cloneNode(true);
      clone.setAttribute('aria-hidden', 'true');
      galleryTrack.appendChild(clone);
    });
  }
});


  // 5. Safari Booking Inquiry Form Handler
  const inquiryForm = document.getElementById('safariInquiryForm');
  if (inquiryForm) {
    inquiryForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const name = document.getElementById('inquiryName')?.value || '';
      const email = document.getElementById('inquiryEmail')?.value || '';
      const phone = document.getElementById('inquiryPhone')?.value || '';
      const onWhatsApp = document.getElementById('inquiryOnWhatsApp')?.checked;
      const contactLegacy = document.getElementById('inquiryContact')?.value || '';
      const safari = document.getElementById('inquirySafari')?.value || '';
      const travelers = document.getElementById('inquiryTravelers')?.value || '';
      const message = document.getElementById('inquiryMessage')?.value || '';

      const contactStr = (email || phone) 
        ? `Email: ${email} | Phone: ${phone}${onWhatsApp ? ' (WhatsApp)' : ''}`
        : `Contact: ${contactLegacy}`;

      const text = `Hello Imoratok Safaris! My name is ${name}. I am interested in: ${safari}. ${contactStr}. Travelers/Dates: ${travelers}. Dream Safari details: ${message}`;
      
      // Track conversion event if gtag exists
      if (typeof gtag === 'function') {
        gtag('event', 'generate_lead', {
          event_category: 'Inquiry',
          event_label: safari
        });
      }

      // Open WhatsApp direct with pre-filled message
      const encoded = encodeURIComponent(text);
      const waUrl = `https://wa.me/256707407660?text=${encoded}`;
      window.open(waUrl, '_blank');
      
      const successNotice = document.getElementById('formSuccessMessage');
      if (successNotice) {
        successNotice.style.display = 'block';
        inquiryForm.reset();
      }
    });
  }

  // 6. Analytics Event Tracking for Click-to-call & WhatsApp
  document.querySelectorAll('a[href^="tel:"], a[href*="wa.me"]').forEach(link => {
    link.addEventListener('click', () => {
      if (typeof gtag === 'function') {
        const isWa = link.href.includes('wa.me');
        gtag('event', isWa ? 'whatsapp_click' : 'phone_call_click', {
          event_category: 'Contact',
          event_label: link.href
        });
      }
    });
  });
