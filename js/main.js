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


  // 5. Safari Booking Inquiry Form Handler (Web3Forms AJAX Submission)
  const inquiryForm = document.getElementById('safariInquiryForm');
  if (inquiryForm) {
    inquiryForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      const submitBtn = document.getElementById('inquirySubmitBtn');
      const successNotice = document.getElementById('formSuccessMessage');
      const errorNotice = document.getElementById('formErrorMessage');

      if (successNotice) successNotice.style.display = 'none';
      if (errorNotice) errorNotice.style.display = 'none';

      const originalBtnText = submitBtn ? submitBtn.textContent : 'Send Inquiry';
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Sending Inquiry...';
        submitBtn.style.opacity = '0.75';
      }

      const formData = new FormData(inquiryForm);

      try {
        const response = await fetch('https://api.web3forms.com/submit', {
          method: 'POST',
          body: formData,
          headers: {
            'Accept': 'application/json'
          }
        });

        const result = await response.json();

        if (response.status === 200 && result.success) {
          if (successNotice) {
            successNotice.style.display = 'block';
          }
          inquiryForm.reset();

          // Track conversion event if analytics gtag exists
          if (typeof gtag === 'function') {
            gtag('event', 'generate_lead', {
              event_category: 'Inquiry',
              event_label: 'Web3Forms Lead'
            });
          }
        } else {
          if (errorNotice) {
            errorNotice.style.display = 'block';
          }
        }
      } catch (err) {
        if (errorNotice) {
          errorNotice.style.display = 'block';
        }
      } finally {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.textContent = originalBtnText;
          submitBtn.style.opacity = '1';
        }
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

  // 7. Interactive Lightbox Modal (Carousel & Full Gallery Grid)
  const setupGalleryLightbox = () => {
    const galleryTrack = document.getElementById('galleryTrack');
    const fullGallery = document.querySelector('.full-gallery-masonry');

    // Only run if either gallery element is present on this page
    if (!galleryTrack && !fullGallery) return;

    // Create Lightbox DOM structure if not already present
    let lightbox = document.getElementById('imageLightboxModal');
    if (!lightbox) {
      lightbox = document.createElement('div');
      lightbox.id = 'imageLightboxModal';
      lightbox.className = 'lightbox-modal';
      lightbox.setAttribute('role', 'dialog');
      lightbox.setAttribute('aria-modal', 'true');
      lightbox.setAttribute('aria-label', 'Image preview');
      lightbox.innerHTML = `
        <div class="lightbox-overlay"></div>
        <button class="lightbox-close" aria-label="Close image preview">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
        <div class="lightbox-content">
          <button class="lightbox-nav lightbox-prev" aria-label="Previous image">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
          </button>
          <div class="lightbox-image-wrap">
            <img class="lightbox-img" src="" alt="">
            <div class="lightbox-caption"></div>
          </div>
          <button class="lightbox-nav lightbox-next" aria-label="Next image">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
          </button>
        </div>
      `;
      document.body.appendChild(lightbox);
    }

    const overlay = lightbox.querySelector('.lightbox-overlay');
    const closeBtn = lightbox.querySelector('.lightbox-close');
    const prevBtn = lightbox.querySelector('.lightbox-prev');
    const nextBtn = lightbox.querySelector('.lightbox-next');
    const imgEl = lightbox.querySelector('.lightbox-img');
    const captionEl = lightbox.querySelector('.lightbox-caption');

    let activeImageList = [];
    let currentIndex = 0;
    let trackWasPaused = false;

    const updateLightboxImage = () => {
      if (!activeImageList.length) return;
      const target = activeImageList[currentIndex];
      imgEl.src = target.src;
      imgEl.alt = target.alt || 'Safari gallery photo';
      captionEl.textContent = target.alt || '';

      if (activeImageList.length > 1) {
        prevBtn.style.display = 'flex';
        nextBtn.style.display = 'flex';
      } else {
        prevBtn.style.display = 'none';
        nextBtn.style.display = 'none';
      }
    };

    const openLightbox = (images, index, fromTrack = false) => {
      activeImageList = images;
      currentIndex = index;
      trackWasPaused = fromTrack;

      if (trackWasPaused && galleryTrack) {
        galleryTrack.classList.add('is-paused');
      }

      updateLightboxImage();
      lightbox.classList.add('is-open');
      document.body.style.overflow = 'hidden';
    };

    const closeLightbox = () => {
      lightbox.classList.remove('is-open');
      document.body.style.overflow = '';
      if (trackWasPaused && galleryTrack) {
        galleryTrack.classList.remove('is-paused');
        trackWasPaused = false;
      }
    };

    const showPrev = () => {
      currentIndex = (currentIndex - 1 + activeImageList.length) % activeImageList.length;
      updateLightboxImage();
    };

    const showNext = () => {
      currentIndex = (currentIndex + 1) % activeImageList.length;
      updateLightboxImage();
    };

    closeBtn.addEventListener('click', closeLightbox);
    overlay.addEventListener('click', closeLightbox);
    prevBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      showPrev();
    });
    nextBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      showNext();
    });

    document.addEventListener('keydown', (e) => {
      if (!lightbox.classList.contains('is-open')) return;
      if (e.key === 'Escape') closeLightbox();
      if (e.key === 'ArrowLeft') showPrev();
      if (e.key === 'ArrowRight') showNext();
    });

    // 1. Attach to Carousel Images (click stops carousel and expands image)
    if (galleryTrack) {
      galleryTrack.addEventListener('click', (e) => {
        const clickedImg = e.target.closest('img');
        if (!clickedImg) return;

        const allTrackImgs = Array.from(galleryTrack.querySelectorAll('.gallery-item img'));
        const matchIndex = allTrackImgs.findIndex(img => img.src === clickedImg.src);
        const startIndex = matchIndex >= 0 ? matchIndex : 0;

        openLightbox(allTrackImgs, startIndex, true);
      });
    }

    // 2. Attach to Full Gallery Masonry Grid
    if (fullGallery) {
      fullGallery.addEventListener('click', (e) => {
        const clickedImg = e.target.closest('img');
        if (!clickedImg) return;

        const allGalleryImgs = Array.from(fullGallery.querySelectorAll('.full-gallery-card img'));
        const matchIndex = allGalleryImgs.indexOf(clickedImg);
        const startIndex = matchIndex >= 0 ? matchIndex : 0;

        openLightbox(allGalleryImgs, startIndex, false);
      });
    }
  };

  setupGalleryLightbox();

  // 10. Scroll Reveal for Organic Safari Photo Slots
  const initPhotoSlotAnimations = () => {
    const slots = document.querySelectorAll('.safari-photo-slot.animate-on-scroll');
    if (!slots.length) return;

    if (!('IntersectionObserver' in window)) {
      slots.forEach(slot => slot.classList.add('is-visible'));
      return;
    }

    const slotObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    }, {
      root: null,
      rootMargin: '0px 0px -10% 0px',
      threshold: 0.15
    });

    slots.forEach(slot => slotObserver.observe(slot));
  };

  initPhotoSlotAnimations();
