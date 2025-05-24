// Fullscreen image modal with zoom and pan
// Ensure DOM is loaded before attaching listeners
window.addEventListener('DOMContentLoaded', function() {
  const modal = document.getElementById('image-modal');
  const modalImg = document.getElementById('image-modal-img');
  const closeBtn = document.getElementById('image-modal-close');

  function openModal(src) {
    modal.classList.add('active');
    modalImg.src = src;
    modalImg.style.transform = '';
    document.body.style.overflow = 'hidden';
  }
  function closeModal() {
    modal.classList.remove('active');
    modalImg.src = '';
    document.body.style.overflow = '';
  }

  // Open modal on image click
  document.querySelectorAll('.cinema-gallery-img.single').forEach(img => {
    img.addEventListener('click', function() {
      openModal(this.src);
    });
  });
  // Close modal
  closeBtn.addEventListener('click', closeModal);
  modal.addEventListener('click', function(e) {
    if (e.target === modal) closeModal();
  });
  window.addEventListener('keydown', function(e) {
    if (modal.classList.contains('active') && (e.key === 'Escape' || e.key === 'Esc')) closeModal();
  });
});