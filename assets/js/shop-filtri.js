/* Shop su mobile: pulsante "Mostra filtri" con barra agganciata sotto l'header.
   Il tema lo scrive IN LINEA nel footer: come file esterno Velox DelayJS lo ritarderebbe fino alla prima interazione. */
(function () {
	var mq = window.matchMedia('(max-width: 767px)');
	var filters = document.querySelector('.yith-wcan-filters');
	// Se lo snippet di Code Snippets è ancora attivo ha già creato il pulsante: non lo duplichiamo.
	if (!filters || document.querySelector('.cn-filtri-bar')) { return; }
	var panel = filters.closest('.elementor-widget') || filters;
	var header = document.querySelector('header.wp-block-template-part');

	var ph = document.createElement('div');
	ph.className = 'cn-filtri-ph';
	var bar = document.createElement('div');
	bar.className = 'cn-filtri-bar';
	bar.innerHTML = '<button type="button" class="cn-filtri-btn" aria-expanded="false"><span>Mostra filtri</span></button>';
	panel.parentNode.insertBefore(ph, panel);
	panel.parentNode.insertBefore(bar, panel);
	panel.classList.add('cn-filtri-panel');
	// Il contenitore Elementor ha uno spazio (gap) tra gli elementi: il segnaposto lo annulla.
	function gap() { return parseFloat(getComputedStyle(ph.parentNode).rowGap) || 0; }
	ph.style.marginBottom = -gap() + 'px';
	document.documentElement.classList.add('cn-filtri-ready');

	var btn = bar.querySelector('button');
	var label = btn.querySelector('span');

	function setOpen(open) {
		bar.classList.toggle('is-open', open);
		panel.classList.toggle('is-open', open);
		btn.setAttribute('aria-expanded', open ? 'true' : 'false');
		label.textContent = open ? 'Nascondi filtri' : 'Mostra filtri';
		update();
	}

	btn.addEventListener('click', function () {
		setOpen(!panel.classList.contains('is-open'));
	});

	function headerBottom() {
		if (!header) { return 0; }
		return Math.max(0, Math.round(header.getBoundingClientRect().bottom));
	}

	function update() {
		if (!mq.matches) {
			bar.classList.remove('is-fixed');
			panel.classList.remove('is-fixed');
			ph.style.height = '';
			return;
		}
		var top = headerBottom();
		var products = document.querySelector('ul.products');
		var end = products ? products.getBoundingClientRect().bottom : Infinity;
		var fix = ph.getBoundingClientRect().top < top && end > top + bar.offsetHeight + 40;
		if (fix) {
			if (!bar.classList.contains('is-fixed')) {
				ph.style.height = (bar.offsetHeight + gap()) + 'px';
				bar.classList.add('is-fixed');
			}
			bar.style.top = top + 'px';
			panel.classList.add('is-fixed');
			panel.style.top = (top + bar.offsetHeight) + 'px';
			panel.style.maxHeight = Math.max(200, window.innerHeight - top - bar.offsetHeight - 80) + 'px';
		} else {
			bar.classList.remove('is-fixed');
			panel.classList.remove('is-fixed');
			bar.style.top = '';
			panel.style.top = '';
			panel.style.maxHeight = '';
			ph.style.height = '';
		}
	}

	var ticking = false;
	function onScroll() {
		if (ticking) { return; }
		ticking = true;
		requestAnimationFrame(function () { ticking = false; update(); });
	}
	window.addEventListener('scroll', onScroll, { passive: true });
	window.addEventListener('resize', onScroll);
	if (mq.addEventListener) { mq.addEventListener('change', update); }

	// Dopo un filtro applicato con ajax: chiudi il pannello e torna alle auto.
	if (window.jQuery) {
		window.jQuery(document).on('yith-wcan-ajax-filtered', function () {
			if (!mq.matches || !panel.classList.contains('is-open')) { return; }
			setOpen(false);
			var products = document.querySelector('ul.products');
			if (products) {
				var y = products.getBoundingClientRect().top + window.scrollY - headerBottom() - bar.offsetHeight - 16;
				window.scrollTo({ top: y, behavior: 'smooth' });
			}
		});
	}

	update();
})();
