<?php
/**
 * Canestri: tema figlio di Twenty Twenty-Five.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_enqueue_scripts',
	function () {
		$version = wp_get_theme()->get( 'Version' );
		wp_enqueue_style( 'canestri-style', get_stylesheet_uri(), array(), $version );
		wp_enqueue_script(
			'canestri-header',
			get_stylesheet_directory_uri() . '/assets/js/header.js',
			array(),
			$version,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}
);

/**
 * Con questo tema i modelli del Theme Builder di Elementor Pro non sostituiscono
 * i modelli a blocchi (articoli, pagine, prodotti).
 */
add_filter( 'elementor/theme/need_override_location', '__return_false' );

/**
 * Le pagine costruite con Elementor usano i modelli "Elementor Canvas" o
 * "Elementor a tutta larghezza", che non stampano header e footer di un tema a blocchi.
 * Solo in visualizzazione le mostriamo con il modello predefinito del tema, senza toccare il database.
 */
add_filter(
	'get_post_metadata',
	function ( $value, $object_id, $meta_key, $single ) {
		if ( '_wp_page_template' !== $meta_key || is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return $value;
		}
		static $busy = false;
		if ( $busy ) {
			return $value;
		}
		$busy     = true;
		$template = get_post_meta( $object_id, '_wp_page_template', true );
		$busy     = false;
		if ( in_array( $template, array( 'elementor_header_footer', 'elementor_canvas', 'elementor_theme' ), true ) ) {
			return $single ? 'default' : array( 'default' );
		}
		return $value;
	},
	10,
	4
);

/**
 * Scheda auto: etichetta tipologia, dettagli e pulsanti di contatto.
 */
function canestri_current_product() {
	if ( ! function_exists( 'wc_get_product' ) ) {
		return null;
	}
	$product = wc_get_product( get_the_ID() );
	return $product ? $product : null;
}

add_shortcode(
	'canestri_tipologia',
	function () {
		$product = canestri_current_product();
		if ( ! $product ) {
			return '';
		}
		$value = $product->get_attribute( 'pa_1-tipologia' );
		if ( ! $value ) {
			$terms = get_the_terms( $product->get_id(), 'product_cat' );
			if ( $terms && ! is_wp_error( $terms ) ) {
				$value = end( $terms )->name;
			}
		}
		return $value ? '<p class="canestri-eyebrow">' . esc_html( $value ) . '</p>' : '';
	}
);

add_shortcode(
	'canestri_specs',
	function () {
		$product = canestri_current_product();
		if ( ! $product ) {
			return '';
		}
		$rows = '';
		foreach ( $product->get_attributes() as $attribute ) {
			if ( ! $attribute->get_visible() ) {
				continue;
			}
			$name = wc_attribute_label( $attribute->get_name(), $product );
			if ( false !== stripos( $name, 'tipologia' ) ) {
				continue;
			}
			$value = $attribute->is_taxonomy()
				? implode( ', ', wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'names' ) ) )
				: implode( ', ', $attribute->get_options() );
			if ( '' === trim( $value ) ) {
				continue;
			}
			$rows .= '<div class="canestri-spec"><dt>' . esc_html( $name ) . '</dt><dd>' . esc_html( $value ) . '</dd></div>';
		}
		return $rows ? '<dl class="canestri-specs">' . $rows . '</dl>' : '';
	}
);

add_shortcode(
	'canestri_cta',
	function () {
		$product = canestri_current_product();
		$name    = $product ? $product->get_name() : get_the_title();
		$text    = rawurlencode( 'Buongiorno, vorrei un preventivo per ' . wp_strip_all_tags( $name ) . '. Data: ' );
		$out     = '<div class="wp-block-buttons canestri-cta-buttons">';
		$out    .= '<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="https://wa.me/393408378262?text=' . $text . '">Chiedi su WhatsApp</a></div>';
		$out    .= '<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="tel:+390690622821">Chiama 06 9062 2821</a></div>';
		$out    .= '</div><p class="canestri-cta-link"><a href="' . esc_url( home_url( '/contact/' ) ) . '">Oppure compila il modulo di contatto</a></p>';
		return $out;
	}
);

