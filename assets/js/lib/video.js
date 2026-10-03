/**
 * Video block: swap the still for the player when it is played.
 *
 * The block renders the provider's still as a link to the video, with the
 * player's markup waiting in a <template>, so nothing from the provider loads
 * until someone asks for it. Delegated, so it needs no setup per block.
 */
export function initVideo() {
  document.addEventListener('click', (e) => {
    const play = e.target.closest ? e.target.closest('[data-dt-video]') : null;
    if (!play) return;

    const template = play.parentNode.querySelector('template[data-dt-video-embed]');
    if (!template) return;

    e.preventDefault();

    const player = template.content.cloneNode(true);
    const iframe = player.querySelector('iframe');
    play.replaceWith(player);
    template.remove();

    // Keep keyboard focus where the button was rather than dropping it.
    if (iframe) iframe.focus();
  });
}
