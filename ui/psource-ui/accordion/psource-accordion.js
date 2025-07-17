function initPsourceAccordion() {
  document.querySelectorAll('.psource-accordion').forEach(accordion => {
    accordion.querySelectorAll('.psource-accordion-header').forEach(header => {
      // Verhindere mehrfaches Hinzufügen
      if (!header.classList.contains('psource-accordion-initialized')) {
        header.addEventListener('click', function () {
          const item = this.closest('.psource-accordion-item');
          if (!item) return;
          item.classList.toggle('active');
        });
        header.classList.add('psource-accordion-initialized');
      }
    });
  });
}

document.addEventListener('DOMContentLoaded', initPsourceAccordion);

const observer = new MutationObserver(() => {
  initPsourceAccordion();
});

observer.observe(document.body, { childList: true, subtree: true });