/**
 * Rete di sicurezza: se Elementor o Elementor Pro (Theme Builder, modelli
 * "Canvas" o "a tutta larghezza", modelli prodotto) sostituiscono il file di
 * template, torniamo al modello a blocchi che WordPress ha già scelto per la pagina.
 */
add_filter(
	'template_include',
	function ( $template ) {
		global $_wp_current_template_content;
		if ( is_admin() || empty( $_wp_current_template_content ) ) {
			return $template;
		}
		$normalized = wp_normalize_path( (string) $template );
		if ( false !== strpos( $normalized, '/plugins/elementor' ) ) {
			return ABSPATH . WPINC . '/template-canvas.php';
		}
		return $template;
	},
	PHP_INT_MAX
);

/**
 * Pagine costruite con Elementor: modello largo senza sidebar, come sul sito attuale.
 */
function canestri_is_elementor_page( $post_id ) {
	return 'builder' === get_post_meta( $post_id, '_elementor_edit_mode', true );
}

add_filter(
	'page_template_hierarchy',
	function ( $templates ) {
		$id = get_queried_object_id();
		$slug = get_post_field( 'post_name', $id );
		if ( $slug && file_exists( get_stylesheet_directory() . '/templates/page-' . $slug . '.html' ) ) {
			return $templates;
		}
		if ( $id && ! is_front_page() && canestri_is_elementor_page( $id ) && in_array( get_page_template_slug( $id ), array( '', 'default' ), true ) ) {
			array_unshift( $templates, 'page-elementor.php' );
		}
		return $templates;
	}
);

/**
 * Un solo H1 per pagina: se il contenuto Elementor ha già un H1 non stampiamo il titolo.
 * Il titolo inglese "About us" viene mostrato in italiano.
 */
add_filter(
	'render_block_core/post-title',
	function ( $html, $block ) {
		if ( is_admin() || ! is_singular() || empty( $block['attrs']['level'] ) || 1 !== (int) $block['attrs']['level'] ) {
			return $html;
		}
		$id = get_queried_object_id();
		if ( ! $id ) {
			return $html;
		}
		if ( canestri_is_elementor_page( $id ) ) {
			$data = (string) get_post_meta( $id, '_elementor_data', true );
			if ( false !== strpos( $data, '"header_size":"h1"' ) || false !== stripos( $data, '<h1' ) ) {
				return '';
			}
		}
		if ( 'about-us' === get_post_field( 'post_name', $id ) ) {
			$html = str_replace( '>About us<', '>Chi siamo<', $html );
		}
		return $html;
	},
	10,
	2
);

/**
 * Prestazioni: carichiamo gli script dei plugin solo dove servono.
 */
/** Percorso del modello dedicato alla pagina (templates/page-{slug}.html), se esiste. */
function canestri_slug_template() {
	if ( ! is_page() ) {
		return '';
	}
	$slug = get_post_field( 'post_name', get_queried_object_id() );
	$file = get_stylesheet_directory() . '/templates/page-' . $slug . '.html';
	return ( $slug && file_exists( $file ) ) ? $file : '';
}

function canestri_page_has_form() {
	if ( is_page( 'contact' ) || is_front_page() ) {
		return true;
	}
	$tpl = canestri_slug_template();
	if ( $tpl ) {
		return false !== strpos( (string) file_get_contents( $tpl ), 'contact-form-7' );
	}
	if ( ! is_singular() ) {
		return false;
	}
	$id      = get_queried_object_id();
	$haystack = (string) get_post_field( 'post_content', $id ) . (string) get_post_meta( $id, '_elementor_data', true );
	return ( false !== strpos( $haystack, 'contact-form-7' ) || false !== strpos( $haystack, 'wpcf7' ) );
}

