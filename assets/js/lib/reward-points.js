/**
 * The reward points line above the basket and the checkout, kept current.
 *
 * inc/rewards.php prints it with the page and puts the same answer on the
 * Store API's cart, under extensions.dorotape_rewards. Works like the free
 * delivery line (free-delivery.js): listen to the blocks' cart store and copy
 * the answer across whenever it changes.
 */

const STORE = 'wc/store/cart';

export function initRewardPoints() {
  const nodes = document.querySelectorAll('[data-reward-points]');
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

      node.querySelector('.reward-points-notice__text').textContent = status.message;
      node.querySelector('.reward-points-notice__fill').style.width = status.progress + '%';
    });
  }

  function update() {
    const store = data.select(STORE);

    if (!store || !store.getCartData) {
      return;
    }

    const cart = store.getCartData();

    // The store's empty default cart, before the blocks have fetched
    // anything, would hide the line the page arrived with.
    if (!cart || !cart.extensions || !('dorotape_rewards' in cart.extensions)) {
      return;
    }

    const status = cart.extensions.dorotape_rewards.points || null;
    const key = JSON.stringify(status);

    if (key !== last) {
      last = key;
      render(status);
    }
  }

  data.subscribe(update);
  update();
}
