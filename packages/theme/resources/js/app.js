const HELLOASSO_ORIGIN = 'https://www.helloasso.com';
const HELLOASSO_MAX_HEIGHT = 3000;

window.addEventListener('message', (event) => {
  if (event.origin !== HELLOASSO_ORIGIN || !event.source) {
    return;
  }

  const iframe = [...document.querySelectorAll('iframe[data-helloasso-widget]')]
    .find((candidate) => candidate.contentWindow === event.source);
  const height = Number(event.data?.height);

  if (!iframe || !Number.isFinite(height) || height <= 0 || height > HELLOASSO_MAX_HEIGHT) {
    return;
  }

  iframe.style.height = `${height}px`;
});