function canestri_trim_assets() {
	if ( is_admin() ) {
		return;
	}
	$styles  = array( 'sb-elementor-editor', 'wpb-get-a-quote-button-sweetalert2', 'wpb-get-a-quote-button-styles' );
	$scripts = array( 'wpb-get-a-quote-button-sweetalert2', 'wpb-get-a-quote-button-scripts' );
	if ( ! canestri_page_has_form() ) {
		$styles  = array_merge( $styles, array( 'contact-form-7', 'walcf7-datepicker-css' ) );
		$scripts = array_merge( $scripts, array( 'contact-form-7', 'walcf7-datepicker-js', 'walcf7-datepicker', 'google-recaptcha', 'wpcf7-recaptcha' ) );
	}
	foreach ( $styles as $h ) {
		wp_dequeue_style( $h );
	}
	foreach ( $scripts as $h ) {
		wp_dequeue_script( $h );
	}
}
add_action( 'wp_enqueue_scripts', 'canestri_trim_assets', 999 );
add_action( 'wp_print_footer_scripts', 'canestri_trim_assets', 1 );

/**
 * Prestazioni: niente file di Elementor e dei suoi addon nelle pagine che non sono fatte con Elementor.
 */
function canestri_strip_elementor_assets() {
	if ( is_admin() ) {
		return;
	}
	if ( is_singular() && ! is_front_page() && ! canestri_slug_template() && canestri_is_elementor_page( get_queried_object_id() ) ) {
		return;
	}
	if ( is_singular( 'product' ) ) {
		return;
	}
	$pattern = '#/(plugins/(elementor|elementor-pro|pro-elements|happy-elementor-addons)|uploads/elementor)/#';
	foreach ( array( wp_styles(), wp_scripts() ) as $deps ) {
		foreach ( (array) $deps->queue as $handle ) {
			$src = isset( $deps->registered[ $handle ] ) ? (string) $deps->registered[ $handle ]->src : '';
			if ( $src && preg_match( $pattern, $src ) ) {
				if ( $deps instanceof WP_Styles ) {
					wp_dequeue_style( $handle );
				} else {
					wp_dequeue_script( $handle );
				}
			}
		}
	}
	wp_dequeue_style( 'sb-elementor-shared-style' );
	foreach ( (array) wp_styles()->queue as $handle ) {
		if ( 0 === strpos( $handle, 'elementor-gf-' ) ) {
			wp_dequeue_style( $handle );
		}
	}
}
add_action( 'wp_print_styles', 'canestri_strip_elementor_assets', 1 );
add_action( 'wp_enqueue_scripts', 'canestri_strip_elementor_assets', 1000 );
add_action( 'wp_print_footer_scripts', 'canestri_strip_elementor_assets', 1 );

/**
 * Prestazioni: tutti gli script del front end in modalità "defer".
 * WordPress la applica solo dove l'ordine delle dipendenze lo consente.
 */
add_action(
	'wp_print_scripts',
	function () {
		if ( is_admin() ) {
			return;
		}
		$scripts = wp_scripts();
		foreach ( (array) $scripts->queue as $handle ) {
			$todo = array( $handle );
			while ( $todo ) {
				$h = array_pop( $todo );
				if ( in_array( $h, array( 'jquery', 'jquery-core', 'jquery-migrate' ), true ) || empty( $scripts->registered[ $h ] ) || $scripts->get_data( $h, 'strategy' ) ) {
					continue;
				}
				$scripts->add_data( $h, 'strategy', 'defer' );
				$todo = array_merge( $todo, (array) $scripts->registered[ $h ]->deps );
			}
		}
	},
	1
);

/** Solo la prima immagine (la foto di apertura) viene caricata subito, le altre in lazy load. */
add_filter( 'wp_omit_loading_attr_threshold', function () { return 1; } );

/** Dimensioni corrette per le immagini dei riquadri categoria, così il telefono scarica file piccoli. */
/**
 * Foto della dimensione giusta: l'attributo sizes dice al browser quanto è larga davvero
 * l'immagine, così da telefono scarica la versione piccola e non quella da 1024 px.
 */
