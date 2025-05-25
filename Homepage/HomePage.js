// Theme toggle logic (shared across all pages)
function setTheme(mode) {
  if (mode === 'light') {
    document.body.classList.add('light');
    document.body.classList.add('light-mode');
    document.getElementById('theme-icon')?.classList.remove('fa-moon');
    document.getElementById('theme-icon')?.classList.add('fa-sun');
  } else {
    document.body.classList.remove('light');
    document.body.classList.remove('light-mode');
    document.getElementById('theme-icon')?.classList.remove('fa-sun');
    document.getElementById('theme-icon')?.classList.add('fa-moon');
  }
  localStorage.setItem('theme', mode);
}

function toggleTheme() {
  const isLight = !document.body.classList.contains('light') && !document.body.classList.contains('light-mode');
  setTheme(isLight ? 'light' : 'dark');
}

// List of all movie poster image paths (relative to Homepage/)
const nowShowingPosterImages = [
  '../movie posters/Twisters.jpg',
  '../movie posters/The Garfield Movie.jpg',
  '../movie posters/The Fall Guy.jpg',
  '../movie posters/Superman Legacy.jpeg',
  '../movie posters/Mission Impossible – Dead Reckoning Part Two.jpg',
  '../movie posters/Kung Fu Panda 4.jpg',
  '../movie posters/Joker Folie à Deux.jpg',
  '../movie posters/Inside Out 2.jpg',
  '../movie posters/Furiosa A Mad Max Saga.jpg',
  '../movie posters/Fantastic Four.jpg',
  '../movie posters/Dune.jpg',
  '../movie posters/Deadpool & Wolverine.jpg',
  '../movie posters/Bad Boys Ride or Die.jpg',
  '../movie posters/Avatar 3.jpg',
  '../movie posters/A Quiet Place Day One.jpg'
];

const comingSoonPosterImages = [
  '../background/carryon.jpg',
  '../background/conclave.jpg',
  '../background/conjuring_last_rites.jpg',
  '../background/despicable_me_four.jpg',
  '../background/didi.jpg',
  '../background/fear_street_prom_queen_ver7.jpg',
  '../background/final_destination_bloodlines_ver8.jpg',
  '../background/first_omen.jpg',
  '../background/ghostbusters_afterlife_two_ver7.jpg',
  '../background/gladiator_ii.jpg',
  '../background/longlegs.jpg',
  '../background/maxxxine_ver2.jpg',
  '../background/nobody_two_ver2.jpg',
  '../background/smurfs_ver3.jpg',
  '../background/thunderbolts_ver10.jpg'
];

// Utility to get a random non-repeating subset of images
function getRandomUniqueImages(pool, count) {
  const shuffled = pool.slice().sort(() => Math.random() - 0.5);
  return shuffled.slice(0, Math.min(count, shuffled.length));
}

// Generate separate sets for each tab
const nowShowingMovies = Array.from({length: 12}, () => getRandomImage());
const comingSoonMovies = Array.from({length: 12}, () => getRandomImage());

function renderCarousel(tab) {
  const carousel = document.getElementById('carousel');
  const container = document.querySelector('.carousel-container');
  if (!carousel || !container) return;
  carousel.innerHTML = '';
  // Toggle background color for COMING SOON
  if (tab === 'COMING SOON') {
    container.classList.add('coming-soon-active');
  } else {
    container.classList.remove('coming-soon-active');
  }
  // Use separate pools for each tab
  const images = tab === 'COMING SOON'
    ? getRandomUniqueImages(comingSoonPosterImages, 12)
    : getRandomUniqueImages(nowShowingPosterImages, 12);
  images.forEach(img => {
    const card = document.createElement('div');
    card.className = 'movie-card';
    card.innerHTML = `<img src="${img}" alt="Movie Poster" style="width:100%;height:100%;object-fit:cover;">`;
    carousel.appendChild(card);
  });
}

function switchTab(selected) {
  document.querySelectorAll('.tab').forEach(tab => {
    tab.classList.remove('active');
  });
  selected.classList.add('active');
  renderCarousel(selected.textContent.trim());
  // Scroll carousel to the start (far left) when switching tabs
  const carousel = document.getElementById('carousel');
  if (carousel) carousel.scrollLeft = 0;
}

function scrollCarousel(direction) {
  const carousel = document.getElementById("carousel");
  const scrollAmount = 1300 * direction; // You can tweak this
  carousel.scrollBy({ left: scrollAmount, behavior: 'smooth' });
}

window.addEventListener('DOMContentLoaded', function() {
  // Theme
  const savedTheme = localStorage.getItem('theme');
  setTheme(savedTheme === 'light' ? 'light' : 'dark');
  const themeToggle = document.getElementById('theme-toggle');
  if (themeToggle) {
    themeToggle.addEventListener('click', toggleTheme);
  }
  // Attach tab and carousel button events
  document.querySelectorAll('.tab').forEach(tab => {
    tab.onclick = function() { switchTab(this); };
  });
  document.querySelectorAll('.carousel-button.left').forEach(btn => {
    btn.onclick = function() { scrollCarousel(-1); };
  });
  document.querySelectorAll('.carousel-button.right').forEach(btn => {
    btn.onclick = function() { scrollCarousel(1); };
  });
  // Directly render NOW SHOWING posters on first load (random, non-repeating)
  renderCarousel('NOW SHOWING');
  // Set NOW SHOWING tab as active
  document.querySelectorAll('.tab').forEach(tab => {
    if (tab.textContent.trim() === 'NOW SHOWING') tab.classList.add('active');
    else tab.classList.remove('active');
  });
});
