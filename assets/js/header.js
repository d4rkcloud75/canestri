(function () {
	var root = document.documentElement;
	var ticking = false;
	// Isteresi: evita che l'header "tremi" vicino alla soglia mentre cambia altezza.
	var on = 90;
	var off = 20;
	function update() {
		var y = window.scrollY;
		var isScrolled = root.classList.contains('canestri-scrolled');
		if (!isScrolled && y > on) {
			root.classList.add('canestri-scrolled');
		} else if (isScrolled && y < off) {
			root.classList.remove('canestri-scrolled');
		}
		ticking = false;
	}
	window.addEventListener('scroll', function () {
		if (!ticking) {
			window.requestAnimationFrame(update);
			ticking = true;
		}
	}, { passive: true });
	update();
})();