function canestri_set_sizes( $html, $sizes ) {
	return preg_replace_callback(
		'/<img\b[^>]*>/i',
		function ( $m ) use ( $sizes ) {
			$tag = $m[0];
			if ( false === strpos( $tag, 'srcset=' ) ) {
				/* srcset arriva dopo (wp_filter_content_tags): lasciamo un segnaposto letto da wp_content_img_tag. */
				return preg_replace( '/^<img\b/i', '<img data-csizes="' . esc_attr( $sizes ) . '"', $tag, 1 );
			}
			$auto = preg_match( '/sizes="auto,/', $tag ) ? 'auto, ' : '';
			return preg_replace( '/\ssizes="[^"]*"/', ' sizes="' . $auto . $sizes . '"', $tag, 1 );
		},
		$html
	);
}
add_filter(
	'wp_content_img_tag',
	function ( $tag ) {
		if ( ! preg_match( '/\sdata-csizes="([^"]*)"/', $tag, $m ) ) {
			return $tag;
		}
		$tag = str_replace( $m[0], '', $tag );
		if ( false === strpos( $tag, 'srcset=' ) ) {
			return $tag;
		}
		$auto = preg_match( '/sizes="auto,/', $tag ) ? 'auto, ' : '';
		return preg_replace( '/\ssizes="[^"]*"/', ' sizes="' . $auto . $m[1] . '"', $tag, 1 );
	},
	20
);
add_filter(
	'render_block',
	function ( $html, $block ) {
		if ( is_admin() || empty( $block['blockName'] ) ) {
			return $html;
		}
		$class = isset( $block['attrs']['className'] ) ? (string) $block['attrs']['className'] : '';
		switch ( $block['blockName'] ) {
			case 'woocommerce/product-template':
			case 'core/post-template':
				return canestri_set_sizes( $html, '(max-width: 600px) 88vw, (max-width: 781px) 45vw, 390px' );
			case 'core/gallery':
				return canestri_set_sizes( $html, '(max-width: 781px) 46vw, 300px' );
			case 'core/group':
				if ( preg_match( '/(^|\s)canestri-gallery(\s|$)/', $class ) ) {
					return canestri_set_sizes( $html, '(max-width: 781px) 46vw, 300px' );
				}
				if ( false !== strpos( $class, 'canestri-occ-grid' ) ) {
					return canestri_set_sizes( $html, '(max-width: 781px) 46vw, 390px' );
				}
				if ( false !== strpos( $class, 'canestri-about-sec' ) ) {
					return canestri_set_sizes( $html, '(max-width: 781px) 92vw, 520px' );
				}
				break;
		}
		return $html;
	},
	20,
	2
);
add_filter(
	'render_block_core/cover',
	function ( $html, $block ) {
		$class = isset( $block['attrs']['className'] ) ? $block['attrs']['className'] : '';
		$id    = isset( $block['attrs']['id'] ) ? (int) $block['attrs']['id'] : 0;
		if ( $id && false !== strpos( $class, 'canestri-cat-card' ) ) {
			$srcset = wp_get_attachment_image_srcset( $id, 'large' );
			if ( $srcset ) {
				$html = preg_replace( '/<img /', '<img srcset="' . esc_attr( $srcset ) . '" sizes="(max-width: 781px) 50vw, 300px" ', $html, 1 );
			}
		}
		if ( false !== strpos( $class, 'canestri-occ' ) ) {
			$html = canestri_set_sizes( $html, '(max-width: 781px) 46vw, 390px' );
		}
		return $html;
	},
	20,
	2
);

/** Fogli di stile dell'editor che alcuni plugin caricano anche sul sito pubblico. */
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! is_user_logged_in() && ! ( function_exists( 'is_checkout' ) && is_checkout() ) ) {
			wp_dequeue_style( 'wp-components' );
		}
	},
	1001
);

