import {Pane} from 'https://cdn.jsdelivr.net/npm/tweakpane@4.0.5/dist/tweakpane.min.js';

let density = 5;
let distance = 0;
let speed = 60;
const directions = ['top', 'right', 'bottom', 'left'];
let isPaused = false;
let images = ["https://picsum.photos/id/106/900/500","https://picsum.photos/id/115/900/500","https://picsum.photos/id/116/900/500","https://picsum.photos/id/124/900/500","https://picsum.photos/id/126/900/500","https://picsum.photos/id/130/900/500","https://picsum.photos/id/143/900/500","https://picsum.photos/id/152/900/500","https://picsum.photos/id/167/900/500","https://picsum.photos/id/190/900/500","https://picsum.photos/id/191/900/500","https://picsum.photos/id/193/900/500","https://picsum.photos/id/195/900/500","https://picsum.photos/id/204/900/500","https://picsum.photos/id/227/900/500","https://picsum.photos/id/251/900/500","https://picsum.photos/id/253/900/500","https://picsum.photos/id/256/900/500","https://picsum.photos/id/257/900/500","https://picsum.photos/id/259/900/500","https://picsum.photos/id/271/900/500","https://picsum.photos/id/274/900/500","https://picsum.photos/id/277/900/500","https://picsum.photos/id/278/900/500","https://picsum.photos/id/289/900/500","https://picsum.photos/id/291/900/500","https://picsum.photos/id/296/900/500","https://picsum.photos/id/299/900/500","https://picsum.photos/id/306/900/500","https://picsum.photos/id/308/900/500","https://picsum.photos/id/318/900/500","https://picsum.photos/id/327/900/500","https://picsum.photos/id/337/900/500","https://picsum.photos/id/339/900/500","https://picsum.photos/id/376/900/500","https://picsum.photos/id/381/900/500","https://picsum.photos/id/392/900/500","https://picsum.photos/id/395/900/500","https://picsum.photos/id/402/900/500","https://picsum.photos/id/411/900/500","https://picsum.photos/id/419/900/500","https://picsum.photos/id/424/900/500","https://picsum.photos/id/428/900/500"];

// If server provided initial images, prefer them
if (typeof window !== 'undefined' && Array.isArray(window.INIT_BG_IMAGES) && window.INIT_BG_IMAGES.length > 0) {
  images = window.INIT_BG_IMAGES.slice();
}

function preloadImages(srcArray, callback) {
  let loaded = 0;
  srcArray.forEach(src => {
    const img = new Image();
    img.onload = () => {
      loaded++;
      if (loaded === srcArray.length) callback();
    };
    img.src = src;
  });
}

document.addEventListener("DOMContentLoaded", () => {
  preloadImages(images, () => { renderWalls(); });
});

const allGridElements = [];
let intervalId;

function renderWalls() {
  const gridContainer = document.querySelector('.inf-grid-hero-container');
  if (!gridContainer) return;
  gridContainer.style.setProperty('--grid-sz', density);
  gridContainer.style.setProperty('--rev-dis', distance);

  allGridElements.length = 0;

  directions.forEach(dir => {
    const parent = document.querySelector(`.${dir}`);
    if (!parent) return;
    parent.innerHTML = '';
    const total = density * density;
    for (let i = 1; i <= total; i++) {
      const div = document.createElement('div');
      div.classList.add(`${dir.charAt(0)}${i}`);
      parent.appendChild(div);
      allGridElements.push(div);
    }
  });

  startImageInterval();
}

let loadedCount = 0;
let totalElementsToLoad = 0;

function startImageInterval() {
  if (intervalId) clearInterval(intervalId);
  loadedCount = 0;
  totalElementsToLoad = allGridElements.length;

  intervalId = setInterval(() => {
    if (isPaused) return;
    let unloadedElements = allGridElements.filter(el => !el.classList.contains('loaded'));
    if (unloadedElements.length === 0) return;

    for (let i = 0; i < 6 && unloadedElements.length > 0; i++) {
      const idx = Math.floor(Math.random() * unloadedElements.length);
      const randomElement = unloadedElements.splice(idx, 1)[0];
      const randomImage = images[Math.floor(Math.random() * images.length)];
      randomElement.style.background = `url('${randomImage}')`;
      randomElement.classList.add('loaded');
      loadedCount++;
    }

    if (loadedCount >= totalElementsToLoad) {
      clearInterval(intervalId);
      document.dispatchEvent(new Event('allImagesLoaded'));
    }
  }, speed);
}

function pauseInterval() {
  isPaused = true;
}

function resumeInterval() {
  document.querySelector('.selected')?.classList.remove('selected');
  document.querySelector('.selectedPane')?.classList.remove('selectedPane');
  if (!isPaused) return;
  isPaused = false;
  startImageInterval();
}

const backBtn = document.getElementById('back-btn');
if (backBtn) backBtn.addEventListener('click', resumeInterval);

const focusBtn = document.querySelector('.button');
if (focusBtn) focusBtn.addEventListener('click', () => {
  const newValue = distance === 100 ? 0 : 100;
  animateDistance(newValue, 1000);
});

function animateDistance(toValue, duration = 600) {
  const el = document.querySelector('.inf-grid-hero-container');
  if (!el) return;
  const fromValue = distance;
  const startTime = performance.now();
  function update(currentTime) {
    const elapsed = currentTime - startTime;
    const progress = Math.min(elapsed / duration, 1);
    const eased = 1 - Math.pow(1 - progress, 3);
    distance = fromValue + (toValue - fromValue) * eased;
    el.style.setProperty('--rev-dis', distance.toFixed(2));
    pane.refresh();
    if (progress < 1) requestAnimationFrame(update);
  }

  requestAnimationFrame(update);
}

document.addEventListener('allImagesLoaded', () => {
  document.body.classList.add('all-loaded');
  console.log(`\n    Trigger for all images being loaded. \n    Idea maybe to unload after a set time of loaded and refresh?\n  `);
});

// Accept dynamic image updates via custom event
// Usage: document.dispatchEvent(new CustomEvent('updateGridImages', { detail: { urls: string[], mode: 'replace'|'append' } }))
document.addEventListener('updateGridImages', (ev) => {
  const detail = ev.detail || {};
  const urls = Array.isArray(detail.urls) ? detail.urls.filter(Boolean) : [];
  const mode = detail.mode === 'append' ? 'append' : 'replace';
  if (!urls.length) return;
  images = mode === 'append' ? images.concat(urls) : urls.slice();
  renderWalls();
});

/* js gui */
const PARAMS = {
  size: density,
  speed: speed,
};

const pane = new Pane();
const size = pane.addBinding( PARAMS, 'size', {min: 2, max: 8, step: 1});
size.on('change', function(ev) {
  density = ev.value;
  renderWalls();
});
// Distance control removed from UI
const spd = pane.addBinding( PARAMS, 'speed', {min: 50, max: 400, step: 50} );
spd.on('change', function(ev) {
  speed = ev.value;
  startImageInterval();
});

