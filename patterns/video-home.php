<?php
/**
 * Title: Canestri: sezione video (home)
 * Slug: canestri/video-home
 * Categories: featured
 * Keywords: video, youtube, shorts
 * Description: Shorts di YouTube in fila con pulsanti verso la pagina /video/ e il canale.
 */
?>
<!-- wp:group {"anchor":"video","className":"canestri-section canestri-video","layout":{"type":"constrained","contentSize":"1200px"}} -->
<div id="video" class="wp-block-group canestri-section canestri-video"><!-- wp:paragraph {"className":"canestri-eyebrow"} -->
<p class="canestri-eyebrow">Video</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"className":"canestri-limo-h2"} -->
<h2 class="wp-block-heading canestri-limo-h2">Le nostre auto in pochi secondi</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"cn-video-lead"} -->
<p class="cn-video-lead">Interni, luci, dettagli e le risposte alle domande che ci fate più spesso.</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[canestri_video gruppo="shorts" max="6" layout="reel"]
<!-- /wp:shortcode -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"left"},"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--40)"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/video/">Guarda tutti i video</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="https://www.youtube.com/@andrea1972roma" target="_blank" rel="noreferrer noopener">Iscriviti al canale YouTube</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->
