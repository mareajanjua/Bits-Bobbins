(() => {
  const sliders = document.querySelectorAll(".bb-category-slider, .bb-product-slider");

  sliders.forEach((slider) => {
    let isDown = false;
    let isDragging = false;
    let blockClick = false;
    let startX = 0;
    let scrollLeft = 0;

    const stopDrag = () => {
      isDown = false;
      isDragging = false;
      blockClick = false;
      slider.classList.remove("is-dragging");
    };

    slider.addEventListener("pointerdown", (event) => {
      isDown = true;
      isDragging = false;
      blockClick = false;
      startX = event.pageX - slider.offsetLeft;
      scrollLeft = slider.scrollLeft;
      slider.setPointerCapture?.(event.pointerId);
    });

    slider.addEventListener("pointermove", (event) => {
      if (!isDown) return;
      const dragDistance = Math.abs((event.pageX - slider.offsetLeft) - startX);
      if (dragDistance < 24) return;
      isDragging = true;
      blockClick = true;
      slider.classList.add("is-dragging");
      event.preventDefault();
      const x = event.pageX - slider.offsetLeft;
      slider.scrollLeft = scrollLeft - ((x - startX) * 1.2);
    });

    slider.addEventListener("click", (event) => {
      if (event.target.closest("a, button, form, input, select, textarea, label")) return;

      if (blockClick) {
        event.preventDefault();
      }
    }, true);

    slider.addEventListener("pointerup", stopDrag);
    slider.addEventListener("pointercancel", stopDrag);
    slider.addEventListener("pointerleave", stopDrag);
  });

  document.querySelectorAll("[data-product-slide]").forEach((button) => {
    button.addEventListener("click", () => {
      const slider = button.closest(".bb-product-carousel-wrap")?.querySelector(".bb-product-slider");
      if (!slider) return;

      const direction = button.dataset.productSlide === "next" ? 1 : -1;
      const distance = slider.querySelector(".bb-product-slide")?.offsetWidth ?? 280;
      slider.scrollBy({ left: direction * (distance + 32), behavior: "smooth" });
    });
  });

  document.querySelectorAll(".bb-product-card[data-product-url]").forEach((card) => {
    const openProduct = () => {
      if (card.dataset.productUrl) {
        window.location.href = card.dataset.productUrl;
      }
    };

    card.addEventListener("click", (event) => {
      if (event.target.closest("button, input, select, textarea, form")) return;
      event.preventDefault();
      openProduct();
    });

    card.addEventListener("keydown", (event) => {
      if (event.key !== "Enter" && event.key !== " ") return;
      if (event.target.closest("a, button, input, select, textarea, form")) return;
      event.preventDefault();
      openProduct();
    });
  });

  document.querySelectorAll(".bb-feedback-track").forEach((track) => {
    const firstSequence = track.querySelector(".bb-feedback-sequence");
    if (!firstSequence) return;

    let sequenceWidth = firstSequence.scrollWidth;
    if (!sequenceWidth) return;

    const movesRight = track.classList.contains("bb-feedback-track-right");
    let progress = movesRight ? sequenceWidth : 0;
    let previousTime = null;
    let paused = false;
    const speed = 28 / 1000;

    if ("ResizeObserver" in window) {
      const resizeObserver = new ResizeObserver(() => {
        sequenceWidth = firstSequence.scrollWidth;
        progress = movesRight ? sequenceWidth : 0;
      });
      resizeObserver.observe(firstSequence);
    }

    track.addEventListener("mouseenter", () => {
      paused = true;
    });

    track.addEventListener("mouseleave", () => {
      paused = false;
      previousTime = null;
    });

    const animate = (time) => {
      if (previousTime === null) previousTime = time;
      const delta = time - previousTime;
      previousTime = time;

      if (!paused && sequenceWidth > 0) {
        progress = movesRight
          ? progress - (delta * speed)
          : progress + (delta * speed);

        if (progress >= sequenceWidth) progress = 0;
        if (progress <= 0) progress = sequenceWidth;

        const offset = movesRight ? -progress : -progress;
        track.style.transform = `translate3d(${offset}px, 0, 0)`;
      }

      window.requestAnimationFrame(animate);
    };

    window.requestAnimationFrame(animate);
  });
})();
