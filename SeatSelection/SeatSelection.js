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

  // Multi-seat booking logic
  let multiSeatMode = false;
  let multiSeatLimit = 1;
  const multiSeatToggle = document.getElementById('multi-seat-toggle');
  const multiSeatCount = document.getElementById('multi-seat-count');
  const multiSeatHint = document.getElementById('multi-seat-hint');

  multiSeatToggle.addEventListener('change', function() {
    multiSeatMode = this.checked;
    multiSeatCount.style.display = multiSeatMode ? 'inline-block' : 'none';
    multiSeatHint.style.display = multiSeatMode ? 'inline' : 'none';
    multiSeatLimit = multiSeatMode ? parseInt(multiSeatCount.value) : 1;
    // Deselect extra seats if needed
    enforceSeatLimit();
    updateConfirmBtn();
  });
  multiSeatCount.addEventListener('input', function() {
    let val = parseInt(this.value);
    if (isNaN(val) || val < 1) val = 1;
    if (val > 10) val = 10;
    this.value = val;
    multiSeatLimit = val;
    enforceSeatLimit();
    updateConfirmBtn();
  });
  function enforceSeatLimit() {
    const selected = document.querySelectorAll('.seat.selected');
    if (selected.length > multiSeatLimit) {
      // Deselect extras
      Array.from(selected).slice(multiSeatLimit).forEach(seat => seat.classList.remove('selected'));
    }
  }

  // Seat selection logic (allow multiple, limit by multiSeatLimit)
  seatMap.addEventListener('click', function(e) {
    if (
      e.target.classList.contains('seat') &&
      e.target.classList.contains('available') &&
      !e.target.classList.contains('unavailable') &&
      !e.target.disabled
    ) {
      if (multiSeatMode) {
        // Toggle selection, but limit to multiSeatLimit
        if (e.target.classList.contains('selected')) {
          e.target.classList.remove('selected');
        } else {
          const selected = document.querySelectorAll('.seat.selected');
          if (selected.length < multiSeatLimit) {
            e.target.classList.add('selected');
          }
        }
      } else {
        // Single seat mode
        document.querySelectorAll('.seat.selected').forEach(seat => seat.classList.remove('selected'));
        e.target.classList.add('selected');
      }
      updateConfirmBtn();
    }
  });

  // Confirm button logic (submit all selected seats)
  const confirmBtn = document.getElementById('seat-confirm-btn');
  function updateConfirmBtn() {
    const selected = document.querySelectorAll('.seat.selected');
    confirmBtn.disabled = selected.length === 0;
  }
  confirmBtn.addEventListener('click', function() {
    const selected = document.querySelectorAll('.seat.selected');
    if (selected.length === 0) {
      console.log("No seat selected.");
      return;
    }
    // Remove previous seat inputs
    const container = document.getElementById('selected-seats-container');
    container.innerHTML = '';
    // Add a hidden input for each selected seat
    selected.forEach(seat => {
      const seatValue = seat.dataset.row + seat.textContent;
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'seat[]';
      input.value = seatValue;
      container.appendChild(input);
    });
    document.getElementById('seat-confirm-form').submit();
  });
  updateConfirmBtn();
});