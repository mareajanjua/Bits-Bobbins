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
      setTimeout(() => {
        isDragging = false;
        blockClick = false;
      }, 120);
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
      if (dragDistance < 14) return;
      isDragging = true;
      blockClick = true;
      slider.classList.add("is-dragging");
      event.preventDefault();
      const x = event.pageX - slider.offsetLeft;
      slider.scrollLeft = scrollLeft - ((x - startX) * 1.2);
    });

    slider.addEventListener("click", (event) => {
      if (blockClick) {
        event.preventDefault();
        event.stopPropagation();
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
})();
