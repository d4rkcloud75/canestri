/* Video YouTube: al clic sulla copertina carica il lettore youtube-nocookie al suo posto.
   Stampato in linea nel footer: un file esterno verrebbe ritardato da Velox DelayJS. */
(function () {
	document.addEventListener('click', function (e) {
		var btn = e.target.closest ? e.target.closest('.cn-video__play') : null;
		if (!btn) { return; }
		e.preventDefault();
		var box = btn.parentNode, id = btn.getAttribute('data-yt'), title = btn.getAttribute('data-title') || '';
		if (!id || box.classList.contains('is-playing')) { return; }
		var f = document.createElement('iframe');
		f.src = 'https://www.youtube-nocookie.com/embed/' + encodeURIComponent(id) + '?autoplay=1&playsinline=1&rel=0';
		f.title = title;
		f.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
		f.allowFullscreen = true;
		box.classList.add('is-playing');
		btn.remove();
		box.appendChild(f);
		if (typeof window.gtag === 'function') {
			window.gtag('event', 'video_start', { video_provider: 'youtube', video_title: title, video_url: 'https://youtu.be/' + id });
		}
	});
})();
