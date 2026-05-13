document.addEventListener('DOMContentLoaded', () => {
  const faqBlocks = document.querySelectorAll('.sobe-faq');

  faqBlocks.forEach((block) => {
    // Select the items
    const items = block.querySelectorAll('.faq__item');

    items.forEach((item) => {
      // Look for the button inside the item
      const button = item.querySelector('.sobe-faq__question-btn');

      if (!button) return;

      button.addEventListener('click', (e) => {
        e.preventDefault();
        const isOpen = item.classList.contains('is-open');

        // Close ALL other items (Accordion mode)
        items.forEach((el) => {
          el.classList.remove('is-open');
          el.querySelector('.sobe-faq__question-btn')?.setAttribute(
            'aria-expanded',
            'false',
          );
        });

        // If the clicked one wasn't open, open it
        if (!isOpen) {
          item.classList.add('is-open');
          button.setAttribute('aria-expanded', 'true');
        }
      });
    });
  });
});