/** Toglie dal sito pubblico tutti i file di un plugin, in base al percorso. */
function canestri_strip_by_path( $pattern ) {
	foreach ( array( wp_styles(), wp_scripts() ) as $deps ) {
		foreach ( (array) $deps->queue as $handle ) {
			$src = isset( $deps->registered[ $handle ] ) ? (string) $deps->registered[ $handle ]->src : '';
			if ( $src && preg_match( $pattern, $src ) ) {
				if ( $deps instanceof WP_Styles ) {
					wp_dequeue_style( $handle );
				} else {
					wp_dequeue_script( $handle );
				}
			}
		}
	}
}

/** Niente widget chat flottante (Chaty) con questo tema: c'è già la barra WhatsApp e Chiama. */
function canestri_strip_chaty() {
	if ( ! is_admin() ) {
		canestri_strip_by_path( '#/plugins/chaty/#' );
	}
}
add_action( 'wp_enqueue_scripts', 'canestri_strip_chaty', 1002 );
add_action( 'wp_print_footer_scripts', 'canestri_strip_chaty', 1 );

/** Niente icone carrello e account che WooCommerce aggiunge da solo all'header. */
add_filter(
	'hooked_block_types',
	function ( $hooked, $position, $anchor, $context ) {
		return array_values( array_diff( (array) $hooked, array( 'woocommerce/mini-cart', 'woocommerce/customer-account' ) ) );
	},
	99,
	4
);
add_filter( 'render_block_woocommerce/mini-cart', function ( $html ) { return is_admin() ? $html : ''; } );
add_filter( 'render_block_woocommerce/customer-account', function ( $html ) { return is_admin() ? $html : ''; } );

/**
 * Pagina FAQ: dati strutturati FAQPage e testi SEO (se non impostati in Yoast).
 */
add_action(
	'wp_head',
	function () {
		if ( ! is_page( 'faq' ) ) {
			return;
		}
		$file = get_stylesheet_directory() . '/assets/faq-schema.json';
		if ( file_exists( $file ) ) {
			echo '<script type="application/ld+json">' . file_get_contents( $file ) . '</script>' . "\n"; // phpcs:ignore
		}
	}
);
add_filter(
	'wpseo_metadesc',
	function ( $desc ) {
		if ( is_page( 'faq' ) && '' === trim( (string) $desc ) ) {
			return 'Quanto costa un\'auto d\'epoca per il matrimonio a Roma, autista, nastri e fiori, prenotazione: tutte le risposte di Canestri Autonoleggi. Preventivo gratuito in giornata.';
		}
		return $desc;
	}
);
add_filter(
	'wpseo_title',
	function ( $title ) {
		if ( is_page( 'faq' ) ) {
			return 'Domande frequenti sul noleggio auto per matrimoni a Roma | Canestri Autonoleggi';
		}
		return $title;
	}
);

/**
 * CSS del tema in linea, compresso e con le sole sezioni utili alla pagina:
 * i blocchi segnati con un marcatore nel foglio di stile (prodotto, contatti, Elementor, FAQ)
 * vengono scritti solo dove servono.
 */
function canestri_page_css( $file ) {
	$id   = get_queried_object_id();
	$keep = array(
		'product'   => is_singular( 'product' ),
		'contact'   => is_page( 'contact' ) || is_page( 'contatti' ) || ( $id && 'page-contact' === get_page_template_slug( $id ) ),
		'elementor' => is_singular( 'product' ) || ( is_singular() && ! is_front_page() && ! canestri_slug_template() && canestri_is_elementor_page( $id ) ),
		'faq'       => is_page( 'faq' ),
	);
	$key = 'canestri_css_' . md5( filemtime( $file ) . wp_json_encode( $keep ) );
	$css = get_transient( $key );
	if ( false !== $css ) {
		return $css;
	}
	$css = (string) file_get_contents( $file );
	$css = preg_replace_callback(
		'#/\*@(\w+)\*/(.*?)/\*@end\*/#s',
		function ( $m ) use ( $keep ) {
			return ! empty( $keep[ $m[1] ] ) ? $m[2] : '';
		},
		$css
	);
	$css = preg_replace( '#/\*.*?\*/#s', '', $css );
	$css = preg_replace( '/\s+/', ' ', $css );
	$css = preg_replace( '/\s*([{};,>])\s*/', '$1', $css );
	$css = str_replace( ';}', '}', $css );
	$css = trim( $css );
	set_transient( $key, $css, WEEK_IN_SECONDS );
	return $css;
}

