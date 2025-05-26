document.addEventListener('DOMContentLoaded', function() {
  // BOOKED_SEATS is provided by PHP in the HTML

  const seatData = [
    Array(8).fill([1, 2, 3]),  // Left section
    Array(8).fill([4, 5, 6, 7, 8, 9]),  // Middle section
    Array(8).fill([10, 11, 12])  // Right section
  ];
  const rowLabels = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
  const seatMap = document.getElementById('seat-map');
  seatMap.innerHTML = '';

  seatData.forEach((section, sectionIdx) => {
    const sectionDiv = document.createElement('div');
    sectionDiv.className = 'seat-section';

    section.forEach((row, rowIdx) => {
      const rowDiv = document.createElement('div');
      rowDiv.className = 'seat-row';

      if (sectionIdx === 0) {
        const label = document.createElement('span');
        label.className = 'seat-label';
        label.textContent = rowLabels[rowIdx];
        rowDiv.appendChild(label);
      }

      row.forEach((seatNum, colIdx) => {
        const seat = document.createElement('button');
        seat.className = 'seat';
        seat.type = 'button';
        seat.dataset.section = sectionIdx;
        seat.dataset.row = rowLabels[rowIdx];
        seat.dataset.col = seatNum;
        seat.textContent = seatNum;

        // Assign unique ID to each seat
        seat.id = `seat-${rowLabels[rowIdx]}-${seatNum}`;

        // Compose seat value as in booking: e.g. "A1"
        const seatValue = rowLabels[rowIdx] + seatNum;

        // Check if seat is booked
        if (typeof BOOKED_SEATS !== "undefined" && BOOKED_SEATS.includes(seatValue)) {
          seat.classList.add('unavailable');
          seat.disabled = true;
          seat.style.backgroundColor = '#c0392b'; // Use a strong red for unavailable
          seat.style.color = '#fff';
          seat.style.border = '2px solid #c0392b';
        } else {
          seat.classList.add('available');
        }

        rowDiv.appendChild(seat);
      });

      if (sectionIdx === seatData.length - 1) {
        const label = document.createElement('span');
        label.className = 'seat-label';
        label.textContent = rowLabels[rowIdx];
        rowDiv.appendChild(label);
      }

      sectionDiv.appendChild(rowDiv);
    });

    seatMap.appendChild(sectionDiv);
  });

  // Seat count logic
  let seatLimit = 1;
  const seatCount = document.getElementById('multi-seat-count');

  seatCount.addEventListener('input', function() {
    let val = parseInt(this.value);
    if (isNaN(val) || val < 1) val = 1;
    if (val > 10) val = 10;
    this.value = val;
    seatLimit = val;
    enforceSeatLimit();
    updateConfirmBtn();
  });
  function enforceSeatLimit() {
    const selected = document.querySelectorAll('.seat.selected');
    if (selected.length > seatLimit) {
      // Deselect extras from the end
      Array.from(selected).slice(seatLimit).forEach(seat => seat.classList.remove('selected'));
    }
  }

  // Seat selection logic
  seatMap.addEventListener('click', function(e) {
    if (
      e.target.classList.contains('seat') &&
      e.target.classList.contains('available') &&
      !e.target.classList.contains('unavailable') &&
      !e.target.disabled
    ) {
      // Allow selection and deselection up to seat limit
      if (e.target.classList.contains('selected')) {
        e.target.classList.remove('selected');
      } else {
        const selected = document.querySelectorAll('.seat.selected');
        if (selected.length < seatLimit) {
          e.target.classList.add('selected');
        }
      }
      updateConfirmBtn();
    }
  });

  // Payment modal elements
  const paymentModal = document.getElementById('payment-modal');
  const modalTotalPrice = document.getElementById('modal-total-price');
  const gcashButton = document.getElementById('gcash-button');
  const cancelPaymentBtn = document.getElementById('cancel-payment');

  // Confirm button logic (show payment modal)
  const confirmBtn = document.getElementById('seat-confirm-btn');
  function updateConfirmBtn() {
    const selected = document.querySelectorAll('.seat.selected');
    confirmBtn.disabled = selected.length === 0;
    
    // Update total price
    const totalPrice = selected.length * TICKET_PRICE;
    document.getElementById('total-price').textContent = totalPrice.toFixed(2);
    modalTotalPrice.textContent = totalPrice.toFixed(2);
  }

  // Show confirmation modal when confirm button is clicked
  confirmBtn.addEventListener('click', function() {
    const selected = document.querySelectorAll('.seat.selected');
    if (selected.length === 0) {
      console.log("No seat selected.");
      return;
    }
    
    // Update total price display
    const totalPrice = (selected.length * TICKET_PRICE).toFixed(2);
    document.getElementById('modal-total-price').textContent = totalPrice;
    
    paymentModal.style.display = 'flex';
  });

  // Handle booking confirmation
  gcashButton.addEventListener('click', async function() {
    // Show processing state
    const originalText = this.innerHTML;
    this.disabled = true;
    this.innerHTML = `
      <div class="loading-spinner"></div>
      Processing...
    `;

    try {
      // Simulate payment processing
      await new Promise(resolve => setTimeout(resolve, 1500));

      // Prepare form submission
      const container = document.getElementById('selected-seats-container');
      container.innerHTML = '';
      const selected = document.querySelectorAll('.seat.selected');
      selected.forEach(seat => {
        const seatValue = seat.dataset.row + seat.textContent;
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'seat[]';
        input.value = seatValue;
        container.appendChild(input);
      });

      // Submit form
      document.getElementById('seat-confirm-form').submit();
    } catch (error) {
      console.error('Payment failed:', error);
      alert('Payment processing failed. Please try again.');
      
      // Reset button state
      this.disabled = false;
      this.innerHTML = originalText;
    }
  });

  // Close payment modal
  cancelPaymentBtn.addEventListener('click', function() {
    paymentModal.style.display = 'none';
  });

  // Initialize confirm button state
  updateConfirmBtn();
});