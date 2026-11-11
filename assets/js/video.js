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
/* Slider dei video in home: frecce, pallini e stato in base allo scorrimento. */
(function () {
	var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	document.querySelectorAll('.cn-slider').forEach(function (box) {
		var list = box.querySelector('.cn-video--reel'), prev = box.querySelector('.cn-slider__btn--prev'), next = box.querySelector('.cn-slider__btn--next');
		var dots = box.nextElementSibling && box.nextElementSibling.classList.contains('cn-slider__dots') ? box.nextElementSibling : null;
		if (!list) { return; }
		var cards = Array.prototype.slice.call(list.children);
		function go(i) {
			i = Math.max(0, Math.min(cards.length - 1, i));
			list.scrollTo({ left: cards[i].offsetLeft - list.offsetLeft, behavior: reduce ? 'auto' : 'smooth' });
		}
		function current() {
			var x = list.scrollLeft, best = 0;
			cards.forEach(function (c, i) { if (Math.abs(c.offsetLeft - list.offsetLeft - x) < Math.abs(cards[best].offsetLeft - list.offsetLeft - x)) { best = i; } });
			return best;
		}
		function step() {
			var w = cards[0] ? cards[0].getBoundingClientRect().width : 1;
			return Math.max(1, Math.floor((list.clientWidth + 10) / w));
		}
		if (dots) {
			cards.forEach(function (c, i) {
				var d = document.createElement('button');
				d.type = 'button';
				d.setAttribute('aria-label', 'Video ' + (i + 1));
				d.addEventListener('click', function () { go(i); });
				dots.appendChild(d);
			});
		}
		function update() {
			var max = list.scrollWidth - list.clientWidth - 2, i = current();
			if (prev) { prev.disabled = list.scrollLeft <= 2; }
			if (next) { next.disabled = list.scrollLeft >= max; }
			if (dots) { Array.prototype.forEach.call(dots.children, function (d, k) { d.setAttribute('aria-current', k === i ? 'true' : 'false'); }); }
		}
		if (prev) { prev.addEventListener('click', function () { go(current() - step()); }); }
		if (next) { next.addEventListener('click', function () { go(current() + step()); }); }
		var t;
		list.addEventListener('scroll', function () { clearTimeout(t); t = setTimeout(update, 60); }, { passive: true });
		window.addEventListener('resize', update);
		update();
	});
})();