/**
 * Prestazioni: meno file che bloccano la prima visualizzazione.
 * - gli stili dei blocchi e il foglio di stile del tema vengono scritti dentro la pagina invece che caricati a parte;
 * - jQuery Migrate (serve solo a vecchi plugin) non viene caricato sul sito pubblico.
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		$file = get_stylesheet_directory() . '/style.css';
		if ( is_readable( $file ) ) {
			wp_dequeue_style( 'canestri-style' );
			wp_register_style( 'canestri-inline', false, array(), null );
			wp_enqueue_style( 'canestri-inline' );
			wp_add_inline_style( 'canestri-inline', canestri_page_css( $file ) );
		}
		/* jQuery e il banner cookie in fondo alla pagina: non bloccano più la prima visualizzazione. */
		foreach ( array( 'jquery', 'jquery-core', 'cookie-law-info' ) as $h ) {
			wp_script_add_data( $h, 'group', 1 );
		}
	},
	20
);
add_action(
	'wp_default_scripts',
	function ( $scripts ) {
		if ( is_admin() || empty( $scripts->registered['jquery'] ) ) {
			return;
		}
		$scripts->registered['jquery']->deps = array_diff( (array) $scripts->registered['jquery']->deps, array( 'jquery-migrate' ) );
	}
);

/** Tutti gli script che dipendono da jQuery vanno nel footer, così jQuery non blocca l'intestazione. */
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( is_admin() ) {
			return;
		}
		$scripts = wp_scripts();
		$needs   = function ( $h, $seen = array() ) use ( &$needs, $scripts ) {
			if ( in_array( $h, $seen, true ) || empty( $scripts->registered[ $h ] ) ) {
				return false;
			}
			$seen[] = $h;
			foreach ( (array) $scripts->registered[ $h ]->deps as $d ) {
				if ( in_array( $d, array( 'jquery', 'jquery-core' ), true ) || $needs( $d, $seen ) ) {
					return true;
				}
			}
			return false;
		};
		foreach ( array_keys( $scripts->registered ) as $h ) {
			if ( $needs( $h ) ) {
				$scripts->add_data( $h, 'group', 1 );
			}
		}
		foreach ( array( 'jquery', 'jquery-core' ) as $h ) {
			$scripts->add_data( $h, 'group', 1 );
		}
	},
	1003
);

/** jQuery stampato all'inizio del footer, prima degli script in linea dei plugin che lo usano. */
add_action(
	'wp_footer',
	function () {
		if ( ! is_admin() && ! wp_script_is( 'jquery', 'done' ) ) {
			wp_print_scripts( array( 'jquery' ) );
		}
	},
	1
);

/**
 * I pochi fogli dei blocchi che bloccavano la prima visualizzazione (copertina, menu,
 * icone social, immagine prodotto) vengono scritti dentro la pagina: sono piccoli
 * e così il browser non aspetta altre richieste prima di disegnare l'hero.
 */
add_filter(
	'style_loader_tag',
	function ( $tag, $handle, $href ) {
		if ( is_admin() || ! $href || ! preg_match( '#/(blocks/(cover|navigation|social-links)/style\.min\.css|product-image(\.min)?\.css)#', $href ) ) {
			return $tag;
		}
		$path = wp_parse_url( $href, PHP_URL_PATH );
		$file = $path ? ABSPATH . ltrim( $path, '/' ) : '';
		if ( ! $file || ! is_readable( $file ) ) {
			return $tag;
		}
		$css = (string) file_get_contents( $file );
		if ( '' === $css || false !== stripos( $css, 'url(' ) || strlen( $css ) > 20000 ) {
			return $tag;
		}
		return '<style id="' . esc_attr( $handle ) . '-css">' . $css . '</style>' . "\n";
	},
	20,
	3
);
