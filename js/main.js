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
});

