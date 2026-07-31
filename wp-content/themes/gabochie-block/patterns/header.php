<?php
/**
 * Title: Header
 * Slug: gabochie-block/header
 * Categories: gabochie
 * Description: Site header with logo, navigation, and CTA.
 * Keywords: header, navigation, menu
 */
?>
<!-- wp:group {"tagName":"header","className":"site-header","style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"},"margin":{"top":"0","bottom":"0"}},"position":{"type":"sticky","top":"0px"}},"backgroundColor":"white","textColor":"charcoal","layout":{"type":"constrained","contentSize":"1200px"}} -->
<header class="wp-block-group site-header has-white-background-color has-charcoal-color has-text-color has-background" style="margin-top:0;margin-bottom:0;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">

<!-- wp:group {"className":"header-inner","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between","verticalAlignment":"center"}} -->
<div class="wp-block-group header-inner" style="padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--50)">

<!-- wp:group {"className":"site-branding","layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
<div class="wp-block-group site-branding">

<!-- wp:site-logo {"width":40} /-->

<!-- wp:group {"className":"brand-text","layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
<div class="wp-block-group brand-text">

<!-- wp:site-title {"level":0,"style":{"typography":{"fontSize":"1.25rem","fontWeight":"700","letterSpacing":"0.05em"}},"fontFamily":"playfair"} /-->

<!-- wp:paragraph {"className":"brand-tagline","style":{"typography":{"fontSize":"0.7rem","fontWeight":"300","letterSpacing":"0.1em","textTransform":"uppercase"}},"textColor":"mist"} -->
<p class="brand-tagline" style="font-size:0.7rem;font-weight:300;letter-spacing:0.1em;text-transform:uppercase">AI-Powered Architecture</p>
<!-- /wp:paragraph -->

</div>
<!-- /wp:group -->

</div>
<!-- /wp:group -->

<!-- wp:navigation {"className":"main-navigation","style":{"spacing":{"blockGap":"var:preset|spacing|50"},"typography":{"fontSize":"0.875rem","fontWeight":"500"}},"fontFamily":"jost","layout":{"type":"flex","justifyContent":"right"}} -->
<!-- wp:navigation-link {"label":"Services","url":"#services","kind":"custom","isTopLevelLink":true} /-->
<!-- wp:navigation-link {"label":"Process","url":"#process","kind":"custom","isTopLevelLink":true} /-->
<!-- wp:navigation-link {"label":"About","url":"#about","kind":"custom","isTopLevelLink":true} /-->
<!-- wp:navigation-link {"label":"Pricing","url":"#pricing","kind":"custom","isTopLevelLink":true} /-->
<!-- wp:navigation-link {"label":"Blog","url":"/blog","kind":"custom","isTopLevelLink":true} /-->
<!-- wp:navigation-link {"label":"Book Consultation","url":"/contact","kind":"custom","isTopLevelLink":true,"className":"nav-cta-button","style":{"elements":{"link":{"color":{"text":"var:preset|color|white"}}},"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"backgroundColor":"teal","textColor":"white"} /-->
<!-- /wp:navigation -->

</div>
<!-- /wp:group -->

</header>
<!-- /wp:group -->
