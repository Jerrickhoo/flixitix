document.addEventListener('DOMContentLoaded', function() {
  const showtimeBtns = document.querySelectorAll('.get-ticket-showtime-btn');
  showtimeBtns.forEach(btn => {
    btn.addEventListener('click', function() {
      showtimeBtns.forEach(b => b.classList.remove('selected'));
      this.classList.add('selected');
    });
  });

  // Animate dropdown arrow for screen select
  const select = document.querySelector('.get-ticket-screen-select');
  const wrapper = select.closest('.get-ticket-screen-select-wrapper') || select.parentElement;
  const arrow = wrapper.querySelector('.get-ticket-arrow');

  let isOpen = false;
  // For most browsers, focus/blur is the best we can do for native select
  select.addEventListener('focus', function() {
    if (wrapper) wrapper.classList.remove('arrow-animating');
    if (arrow) arrow.classList.remove('returning');
    isOpen = true;
  });
  select.addEventListener('blur', function() {
    if (wrapper) {
      wrapper.classList.add('arrow-animating');
      setTimeout(() => {
        wrapper.classList.remove('arrow-animating');
        if (arrow) {
          arrow.classList.add('returning');
          setTimeout(() => arrow.classList.remove('returning'), 340);
        }
      }, 320);
    }
    isOpen = false;
  });
  // For keyboard navigation (Enter/Space)
  select.addEventListener('keydown', function(e) {
    if ((e.key === 'Enter' || e.key === ' ' || e.key === 'Spacebar') && !isOpen) {
      if (wrapper) wrapper.classList.remove('arrow-animating');
      if (arrow) arrow.classList.remove('returning');
      isOpen = true;
    }
  });
});