(() => {
  document.querySelectorAll('[data-slider-control]').forEach((button) => {
    button.addEventListener('click', () => {
      const wrap = button.closest('.bb-slider-wrap');
      const slider = wrap?.querySelector('.bb-category-slider, .bb-product-slider, .bb-feedback-slider');
      if (!slider) return;

      const direction = button.dataset.sliderControl === 'next' ? 1 : -1;
      const slide = slider.querySelector('.bb-category-slide, .bb-product-slide');
      const styles = getComputedStyle(slider);
      const gap = parseFloat(styles.columnGap || styles.gap) || 0;
      const distance = (slide?.getBoundingClientRect().width || slider.clientWidth * 0.8) + gap;

      slider.scrollBy({ left: direction * distance, behavior: 'smooth' });
    });
  });
})();
