document.addEventListener('DOMContentLoaded', function () {
  // --- Custom Dropdown Logic ---
  function setupDropdown(dropdownId, selectedId, optionsId, labelClass) {
    const dropdown = document.getElementById(dropdownId);
    const selected = document.getElementById(selectedId);
    const options = document.getElementById(optionsId);

    if (dropdown && selected && options) {
      selected.addEventListener('click', function(e) {
        dropdown.classList.toggle('open');
      });
      options.querySelectorAll('div').forEach(option => {
        option.addEventListener('click', function(e) {
          options.querySelectorAll('div').forEach(opt => opt.classList.remove('selected'));
          this.classList.add('selected');
          if (labelClass) {
            selected.querySelector(labelClass).textContent = this.textContent.trim();
          }
          dropdown.classList.remove('open');
          fetchFilteredMovies();
        });
      });
      document.addEventListener('click', function(e) {
        if (!dropdown.contains(e.target)) {
          dropdown.classList.remove('open');
        }
      });
    }
  }

  // Only setup dropdowns that match your table columns
  setupDropdown('availabilityDropdown', 'availabilitySelected', 'availabilityOptions', '.availability-name');
  setupDropdown('ratingDropdown', 'ratingSelected', 'ratingOptions', '.rating-label');

  // --- AJAX Filtering ---
  function fetchFilteredMovies() {
    const genre = document.getElementById('genre').value;
    const availability = document.querySelector('#availabilityOptions .availability-option.selected')?.dataset.availability || '';
    const rating = document.querySelector('#ratingOptions .rating-option.selected')?.dataset.rating || '';
    const search = document.getElementById('movieSearch').value;

    const params = new URLSearchParams({
      genre, availability, rating, search
    });

    fetch('filter_movies.php?' + params.toString())
      .then(res => res.text())
      .then(html => {
        document.querySelector('.movie-menu-grid').innerHTML = html;
        // Re-attach click listeners for new dropdown options if needed
        setupDropdown('availabilityDropdown', 'availabilitySelected', 'availabilityOptions', '.availability-name');
        setupDropdown('ratingDropdown', 'ratingSelected', 'ratingOptions', '.rating-label');
      });
  }

  // Listen for filter changes
  document.getElementById('genre').addEventListener('change', fetchFilteredMovies);
  document.getElementById('movieSearch').addEventListener('input', fetchFilteredMovies);

  // Initial fetch (optional)
  // fetchFilteredMovies();
});