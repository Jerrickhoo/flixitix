// DOM Elements
let transactionSection;

document.addEventListener('DOMContentLoaded', () => {
  // Initialize DOM elements
  transactionSection = document.getElementById('transaction-section');

  // Set initial visibility
  transactionSection.style.display = 'block';

  // Transaction modal logic
  setupTransactionModal();
});

// Transaction modal setup
function setupTransactionModal() {
  const modal = document.getElementById('transaction-modal');
  const modalClose = document.getElementById('transaction-modal-close');
  const viewTransactionBtns = document.querySelectorAll('.view-transaction-btn');

  viewTransactionBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const movieTitle = btn.closest('.transaction-card').querySelector('.transaction-movie-title').textContent;
      openTransactionModal({
        title: movieTitle,
        date: '2025-05-18',
        seat: 'A1',
        time: '3:00 AM',
        price: '₱250.00',
        payment: 'GCash',
        status: 'Completed'
      });
    });
  });

  modalClose.addEventListener('click', () => {
    modal.style.display = 'none';
  });

  window.addEventListener('click', (event) => {
    if (event.target === modal) {
      modal.style.display = 'none';
    }
  });
}

function openTransactionModal(data) {
  const modal = document.getElementById('transaction-modal');
  document.getElementById('modal-movie-title').textContent = data.title;
  document.getElementById('modal-transaction-list').innerHTML = `
    <div><strong>Date:</strong> ${data.date}</div>
    <div><strong>Seat:</strong> ${data.seat}</div>
    <div><strong>Time:</strong> ${data.time}</div>
    <div><strong>Price:</strong> ${data.price}</div>
    <div><strong>Payment Method:</strong> ${data.payment}</div>
    <div><strong>Status:</strong> ${data.status}</div>
  `;
  modal.style.display = 'block';
}
