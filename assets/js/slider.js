// RK Destu Store — auto-slide iklan produk di halaman utama (5 slide)
(function () {
  const track = document.getElementById('rkSlides');
  if (!track) return;
  const slides = Array.from(track.children);
  const dotsWrap = document.getElementById('rkSlideDots');
  if (!slides.length) return;

  let idx = 0;
  const AUTO_MS = 4500;
  let timer = null;

  slides.forEach((_, i) => {
    const dot = document.createElement('button');
    dot.className = 'rk-dot' + (i === 0 ? ' active' : '');
    dot.setAttribute('aria-label', 'Slide ' + (i + 1));
    dot.addEventListener('click', () => goTo(i));
    dotsWrap.appendChild(dot);
  });
  const dots = Array.from(dotsWrap.children);

  function goTo(i) {
    idx = (i + slides.length) % slides.length;
    track.style.transform = `translateX(-${idx * 100}%)`;
    dots.forEach((d, di) => d.classList.toggle('active', di === idx));
    restart();
  }
  function next() { goTo(idx + 1); }
  function restart() {
    clearInterval(timer);
    timer = setInterval(next, AUTO_MS);
  }

  const slider = document.getElementById('rkSlider');
  slider.addEventListener('mouseenter', () => clearInterval(timer));
  slider.addEventListener('mouseleave', restart);

  // swipe support
  let startX = null;
  slider.addEventListener('touchstart', (e) => { startX = e.touches[0].clientX; }, { passive: true });
  slider.addEventListener('touchend', (e) => {
    if (startX === null) return;
    const dx = e.changedTouches[0].clientX - startX;
    if (Math.abs(dx) > 40) goTo(idx + (dx < 0 ? 1 : -1));
    startX = null;
  }, { passive: true });

  restart();
})();
