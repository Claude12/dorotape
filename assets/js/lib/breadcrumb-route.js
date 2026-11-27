/**
 * Breadcrumb route: on a product in several categories, show the trail the
 * visitor came by.
 *
 * The page prints its main trail and lists the others in data-routes
 * (inc/single-product.php). The page cache serves one copy to everyone, so
 * the choice is made here, from the category page in document.referrer.
 * The highlighted menu tab and the toast's "Continue shopping" follow the
 * same choice. With scripts off, or arriving from anywhere else, the main
 * trail stays.
 */

const STORE = 'dt-route:';

// "/product-category/a/b/page/2/?orderby=price" -> "/product-category/a/b/"
function pathOf(url) {
  try {
    const u = new URL(url, window.location.href);
    if (u.origin !== window.location.origin) return '';
    return u.pathname.replace(/\/page\/\d+\/?$/, '/').replace(/\/?$/, '/');
  } catch {
    return '';
  }
}

function remember(key, value) {
  try {
    if (null === value) window.sessionStorage.removeItem(key);
    else window.sessionStorage.setItem(key, String(value));
  } catch { /* storage blocked: the choice just is not kept for a reload */ }
}

function recall(key) {
  try {
    return window.sessionStorage.getItem(key);
  } catch {
    return null;
  }
}

// The route whose trail passes through the page the visitor came from. The
// printed route (the first match by position is not necessarily it) wins a
// tie, so a visitor from a shared parent keeps the main trail.
function pick(routes, from, current) {
  if (!from) return -1;
  let found = -1;
  routes.forEach((route, index) => {
    const hit = route.crumbs.some((crumb) => crumb.url && pathOf(crumb.url) === from);
    if (!hit) return;
    if (index === current || -1 === found) found = index;
  });
  return found;
}

function render(nav, route) {
  const list = nav.querySelector('.breadcrumb__trail');
  const separator = list && list.querySelector('.breadcrumb__separator');
  if (!list || !separator) return;

  const items = [];
  route.crumbs.forEach((crumb, index) => {
    if (index) items.push(separator.cloneNode(true));
    const li = document.createElement('li');
    li.className = 'breadcrumb__crumb';
    const last = index === route.crumbs.length - 1;
    const el = document.createElement(last ? 'span' : 'a');
    el.className = last ? 'breadcrumb__current' : 'breadcrumb__link';
    if (last) el.setAttribute('aria-current', 'page');
    else el.href = crumb.url;
    // Labels arrive HTML-escaped from WordPress ("Sign &amp; Display"), so
    // decode them through a parser that never runs or attaches anything.
    el.textContent = new DOMParser().parseFromString(crumb.label, 'text/html').documentElement.textContent;
    li.appendChild(el);
    items.push(li);
  });
  list.replaceChildren(...items);
}

function markTab(menuId) {
  if (!menuId) return;
  document.querySelectorAll('[data-menu-item]').forEach((li) => {
    li.classList.toggle('is-current', String(menuId) === li.getAttribute('data-menu-item'));
  });
}

export function initBreadcrumbRoute() {
  const nav = document.querySelector('.breadcrumb[data-routes]');
  if (!nav) return;

  let routes;
  try {
    routes = JSON.parse(nav.getAttribute('data-routes'));
  } catch {
    return;
  }
  if (!Array.isArray(routes) || routes.length < 2) return;

  const here = pathOf(window.location.href);
  const key = STORE + here;
  const from = pathOf(document.referrer);

  // Which route is printed: the one whose category trail matches the page's.
  const printed = Array.from(nav.querySelectorAll('.breadcrumb__link')).map((a) => pathOf(a.href)).join('|');
  const current = routes.findIndex(
    (route) => route.crumbs.slice(0, -1).map((c) => pathOf(c.url)).join('|') === printed
  );

  let chosen;
  if (from === here) {
    // A reload, or the add to cart that posts back to this page.
    chosen = parseInt(recall(key), 10);
    if (isNaN(chosen)) chosen = -1;
  } else {
    chosen = pick(routes, from, current);
    remember(key, chosen > -1 ? chosen : null);
  }

  if (chosen < 0 || chosen === current || !routes[chosen]) return;

  const route = routes[chosen];
  render(nav, route);
  markTab(route.menu);
  if (route.url && window.dorotapeToastData) window.dorotapeToastData.continueUrl = route.url;
}
