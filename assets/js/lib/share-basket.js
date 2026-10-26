/**
 * The share links under the basket, kept current.
 *
 * inc/share-basket.php prints them with the page and publishes the current
 * link and message on the Store API's cart, under extensions.dorotape_share.
 * The Cart block re-reads that cart after every change, so this listens to
 * the store the way free-delivery.js does and rewrites the three links.
 */

const STORE = 'wc/store/cart';
const COPIED_FOR = 2000;

export function initShareBasket() {
  const node = document.querySelector('[data-share-basket]');

  if (!node) {
    return;
  }

  const whatsapp = node.querySelector('[data-share="whatsapp"]');
  const email = node.querySelector('[data-share="email"]');
  const copy = node.querySelector('[data-share="copy"]');
  const label = copy.querySelector('.share-basket__label');
  const copyText = label.textContent;
  let timer;

  copy.addEventListener('click', () => {
    const url = copy.dataset.url;

    if (!url || !navigator.clipboard) {
      return;
    }

    navigator.clipboard.writeText(url).then(() => {
      label.textContent = copy.dataset.copied;
      clearTimeout(timer);
      timer = setTimeout(() => {
        label.textContent = copyText;
      }, COPIED_FOR);
    });
  });

  const data = window.wp && window.wp.data;

  if (!data) {
    return;
  }

  let last;

  function update() {
    const store = data.select(STORE);

    if (!store || !store.getCartData) {
      return;
    }

    const cart = store.getCartData();

    // The empty default cart the store holds before its first fetch would
    // hide links the page arrived with.
    if (!cart || !cart.extensions || !('dorotape_share' in cart.extensions)) {
      return;
    }

    const share = cart.extensions.dorotape_share;

    if (share.url === last) {
      return;
    }

    last = share.url;
    node.hidden = !share.url;

    if (!share.url) {
      return;
    }

    whatsapp.href = 'https://wa.me/?text=' + encodeURIComponent(share.message);
    email.href =
      'mailto:?subject=' +
      encodeURIComponent(node.dataset.subject) +
      '&body=' +
      encodeURIComponent(share.message);
    copy.dataset.url = share.url;
  }

  data.subscribe(update);
  update();
}
