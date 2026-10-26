/**
 * The free delivery line above the basket and the checkout, kept current.
 *
 * inc/free-delivery.php prints it with the page and puts the same answer on
 * the Store API's cart, under extensions.dorotape. The Cart and Checkout
 * blocks keep that cart in the wc/store/cart data store and re-read it after
 * every change, a quantity, a coupon, a new delivery address, so this only
 * has to listen to the store and copy the answer across.
 */

const STORE = 'wc/store/cart';
const STATE_FREE = 'is-free';

export function initFreeDelivery() {
  const nodes = document.querySelectorAll('[data-free-delivery]');
  const data = window.wp && window.wp.data;

  if (!nodes.length || !data) {
    return;
  }

  let last;

  function render(status) {
    nodes.forEach((node) => {
      node.hidden = !status;

      if (!status) {
        return;
      }

      node.classList.toggle(STATE_FREE, !!status.free);
      node.querySelector('.free-delivery__text').textContent = status.message;
      node.querySelector('.free-delivery__fill').style.width = status.progress + '%';
    });
  }

  function update() {
    const store = data.select(STORE);

    // The blocks register the store with their own script, which may not
    // have run yet.
    if (!store || !store.getCartData) {
      return;
    }

    const cart = store.getCartData();

    // Before the blocks have fetched anything the store holds an empty
    // default cart, which would hide the line the page arrived with.
    if (!cart || !cart.extensions || !('dorotape' in cart.extensions)) {
      return;
    }

    const status = cart.extensions.dorotape.free_delivery || null;
    const key = JSON.stringify(status);

    if (key !== last) {
      last = key;
      render(status);
    }
  }

  data.subscribe(update);
  update();
}
