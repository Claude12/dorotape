-- =====================================================================
-- PART 1: Fix image/asset paths in product descriptions
-- =====================================================================

-- relative src
UPDATE wp_posts SET post_content = REPLACE(post_content, 'src="media/ecom/', 'src="/wp-content/uploads/kryptronic-media/media/ecom/') WHERE post_type = 'product' AND post_content LIKE '%src="media/ecom/%';

-- relative src single-quote
UPDATE wp_posts SET post_content = REPLACE(post_content, 'src=\'media/ecom/', 'src=\'/wp-content/uploads/kryptronic-media/media/ecom/') WHERE post_type = 'product' AND post_content LIKE '%src=\'media/ecom/%';

-- relative href
UPDATE wp_posts SET post_content = REPLACE(post_content, 'href="media/ecom/', 'href="/wp-content/uploads/kryptronic-media/media/ecom/') WHERE post_type = 'product' AND post_content LIKE '%href="media/ecom/%';

-- relative href single-quote
UPDATE wp_posts SET post_content = REPLACE(post_content, 'href=\'media/ecom/', 'href=\'/wp-content/uploads/kryptronic-media/media/ecom/') WHERE post_type = 'product' AND post_content LIKE '%href=\'media/ecom/%';

-- absolute src
UPDATE wp_posts SET post_content = REPLACE(post_content, 'src="https://www.dorotape.co.uk/media/ecom/', 'src="/wp-content/uploads/kryptronic-media/media/ecom/') WHERE post_type = 'product' AND post_content LIKE '%src="https://www.dorotape.co.uk/media/ecom/%';

-- absolute href
UPDATE wp_posts SET post_content = REPLACE(post_content, 'href="https://www.dorotape.co.uk/media/ecom/', 'href="/wp-content/uploads/kryptronic-media/media/ecom/') WHERE post_type = 'product' AND post_content LIKE '%href="https://www.dorotape.co.uk/media/ecom/%';

-- =====================================================================
-- PART 2: Append YouTube video iframes to descriptions
-- =====================================================================

-- SKU 054CGO
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '054CGO' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/NDnK2FgU5_I?si=s6llzMS8D3AmaTTD" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/NDnK2FgU5_%';

-- SKU 054CSI
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '054CSI' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/NDnK2FgU5_I?si=s6llzMS8D3AmaTTD" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/NDnK2FgU5_%';

-- SKU 056SGO
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '056SGO' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/NDnK2FgU5_I?si=s6llzMS8D3AmaTTD" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/NDnK2FgU5_%';

-- SKU 056SSI
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '056SSI' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/NDnK2FgU5_I?si=s6llzMS8D3AmaTTD" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/NDnK2FgU5_%';

-- SKU 12325
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '12325' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/SU-KJZmVA0w?si=CiP394Rv0H3aO_t0" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/SU-KJZmVA0%';

-- SKU 12332
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '12332' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/SU-KJZmVA0w?si=CiP394Rv0H3aO_t0" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/SU-KJZmVA0%';

-- SKU 12345
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '12345' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/SU-KJZmVA0w?si=CiP394Rv0H3aO_t0" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/SU-KJZmVA0%';

-- SKU 12348
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '12348' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/SU-KJZmVA0w?si=CiP394Rv0H3aO_t0" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/SU-KJZmVA0%';

-- SKU 12350
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '12350' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/SU-KJZmVA0w?si=CiP394Rv0H3aO_t0" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/SU-KJZmVA0%';

-- SKU 12351
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '12351' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/SU-KJZmVA0w?si=CiP394Rv0H3aO_t0" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/SU-KJZmVA0%';

-- SKU 12355
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '12355' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/SU-KJZmVA0w?si=CiP394Rv0H3aO_t0" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/SU-KJZmVA0%';

-- SKU 12356
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '12356' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/SU-KJZmVA0w?si=CiP394Rv0H3aO_t0" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/SU-KJZmVA0%';

-- SKU 12358
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '12358' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/SU-KJZmVA0w?si=CiP394Rv0H3aO_t0" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/SU-KJZmVA0%';

-- SKU 12381
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '12381' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/SU-KJZmVA0w?si=CiP394Rv0H3aO_t0" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/SU-KJZmVA0%';

-- SKU 12386
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '12386' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/SU-KJZmVA0w?si=CiP394Rv0H3aO_t0" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/SU-KJZmVA0%';

-- SKU 12388
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '12388' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/SU-KJZmVA0w?si=CiP394Rv0H3aO_t0" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/SU-KJZmVA0%';

-- SKU 12389
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '12389' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/SU-KJZmVA0w?si=CiP394Rv0H3aO_t0" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/SU-KJZmVA0%';

-- SKU 12390
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '12390' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/SU-KJZmVA0w?si=CiP394Rv0H3aO_t0" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/SU-KJZmVA0%';

-- SKU 13140S
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '13140S' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/bB3-nIfo2R0" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/bB3-nIfo2R%';

-- SKU 13141S
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '13141S' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/bB3-nIfo2R0" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/bB3-nIfo2R%';

-- SKU 18003
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '18003' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/Urm7X-3EAJ8" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/Urm7X-3EAJ%';

-- SKU 18004
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '18004' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/Urm7X-3EAJ8" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/Urm7X-3EAJ%';

-- SKU 18005
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '18005' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/Urm7X-3EAJ8" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/Urm7X-3EAJ%';

-- SKU 18006
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '18006' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/Urm7X-3EAJ8" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/Urm7X-3EAJ%';

-- SKU 18002
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '18002' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/Urm7X-3EAJ8" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/Urm7X-3EAJ%';

-- SKU 18001
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '18001' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/Urm7X-3EAJ8" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/Urm7X-3EAJ%';

-- SKU SL95
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'SL95' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/1RX_6_CrNkU" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/1RX_6_CrNk%';

-- SKU SL99
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'SL99' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/lbhVwVHt9to?si=_jJSdBsIoG8Hot_5" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/lbhVwVHt9t%';

-- SKU BB910
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'BB910' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/tfgn1aZ78t4" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/tfgn1aZ78t%';

-- SKU CR63
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'CR63' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/3h5zU6YiE4o" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/3h5zU6YiE4%';

-- SKU DFL200
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'DFL200' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/xC4wUhTUSd8" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/xC4wUhTUSd%';

-- SKU DFL300
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'DFL300' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/xC4wUhTUSd8" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/xC4wUhTUSd%';

-- SKU EL300
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'EL300' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/m18CYPe3oNA" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/m18CYPe3oN%';

-- SKU EL300
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'EL300' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/m18CYPe3oNA" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/m18CYPe3oN%';

-- SKU EL300
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'EL300' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/m18CYPe3oNA" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/m18CYPe3oN%';

-- SKU RP35
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'RP35' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/RdT8c42QEGY" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/RdT8c42QEG%';

-- SKU RP36
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'RP36' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/nDzSyQXqPvk" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/nDzSyQXqPv%';

-- SKU 13100S
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '13100S' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/3Ic4ejFu-_4?si=D4icU7dk2pgYipzt" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/3Ic4ejFu-_%';

-- SKU WBL995
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'WBL995' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/wvyWkAjAros" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/wvyWkAjAro%';

-- SKU 11355
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '11355' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/hAz28VdVrTQ" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/hAz28VdVrT%';

-- SKU 11356
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '11356' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/hAz28VdVrTQ" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/hAz28VdVrT%';

-- SKU 11357
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '11357' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/hAz28VdVrTQ" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/hAz28VdVrT%';

-- SKU 11359
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '11359' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/hAz28VdVrTQ" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/hAz28VdVrT%';

-- SKU 11360
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '11360' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/hAz28VdVrTQ" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/hAz28VdVrT%';

-- SKU 11364
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '11364' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/hAz28VdVrTQ" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/hAz28VdVrT%';

-- SKU 11368
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '11368' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/hAz28VdVrTQ" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/hAz28VdVrT%';

-- SKU 11369
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '11369' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/hAz28VdVrTQ" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/hAz28VdVrT%';

-- SKU 11371
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '11371' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/hAz28VdVrTQ" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/hAz28VdVrT%';

-- SKU 11373
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '11373' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/hAz28VdVrTQ" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/hAz28VdVrT%';

-- SKU 11374
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '11374' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/hAz28VdVrTQ" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/hAz28VdVrT%';

-- SKU 11377
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '11377' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/hAz28VdVrTQ" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/hAz28VdVrT%';

-- SKU 11379
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '11379' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/hAz28VdVrTQ" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/hAz28VdVrT%';

-- SKU 11381
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '11381' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/hAz28VdVrTQ" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/hAz28VdVrT%';

-- SKU 11382
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '11382' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/hAz28VdVrTQ" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/hAz28VdVrT%';

-- SKU 11383
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '11383' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/hAz28VdVrTQ" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/hAz28VdVrT%';

-- SKU 11385
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '11385' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/hAz28VdVrTQ" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/hAz28VdVrT%';

-- SKU 11388
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '11388' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/hAz28VdVrTQ" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/hAz28VdVrT%';

-- SKU 11392
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '11392' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/hAz28VdVrTQ" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/hAz28VdVrT%';

-- SKU 11395
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '11395' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/hAz28VdVrTQ" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/hAz28VdVrT%';

-- SKU DFP06
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'DFP06' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/wtZzRSdsLa8" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/wtZzRSdsLa%';

-- SKU DFP07
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'DFP07' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/wtZzRSdsLa8" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/wtZzRSdsLa%';

-- SKU DFP08
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'DFP08' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/wtZzRSdsLa8" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/wtZzRSdsLa%';

-- SKU DFP10
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'DFP10' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/43NngIrzK0w?si=jddL_GeoEbq4Orj7" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/43NngIrzK0%';

-- SKU DFP45
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'DFP45' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/5e7WarLLoWk" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/5e7WarLLoW%';

-- SKU DFP46
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'DFP46' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/xanDU714V6A" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/xanDU714V6%';

-- SKU DFP49
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'DFP49' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/VjkQoS1YsIQ" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/VjkQoS1YsI%';

-- SKU PTF
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'PTF' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/BRLnyKJU_vU" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/BRLnyKJU_v%';

-- SKU PTF
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'PTF' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/BRLnyKJU_vU" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/BRLnyKJU_v%';

-- SKU PTF
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'PTF' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/BRLnyKJU_vU" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/BRLnyKJU_v%';

-- SKU DFP25
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'DFP25' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/7FP4bisPT2o" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/7FP4bisPT2%';

-- SKU EDGE2
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'EDGE2' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/8DCHzx1M-3Y" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/8DCHzx1M-3%';

-- SKU EDGETEX
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'EDGETEX' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/Ci8ruBA2TEw" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/Ci8ruBA2TE%';

-- SKU FJ115
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'FJ115' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/vUqHuzPEYis?si=lFKniZmlH_3fxLQg" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/vUqHuzPEYi%';

-- SKU FF410
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'FF410' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/jRucmOWot7M" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/jRucmOWot7%';

-- SKU FF410
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'FF410' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/jRucmOWot7M" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/jRucmOWot7%';

-- SKU FLOORAPP
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'FLOORAPP' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/YtThK4FJMLU?si=fQr2GuvY3eD0ibkm" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/YtThK4FJML%';

-- SKU GLASSAPP
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'GLASSAPP' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/j5fb5L2Gd98?si=JLY6O9rzisJDFt5H" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/j5fb5L2Gd9%';

-- SKU 4035
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4035' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/U21VlR47JCU" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/U21VlR47JC%';

-- SKU 4035
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4035' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/U21VlR47JCU" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/U21VlR47JC%';

-- SKU 4010
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4010' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/X3JnLzUBWIg" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/X3JnLzUBWI%';

-- SKU 4010
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4010' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/X3JnLzUBWIg" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/X3JnLzUBWI%';

-- SKU 4030
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4030' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/RObY0VBaTUQ" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/RObY0VBaTU%';

-- SKU 4030
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4030' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/RObY0VBaTUQ" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/RObY0VBaTU%';

-- SKU 4036
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4036' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/URQcG4CLMRQ" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/URQcG4CLMR%';

-- SKU 4036
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4036' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/URQcG4CLMRQ" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/URQcG4CLMR%';

-- SKU 142SGC
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '142SGC' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/5YdrouSA9Ck?si=RZ65VkOaW45k9lMN" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/5YdrouSA9C%';

-- SKU EASYDOT
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'EASYDOT' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/Uon_1kAvw0g" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/Uon_1kAvw0%';

-- SKU EASYDOTPP
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'EASYDOTPP' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/Uon_1kAvw0g" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/Uon_1kAvw0%';

-- SKU 21005
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '21005' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="http://www.youtube.com/embed/sfM7cY3qxZw?" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%http://www.youtube.com/embed/sfM7cY3qxZw%';

-- SKU PTAS
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'PTAS' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/BRLnyKJU_vU" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/BRLnyKJU_v%';

-- SKU PTAS
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'PTAS' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/BRLnyKJU_vU" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/BRLnyKJU_v%';

-- SKU PTAS
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'PTAS' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/BRLnyKJU_vU" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/BRLnyKJU_v%';

-- SKU 7901
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '7901' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/J5p7ZKz_DeY" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/J5p7ZKz_De%';

-- SKU RJ50STA/F
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'RJ50STA/F' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/iGpkdU3FpEI" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/iGpkdU3FpE%';

-- SKU S41
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'S41' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/eHQaEvAkyQI" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/eHQaEvAkyQ%';

-- SKU S41
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'S41' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/eHQaEvAkyQI" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/eHQaEvAkyQ%';

-- SKU PTA
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'PTA' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/BRLnyKJU_vU" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/BRLnyKJU_v%';

-- SKU PTA
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'PTA' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/BRLnyKJU_vU" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/BRLnyKJU_v%';

-- SKU PTA
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'PTA' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/BRLnyKJU_vU" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/BRLnyKJU_v%';

-- SKU WRAPCUT
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'WRAPCUT' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/ShkChTSvPgg?si=15sC3B17w9GHLsi3" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/ShkChTSvPg%';

-- SKU WRAPCUTPRO
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'WRAPCUTPRO' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/ShkChTSvPgg?si=15sC3B17w9GHLsi3" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/ShkChTSvPg%';

-- SKU WRAPCUTWIRE
UPDATE wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'WRAPCUTWIRE' SET p.post_content = CONCAT(p.post_content, '<div class="dt-product-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin-top:1.5em;"><iframe src="https://www.youtube.com/embed/ShkChTSvPgg?si=15sC3B17w9GHLsi3" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allowfullscreen loading="lazy"></iframe></div>') WHERE p.post_type = 'product' AND p.post_content NOT LIKE '%https://www.youtube.com/embed/ShkChTSvPg%';

-- =====================================================================
-- PART 3: Store tab content as product meta
-- =====================================================================

-- SKU 1 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '1' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs press</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '1' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs press</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU 100 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '100' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs press</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '100' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs press</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU 110 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '110' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs press</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '110' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs press</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU 180 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '180' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs press</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '180' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs press</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU 200 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '200' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs press</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '200' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs press</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU 210 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '210' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs press</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '210' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs press</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU 250 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '250' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs press</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '250' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs press</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU 310 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '310' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs press</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '310' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs press</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU 320 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '320' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs press</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '320' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs press</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU 400 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '400' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs press</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '400' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs press</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU 410S : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '410S' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>80<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '410S' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>80<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4212 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4212' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C<br></td>
<td>15 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Cool</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Hand Wash</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4212' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C<br></td>
<td>15 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Cool</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Hand Wash</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4213 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4213' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C<br></td>
<td>15 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Cool</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Hand Wash</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4213' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C<br></td>
<td>15 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Cool</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Hand Wash</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4221 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4221' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C<br></td>
<td>15 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Cool</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Hand Wash</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4221' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C<br></td>
<td>15 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Cool</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Hand Wash</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU PG423 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'PG423' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium</td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER (TOP)</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'PG423' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium</td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER (TOP)</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU PG424 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'PG424' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium</td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER (TOP)</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'PG424' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium</td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER (TOP)</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU PG425 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'PG425' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium</td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER (TOP)</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'PG425' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium</td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER (TOP)</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU PG428 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'PG428' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium</td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER (TOP)</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'PG428' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium</td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER (TOP)</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4280 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Heat Pressing and Washing Info' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4280' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Heat Pressing and Washing Info';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C<br></td>
<td>15 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4280' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C<br></td>
<td>15 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4281 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Heat Pressing and Washing Info' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4281' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Heat Pressing and Washing Info';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C<br></td>
<td>15 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4281' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C<br></td>
<td>15 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4282 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Heat Pressing and Washing Info' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4282' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Heat Pressing and Washing Info';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C<br></td>
<td>15 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4282' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C<br></td>
<td>15 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU PG432 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'PG432' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium</td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER (TOP)</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'PG432' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium</td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER (TOP)</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU 434 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '434' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '434' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 435 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '435' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '435' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 438 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '438' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '438' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 439 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '439' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '439' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU PG444 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'PG444' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium</td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER (TOP)</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'PG444' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium</td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER (TOP)</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU PG450 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'PG450' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium</td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER (TOP)</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'PG450' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium</td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER (TOP)</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4501 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4501' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C</td>
<td>5 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4501' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C</td>
<td>5 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4503 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4503' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C</td>
<td>5 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4503' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C</td>
<td>5 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4508 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4508' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C</td>
<td>5 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4508' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C</td>
<td>5 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU PG451 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'PG451' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium</td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER (TOP)</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'PG451' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium</td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER (TOP)</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4510 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4510' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C</td>
<td>5 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4510' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C</td>
<td>5 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4515 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4515' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C</td>
<td>5 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4515' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C</td>
<td>5 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU PG454 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'PG454' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium</td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER (TOP)</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'PG454' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium</td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER (TOP)</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU PG455 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'PG455' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium</td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER (TOP)</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'PG455' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium</td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER (TOP)</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU PG456 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'PG456' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium</td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER (TOP)</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'PG456' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium</td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER (TOP)</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU PG458 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'PG458' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium</td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER (TOP)</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'PG458' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium</td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER (TOP)</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU PG459 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'PG459' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium</td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER (TOP)</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'PG459' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium</td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER (TOP)</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU 466 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '466' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>80<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '466' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>80<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 469 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '469' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>80<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '469' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>80<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4781 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4781' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4781' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4783 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4783' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C</td>
<td>5 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4783' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C</td>
<td>5 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4785 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4785' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>145<sup>o</sup>C</td>
<td>8 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Cool</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4785' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>145<sup>o</sup>C</td>
<td>8 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Cool</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4801 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4801' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>150<sup>o</sup>C</td>
<td>5 secs pre press / 10 secs press</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Lukewarm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>40<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4801' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>150<sup>o</sup>C</td>
<td>5 secs pre press / 10 secs press</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Lukewarm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>40<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4802 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4802' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>150<sup>o</sup>C</td>
<td>5 secs pre press / 10 secs press</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Lukewarm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>40<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4802' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>150<sup>o</sup>C</td>
<td>5 secs pre press / 10 secs press</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Lukewarm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>40<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4803 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4803' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>150<sup>o</sup>C</td>
<td>5 secs pre press / 10 secs press</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Lukewarm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>40<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4803' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>150<sup>o</sup>C</td>
<td>5 secs pre press / 10 secs press</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Lukewarm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>40<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4804 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4804' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>150<sup>o</sup>C</td>
<td>5 secs pre press / 10 secs press</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Lukewarm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>40<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4804' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>150<sup>o</sup>C</td>
<td>5 secs pre press / 10 secs press</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Lukewarm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>40<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4806 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4806' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>150<sup>o</sup>C</td>
<td>5 secs pre press / 10 secs press</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Lukewarm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>40<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4806' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>150<sup>o</sup>C</td>
<td>5 secs pre press / 10 secs press</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Lukewarm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>40<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4808 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4808' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>150<sup>o</sup>C</td>
<td>5 secs pre press / 10 secs press</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Lukewarm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>40<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4808' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>150<sup>o</sup>C</td>
<td>5 secs pre press / 10 secs press</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Lukewarm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>40<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4820 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4820' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>150<sup>o</sup>C</td>
<td>5 secs pre press / 10 secs press</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Lukewarm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>40<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4820' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>150<sup>o</sup>C</td>
<td>5 secs pre press / 10 secs press</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Lukewarm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>40<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4901 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4901' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4901' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4902 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4902' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4902' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4903 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4903' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Luke Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4903' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Luke Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4904 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4904' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4904' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4905 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4905' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4905' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4906 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4906' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4906' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4908 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4908' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4908' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4909 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4909' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4909' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4910 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4910' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4910' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4912 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4912' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4912' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4914 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4914' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4914' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4915 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4915' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4915' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4916 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4916' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4916' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4917 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4917' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4917' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4918 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4918' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4918' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4919 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4919' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4919' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4920 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4920' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4920' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4922 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4922' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4922' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4924 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4924' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4924' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4925 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4925' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4925' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4926 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4926' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4926' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4927 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4927' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4927' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4928 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4928' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4928' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4929 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4929' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4929' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4930 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4930' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4930' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4940 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4940' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Lukewarm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4940' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Lukewarm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4941 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4941' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Lukewarm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4941' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Lukewarm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4942 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4942' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Lukewarm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4942' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Lukewarm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4943 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4943' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Lukewarm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4943' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Lukewarm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4944 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4944' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Lukewarm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4944' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Lukewarm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4945 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4945' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Lukewarm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4945' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Lukewarm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4951 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4951' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4951' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4952 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4952' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4952' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4953 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4953' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4953' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4954 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4954' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4954' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4955 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4955' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4955' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4956 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4956' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4956' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4957 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4957' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4957' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4958 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4958' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4958' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4961 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4961' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4961' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4962 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4962' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4962' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4965 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4965' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4965' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4967 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4967' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4967' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4968 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4968' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4968' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4973 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4973' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4973' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4975 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4975' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4975' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4977 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4977' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4977' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4987 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4987' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Lukewarm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4987' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Lukewarm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 6978 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '6978' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '6978' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 6985 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '6985' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '6985' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 6986 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '6986' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '6986' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 6988 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '6988' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '6988' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 6989 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '6989' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '6989' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 6995 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '6995' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '6995' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 6996 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '6996' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '6996' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 6997 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '6997' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '6997' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 6998 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '6998' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '6998' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C<br>150<sup>o</sup>C<br>160<sup>o</sup>C</td>
<td>5 secs<br>4 secs<br>3 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU 700 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '700' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs press</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '700' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs press</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU 730 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '730' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs press</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '730' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs press</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU 13100S : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Lamination video' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '13100S' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Lamination video';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<head></head>

<body>

<div class="iframecontainer"> 
<iframe class="responsive-iframe" src="https://www.youtube.com/embed/44Ok6i66Y9s?si=pOih7v2pWNlDyicJ" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe></div>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '13100S' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<head></head>

<body>

<div class="iframecontainer"> 
<iframe class="responsive-iframe" src="https://www.youtube.com/embed/44Ok6i66Y9s?si=pOih7v2pWNlDyicJ" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe></div>';

-- SKU 4625 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4625' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Luke Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4625' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Luke Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4625 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4625' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Luke Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4625' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Luke Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4035 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4035' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>150<sup>o</sup>C</td>
<td>4 secs<br>10 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
<td>40<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4035' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>150<sup>o</sup>C</td>
<td>4 secs<br>10 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
<td>40<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4035 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4035' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>150<sup>o</sup>C</td>
<td>4 secs<br>10 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
<td>40<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4035' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>150<sup>o</sup>C</td>
<td>4 secs<br>10 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
<td>40<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4010 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4010' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C</td>
<td>5 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4010' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C</td>
<td>5 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4010 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4010' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C</td>
<td>5 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4010' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>130<sup>o</sup>C</td>
<td>5 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4030 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4030' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
<td>80<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4030' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
<td>80<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4030 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4030' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
<td>80<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4030' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
<td>80<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4036 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4036' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td><p>160<sup>o</sup>C<br>150<sup>o</sup>C<br>130<sup>o</sup>C</p></td>
<td>3 secs<br>4 secs<br>5 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4036' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td><p>160<sup>o</sup>C<br>150<sup>o</sup>C<br>130<sup>o</sup>C</p></td>
<td>3 secs<br>4 secs<br>5 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4036 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4036' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td><p>160<sup>o</sup>C<br>150<sup>o</sup>C<br>130<sup>o</sup>C</p></td>
<td>3 secs<br>4 secs<br>5 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4036' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td><p>160<sup>o</sup>C<br>150<sup>o</sup>C<br>130<sup>o</sup>C</p></td>
<td>3 secs<br>4 secs<br>5 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Warm</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU P800 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P800' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>-</td>
<td>&#177; 9003</td>
<td>= 700</td>
<td>= 9829-00</td>
<td>= 10</td>
<td>M7-100</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P800' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>-</td>
<td>&#177; 9003</td>
<td>= 700</td>
<td>= 9829-00</td>
<td>= 10</td>
<td>M7-100</td>
  </tr>

  </tbody></table>';

-- SKU P801 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P801' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; Black C</td>
<td>&#177; 9005</td>
<td>= 701</td>
<td>= 9889-00</td>
<td>= 701</td>
<td>M7-110</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P801' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; Black C</td>
<td>&#177; 9005</td>
<td>= 701</td>
<td>= 9889-00</td>
<td>= 701</td>
<td>M7-110</td>
  </tr>

  </tbody></table>';

-- SKU P802 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P802' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>-</td>
<td>-</td>
<td>= 730</td>
<td>= 9828-00</td>
<td>&#177;  102</td>
<td>M7-101M</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P802' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>-</td>
<td>-</td>
<td>= 730</td>
<td>= 9828-00</td>
<td>&#177;  102</td>
<td>M7-101M</td>
  </tr>

  </tbody></table>';

-- SKU P803 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P803' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; Black 3 U</td>
<td>-</td>
<td>= 721</td>
<td>= 9888-00</td>
<td>= 702</td>
<td>M7-136</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P803' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; Black 3 U</td>
<td>-</td>
<td>= 721</td>
<td>= 9888-00</td>
<td>= 702</td>
<td>M7-136</td>
  </tr>

  </tbody></table>';

-- SKU P804 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P804' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 131 C</td>
<td>&#177; 1016</td>
<td> - </td>
<td>= 9809-21</td>
<td> - </td>
<td> - </td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P804' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 131 C</td>
<td>&#177; 1016</td>
<td> - </td>
<td>= 9809-21</td>
<td> - </td>
<td> - </td>
  </tr>

  </tbody></table>';

-- SKU P805 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P805' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 108 C</td>
<td>&#177; 1018</td>
<td>&#177; 707 </td>
<td>= 9807-43</td>
<td> - </td>
<td>M7-136</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P805' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 108 C</td>
<td>&#177; 1018</td>
<td>&#177; 707 </td>
<td>= 9807-43</td>
<td> - </td>
<td>M7-136</td>
  </tr>

  </tbody></table>';

-- SKU P806 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P806' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 109 C</td>
<td>&#177; 1021</td>
<td>&#177; 739 </td>
<td>= 9809-46</td>
<td>= 202</td>
<td> - </td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P806' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 109 C</td>
<td>&#177; 1021</td>
<td>&#177; 739 </td>
<td>= 9809-46</td>
<td>= 202</td>
<td> - </td>
  </tr>

  </tbody></table>';

-- SKU P807 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P807' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 7548 C</td>
<td>&#177; 1023</td>
<td>= 706</td>
<td>&#177; 9809-45</td>
<td> - </td>
<td>M7-131</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P807' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 7548 C</td>
<td>&#177; 1023</td>
<td>= 706</td>
<td>&#177; 9809-45</td>
<td> - </td>
<td>M7-131</td>
  </tr>

  </tbody></table>';

-- SKU P808 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P808' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 3514 C</td>
<td>&#177; 1003</td>
<td>= 704 </td>
<td>&#177; 9809-47</td>
<td>= 229 </td>
<td>M7-132</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P808' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 3514 C</td>
<td>&#177; 1003</td>
<td>= 704 </td>
<td>&#177; 9809-47</td>
<td>= 229 </td>
<td>M7-132</td>
  </tr>

  </tbody></table>';

-- SKU P809 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P809' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 137 C</td>
<td>&#177; 1028</td>
<td>&#177; 764</td>
<td>= 9809-11</td>
<td>= 215</td>
<td>M7-137</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P809' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 137 C</td>
<td>&#177; 1028</td>
<td>&#177; 764</td>
<td>= 9809-11</td>
<td>= 215</td>
<td>M7-137</td>
  </tr>

  </tbody></table>';

-- SKU P810 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P810' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 151 C</td>
<td>&#177; 3003</td>
<td> - </td>
<td>= 9801-44</td>
<td>= 395</td>
<td>M7-138</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P810' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 151 C</td>
<td>&#177; 3003</td>
<td> - </td>
<td>= 9801-44</td>
<td>= 395</td>
<td>M7-138</td>
  </tr>

  </tbody></table>';

-- SKU P811 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P811' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 1505 C</td>
<td>&#177; 2003</td>
<td>-</td>
<td>-</td>
<td>= 35</td>
<td>-</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P811' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 1505 C</td>
<td>&#177; 2003</td>
<td>-</td>
<td>-</td>
<td>= 35</td>
<td>-</td>
  </tr>

  </tbody></table>';

-- SKU P812 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P812' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 1585 C</td>
<td>&#177; 2008</td>
<td>= 705</td>
<td>&#177; 9801-40</td>
<td>-</td>
<td>M7-133</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P812' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 1585 C</td>
<td>&#177; 2008</td>
<td>= 705</td>
<td>&#177; 9801-40</td>
<td>-</td>
<td>M7-133</td>
  </tr>

  </tbody></table>';

-- SKU P813 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P813' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 179 C</td>
<td>-</td>
<td>&#177; 743</td>
<td>= 9859-10</td>
<td>&#177; 302</td>
<td>-</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P813' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 179 C</td>
<td>-</td>
<td>&#177; 743</td>
<td>= 9859-10</td>
<td>&#177; 302</td>
<td>-</td>
  </tr>

  </tbody></table>';

-- SKU P814 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P814' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 3556 C</td>
<td>&#177; 3020</td>
<td>= 737</td>
<td>= 9859-47</td>
<td>&#177; 303</td>
<td>-</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P814' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 3556 C</td>
<td>&#177; 3020</td>
<td>= 737</td>
<td>= 9859-47</td>
<td>&#177; 303</td>
<td>-</td>
  </tr>

  </tbody></table>';

-- SKU P815 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P815' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 485 C</td>
<td>&#177; 3020</td>
<td>&#177; 749-02</td>
<td>= 9859-46</td>
<td>-</td>
<td>M7-141</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P815' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 485 C</td>
<td>&#177; 3020</td>
<td>&#177; 749-02</td>
<td>= 9859-46</td>
<td>-</td>
<td>M7-141</td>
  </tr>

  </tbody></table>';

-- SKU P816 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P816' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 3546 C</td>
<td>&#177; 3020</td>
<td>= 765-01</td>
<td>= 9859-43</td>
<td>= 397</td>
<td>M7-142</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P816' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 3546 C</td>
<td>&#177; 3020</td>
<td>= 765-01</td>
<td>= 9859-43</td>
<td>= 397</td>
<td>M7-142</td>
  </tr>

  </tbody></table>';

-- SKU P817 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P817' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 3517 C</td>
<td>&#177; 3000</td>
<td>= 749-01</td>
<td>= 9859-00</td>
<td>= 305</td>
<td>-</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P817' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 3517 C</td>
<td>&#177; 3000</td>
<td>= 749-01</td>
<td>= 9859-00</td>
<td>= 305</td>
<td>-</td>
  </tr>

  </tbody></table>';

-- SKU P818 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P818' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 3517 C</td>
<td>&#177; 3001</td>
<td>= 703</td>
<td>= 9859-12</td>
<td>= 306</td>
<td>M7-148</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P818' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 3517 C</td>
<td>&#177; 3001</td>
<td>= 703</td>
<td>= 9859-12</td>
<td>= 306</td>
<td>M7-148</td>
  </tr>

  </tbody></table>';

-- SKU P819 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P819' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>

  </tr>
  <tr>
    <td>&#177; 7621 C</td>
<td>&#177; 3002</td>
<td>-</td>
<td>-</td>
<td>&#177; 31</td>
<td></td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P819' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>

  </tr>
  <tr>
    <td>&#177; 7621 C</td>
<td>&#177; 3002</td>
<td>-</td>
<td>-</td>
<td>&#177; 31</td>
<td></td>
  </tr>

  </tbody></table>';

-- SKU P820 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P820' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 7623 C</td>
<td>&#177; 3003</td>
<td>= 778</td>
<td>= 9859-41</td>
<td>&#177; 307</td>
<td>M7-114</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P820' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 7623 C</td>
<td>&#177; 3003</td>
<td>= 778</td>
<td>= 9859-41</td>
<td>&#177; 307</td>
<td>M7-114</td>
  </tr>

  </tbody></table>';

-- SKU P821 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P821' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 209 C</td>
<td>&#177; 3004</td>
<td>&#177; 702</td>
<td>= 9859-05</td>
<td>&#177; 308</td>
<td>M7-145</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P821' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 209 C</td>
<td>&#177; 3004</td>
<td>&#177; 702</td>
<td>= 9859-05</td>
<td>&#177; 308</td>
<td>M7-145</td>
  </tr>

  </tbody></table>';

-- SKU P822 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P822' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 504 C</td>
<td>&#177; 3005</td>
<td>&#177; 702</td>
<td>= 9859-28</td>
<td>-</td>
<td>-</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P822' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 504 C</td>
<td>&#177; 3005</td>
<td>&#177; 702</td>
<td>= 9859-28</td>
<td>-</td>
<td>-</td>
  </tr>

  </tbody></table>';

-- SKU P823 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P823' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 214 C</td>
<td>-</td>
<td>&#177; 715</td>
<td>-</td>
<td>-</td>
<td>M7-182</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P823' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 214 C</td>
<td>-</td>
<td>&#177; 715</td>
<td>-</td>
<td>-</td>
<td>M7-182</td>
  </tr>

  </tbody></table>';

-- SKU P824 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P824' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 520 C</td>
<td>-</td>
<td>&#177; 777</td>
<td>-</td>
<td>-</td>
<td>-</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P824' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 520 C</td>
<td>-</td>
<td>&#177; 777</td>
<td>-</td>
<td>-</td>
<td>-</td>
  </tr>

  </tbody></table>';

-- SKU P825 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P825' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>525 C</td>
<td>-</td>
<td>-</td>
<td>9839-38</td>
<td>-</td>
<td>-</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P825' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>525 C</td>
<td>-</td>
<td>-</td>
<td>9839-38</td>
<td>-</td>
<td>-</td>
  </tr>

  </tbody></table>';

-- SKU P826 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P826' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 268 C</td>
<td>-</td>
<td>&#177; 17</td>
<td>= 9839-13</td>
<td>= 403</td>
<td>M7-183</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P826' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 268 C</td>
<td>-</td>
<td>&#177; 17</td>
<td>= 9839-13</td>
<td>= 403</td>
<td>M7-183</td>
  </tr>

  </tbody></table>';

-- SKU P827 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P827' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 2767 C</td>
<td>&#177; 5011</td>
<td>&#177; 724-01</td>
<td>= 9839-19</td>
<td>= 513</td>
<td>M7-185</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P827' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 2767 C</td>
<td>&#177; 5011</td>
<td>&#177; 724-01</td>
<td>= 9839-19</td>
<td>= 513</td>
<td>M7-185</td>
  </tr>

  </tbody></table>';

-- SKU P828 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P828' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 2768 C</td>
<td>-</td>
<td>= 724</td>
<td>= 9839-40</td>
<td>-</td>
<td>-</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P828' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 2768 C</td>
<td>-</td>
<td>= 724</td>
<td>= 9839-40</td>
<td>-</td>
<td>-</td>
  </tr>

  </tbody></table>';

-- SKU P829 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P829' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 281 C</td>
<td>-</td>
<td>= 738</td>
<td>&#177; 9839-23</td>
<td>&#177; 50</td>
<td>M7-119</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P829' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 281 C</td>
<td>-</td>
<td>= 738</td>
<td>&#177; 9839-23</td>
<td>&#177; 50</td>
<td>M7-119</td>
  </tr>

  </tbody></table>';

-- SKU P830 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P830' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 2748 C</td>
<td>-</td>
<td>&#177; 723</td>
<td>-</td>
<td>= 164</td>
<td>M7-156</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P830' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 2748 C</td>
<td>-</td>
<td>&#177; 723</td>
<td>-</td>
<td>= 164</td>
<td>M7-156</td>
  </tr>

  </tbody></table>';

-- SKU P832 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P832' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 288 C</td>
<td>&#177; 5002</td>
<td>&#177; 792</td>
<td>&#177; 9839-12</td>
<td>&#177; 511</td>
<td>-</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P832' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 288 C</td>
<td>&#177; 5002</td>
<td>&#177; 792</td>
<td>&#177; 9839-12</td>
<td>&#177; 511</td>
<td>-</td>
  </tr>

  </tbody></table>';

-- SKU P833 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P833' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 2748 C</td>
<td>&#177; 5002</td>
<td>&#177; 747</td>
<td>&#177; 9839-12</td>
<td>= 511</td>
<td>M7-117</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P833' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 2748 C</td>
<td>&#177; 5002</td>
<td>&#177; 747</td>
<td>&#177; 9839-12</td>
<td>= 511</td>
<td>M7-117</td>
  </tr>

  </tbody></table>';

-- SKU P834 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P834' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 287 C</td>
<td>-</td>
<td>&#177; 752</td>
<td>= 9839-26</td>
<td>&#177; 510</td>
<td>M7-118</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P834' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 287 C</td>
<td>-</td>
<td>&#177; 752</td>
<td>= 9839-26</td>
<td>&#177; 510</td>
<td>M7-118</td>
  </tr>

  </tbody></table>';

-- SKU P835 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P835' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 2145 C</td>
<td>&#177; 5005</td>
<td>= 708</td>
<td>= 9839-11</td>
<td>&#177; 57</td>
<td>M7-154</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P835' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 2145 C</td>
<td>&#177; 5005</td>
<td>= 708</td>
<td>= 9839-11</td>
<td>&#177; 57</td>
<td>M7-154</td>
  </tr>

  </tbody></table>';

-- SKU P836 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P836' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 2935 C</td>
<td>-</td>
<td>= 741</td>
<td>&#177; 9839-22</td>
<td>= 166</td>
<td>M7-153</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P836' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 2935 C</td>
<td>-</td>
<td>= 741</td>
<td>&#177; 9839-22</td>
<td>= 166</td>
<td>M7-153</td>
  </tr>

  </tbody></table>';

-- SKU P837 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P837' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 2175 C</td>
<td>-</td>
<td>= 751</td>
<td>9839-24</td>
<td>=52</td>
<td>-</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P837' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 2175 C</td>
<td>-</td>
<td>= 751</td>
<td>9839-24</td>
<td>=52</td>
<td>-</td>
  </tr>

  </tbody></table>';

-- SKU P838 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P838' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 3005 C</td>
<td>-</td>
<td>= 709</td>
<td>-</td>
<td>= 167</td>
<td>-</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P838' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 3005 C</td>
<td>-</td>
<td>= 709</td>
<td>-</td>
<td>= 167</td>
<td>-</td>
  </tr>

  </tbody></table>';

-- SKU P839 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P839' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 2194 C</td>
<td>-</td>
<td>= 784</td>
<td>= 9839-10</td>
<td>&#177; 505</td>
<td>M7-151</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P839' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 2194 C</td>
<td>-</td>
<td>= 784</td>
<td>= 9839-10</td>
<td>&#177; 505</td>
<td>M7-151</td>
  </tr>

  </tbody></table>';

-- SKU P840 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P840' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 2915 C</td>
<td>-</td>
<td>&#177; 732</td>
<td>= 9839-07</td>
<td>&#177; 504</td>
<td>M7-150</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P840' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 2915 C</td>
<td>-</td>
<td>&#177; 732</td>
<td>= 9839-07</td>
<td>&#177; 504</td>
<td>M7-150</td>
  </tr>

  </tbody></table>';

-- SKU P841 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P841' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 321 C</td>
<td>&#177; 5018</td>
<td>= 731</td>
<td>= 9849-17</td>
<td>= 503</td>
<td>M7-165</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P841' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 321 C</td>
<td>&#177; 5018</td>
<td>= 731</td>
<td>= 9849-17</td>
<td>= 503</td>
<td>M7-165</td>
  </tr>

  </tbody></table>';

-- SKU P842 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P842' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', 'style>

table {

margin: 0 auto 0 auto;
}

table, th, td {
  
  border-collapse: collapse;
padding: 5px;
}
th, td {
  padding: 5px;
  text-align: center; 
  width: 160px;
  height: 30px;
  background-color:#009ee3;
  color: white;
  border-left: 5px solid white;
  border-right: 5px solid white;
}

th {
  border-top-color: white;
  font-size: 14px;
}

td {

padding-bottom: 1em;
border-bottom: 5px solid white;
font-size: 14px;
  
</style>



<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th
  </tr>
  <tr>
    <td>&#177; 315 C</td>
<td>-</td>
<td>&#177; 798</td>
<td>= 9849-26</td>
<td>= 608</td>
<td>-</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P842' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'style>

table {

margin: 0 auto 0 auto;
}

table, th, td {
  
  border-collapse: collapse;
padding: 5px;
}
th, td {
  padding: 5px;
  text-align: center; 
  width: 160px;
  height: 30px;
  background-color:#009ee3;
  color: white;
  border-left: 5px solid white;
  border-right: 5px solid white;
}

th {
  border-top-color: white;
  font-size: 14px;
}

td {

padding-bottom: 1em;
border-bottom: 5px solid white;
font-size: 14px;
  
</style>



<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th
  </tr>
  <tr>
    <td>&#177; 315 C</td>
<td>-</td>
<td>&#177; 798</td>
<td>= 9849-26</td>
<td>= 608</td>
<td>-</td>
  </tr>

  </tbody></table>';

-- SKU P843 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P843' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 548 C</td>
<td>-</td>
<td>-</td>
<td>= 9849-34</td>
<td>-</td>
<td>M7-168</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P843' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 548 C</td>
<td>-</td>
<td>-</td>
<td>= 9849-34</td>
<td>-</td>
<td>M7-168</td>
  </tr>

  </tbody></table>';

-- SKU P844 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P844' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 343 C</td>
<td>-</td>
<td>-</td>
<td>-</td>
<td>-</td>
<td>-</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P844' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 343 C</td>
<td>-</td>
<td>-</td>
<td>-</td>
<td>-</td>
<td>-</td>
  </tr>

  </tbody></table>';

-- SKU P845 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P845' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 3298 C</td>
<td>-</td>
<td>= 711</td>
<td>= 9849-51</td>
<td>= 60</td>
<td>M7-164</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P845' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 3298 C</td>
<td>-</td>
<td>= 711</td>
<td>= 9849-51</td>
<td>= 60</td>
<td>M7-164</td>
  </tr>

  </tbody></table>';

-- SKU P846 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P846' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 341 C</td>
<td>&#177; 6029</td>
<td>= 756</td>
<td>= 9849-10</td>
<td>&#177; 604</td>
<td>M7-163</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P846' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 341 C</td>
<td>&#177; 6029</td>
<td>= 756</td>
<td>= 9849-10</td>
<td>&#177; 604</td>
<td>M7-163</td>
  </tr>

  </tbody></table>';

-- SKU P847 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P847' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 340 C</td>
<td>&#177; 6024</td>
<td>-</td>
<td>= 9849-52</td>
<td>&#177; 603</td>
<td>M7-163</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P847' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 340 C</td>
<td>&#177; 6024</td>
<td>-</td>
<td>= 9849-52</td>
<td>&#177; 603</td>
<td>M7-163</td>
  </tr>

  </tbody></table>';

-- SKU P848 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P848' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 354 C</td>
<td>-</td>
<td>-</td>
<td>= 9849-36</td>
<td>-</td>
<td>M7-197</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P848' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 354 C</td>
<td>-</td>
<td>-</td>
<td>= 9849-36</td>
<td>-</td>
<td>M7-197</td>
  </tr>

  </tbody></table>';

-- SKU P849 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P849' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 361 C</td>
<td>&#177; 6018</td>
<td>= 713</td>
<td>&#177; 9849-55</td>
<td>= 602</td>
<td>M7-161</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P849' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 361 C</td>
<td>&#177; 6018</td>
<td>= 713</td>
<td>&#177; 9849-55</td>
<td>= 602</td>
<td>M7-161</td>
  </tr>

  </tbody></table>';

-- SKU P850 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P850' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 7488 C</td>
<td>-</td>
<td>= 714</td>
<td>= 9849-13</td>
<td>= 601</td>
<td>M7-160</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P850' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 7488 C</td>
<td>-</td>
<td>= 714</td>
<td>= 9849-13</td>
<td>= 601</td>
<td>M7-160</td>
  </tr>

  </tbody></table>';

-- SKU P851 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P851' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 2299 C</td>
<td>-</td>
<td>&#177; 714-02</td>
<td>= 9849-54</td>
<td>-</td>
<td>M7-198</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P851' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 2299 C</td>
<td>-</td>
<td>&#177; 714-02</td>
<td>= 9849-54</td>
<td>-</td>
<td>M7-198</td>
  </tr>

  </tbody></table>';

-- SKU P852 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P852' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 468 C</td>
<td>&#177; 1015</td>
<td>&#177; 758</td>
<td>&#177; 9829-05</td>
<td>&#177; 841</td>
<td>M7-172</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P852' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 468 C</td>
<td>&#177; 1015</td>
<td>&#177; 758</td>
<td>&#177; 9829-05</td>
<td>&#177; 841</td>
<td>M7-172</td>
  </tr>

  </tbody></table>';

-- SKU P853 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P853' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 4625 C</td>
<td>&#177; 8007</td>
<td>&#177; 718</td>
<td>&#177; 9883-06</td>
<td>-</td>
<td>-</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P853' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 4625 C</td>
<td>&#177; 8007</td>
<td>&#177; 718</td>
<td>&#177; 9883-06</td>
<td>-</td>
<td>-</td>
  </tr>

  </tbody></table>';

-- SKU P854 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P854' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 7533 C</td>
<td>&#177; 8017</td>
<td>= 762</td>
<td>&#177; 9383-04</td>
<td>= 803</td>
<td>-</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P854' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 7533 C</td>
<td>&#177; 8017</td>
<td>= 762</td>
<td>&#177; 9383-04</td>
<td>= 803</td>
<td>-</td>
  </tr>

  </tbody></table>';

-- SKU P855 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P855' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 433 C</td>
<td>&#177; 7021</td>
<td>= 759-01</td>
<td>= 9889-17</td>
<td>-</td>
<td>M7-126</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P855' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 433 C</td>
<td>&#177; 7021</td>
<td>= 759-01</td>
<td>= 9889-17</td>
<td>-</td>
<td>M7-126</td>
  </tr>

  </tbody></table>';

-- SKU P856 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P856' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>-</td>
<td>-</td>
<td>= 746</td>
<td>= 9889-01</td>
<td>= 93</td>
<td>M7-196</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P856' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>-</td>
<td>-</td>
<td>= 746</td>
<td>= 9889-01</td>
<td>= 93</td>
<td>M7-196</td>
  </tr>

  </tbody></table>';

-- SKU P857 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P857' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 446 C</td>
<td>-</td>
<td>-= 759-02</td>
<td>= 9889-05</td>
<td>= 714</td>
<td>M7-128</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P857' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 446 C</td>
<td>-</td>
<td>-= 759-02</td>
<td>= 9889-05</td>
<td>= 714</td>
<td>M7-128</td>
  </tr>

  </tbody></table>';

-- SKU P858 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P858' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 445 C</td>
<td>&#177; 7012</td>
<td>&#177; 720</td>
<td>= 9889-02</td>
<td>= 713</td>
<td>M7-124</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P858' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 445 C</td>
<td>&#177; 7012</td>
<td>&#177; 720</td>
<td>= 9889-02</td>
<td>= 713</td>
<td>M7-124</td>
  </tr>

  </tbody></table>';

-- SKU P859 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P859' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 431 C</td>
<td>-</td>
<td>-</td>
<td>-</td>
<td>-</td>
<td>-</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P859' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 431 C</td>
<td>-</td>
<td>-</td>
<td>-</td>
<td>-</td>
<td>-</td>
  </tr>

  </tbody></table>';

-- SKU P860 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P860' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 444 C</td>
<td>&#177; 7000</td>
<td>= 725</td>
<td>= 9889-15</td>
<td>&#177; 756</td>
<td>-</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P860' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 444 C</td>
<td>&#177; 7000</td>
<td>= 725</td>
<td>= 9889-15</td>
<td>&#177; 756</td>
<td>-</td>
  </tr>

  </tbody></table>';

-- SKU P861 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P861' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 429 C</td>
<td>&#177; 7040</td>
<td>-</td>
<td>&#177; 9889-14</td>
<td>-</td>
<td>-</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P861' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 429 C</td>
<td>&#177; 7040</td>
<td>-</td>
<td>&#177; 9889-14</td>
<td>-</td>
<td>-</td>
  </tr>

  </tbody></table>';

-- SKU P862 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P862' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 427 C</td>
<td>&#177; 7035</td>
<td>= 745</td>
<td>&#177; 9889-04</td>
<td>= 72</td>
<td>M7-122</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P862' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 427 C</td>
<td>&#177; 7035</td>
<td>= 745</td>
<td>&#177; 9889-04</td>
<td>= 72</td>
<td>M7-122</td>
  </tr>

  </tbody></table>';

-- SKU P863 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P863' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 877 C</td>
<td>&#177; 9006</td>
<td>= 735</td>
<td>= 9869-00</td>
<td>&#177; 90</td>
<td>M7-190</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P863' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 877 C</td>
<td>&#177; 9006</td>
<td>= 735</td>
<td>= 9869-00</td>
<td>&#177; 90</td>
<td>M7-190</td>
  </tr>

  </tbody></table>';

-- SKU P864 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P864' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 871 C</td>
<td>-</td>
<td>= 736</td>
<td>= 9879-00</td>
<td>&#177; 93</td>
<td>M7-191</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P864' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 871 C</td>
<td>-</td>
<td>= 736</td>
<td>= 9879-00</td>
<td>&#177; 93</td>
<td>M7-191</td>
  </tr>

  </tbody></table>';

-- SKU P865 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P865' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>-</td>
<td>-</td>
<td>-</td>
<td>= 9888-01</td>
<td>-</td>
<td>-</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P865' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>-</td>
<td>-</td>
<td>-</td>
<td>= 9888-01</td>
<td>-</td>
<td>-</td>
  </tr>

  </tbody></table>';

-- SKU P866 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P866' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 877 U</td>
<td>-</td>
<td>-</td>
<td>= 9868-00</td>
<td>-</td>
<td>M7-195</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P866' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 877 U</td>
<td>-</td>
<td>-</td>
<td>= 9868-00</td>
<td>-</td>
<td>M7-195</td>
  </tr>

  </tbody></table>';

-- SKU P867 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P867' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>-</td>
<td>-</td>
<td>-</td>
<td>= 9878-03</td>
<td>-</td>
<td>-</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P867' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>-</td>
<td>-</td>
<td>-</td>
<td>= 9878-03</td>
<td>-</td>
<td>-</td>
  </tr>

  </tbody></table>';

-- SKU P868 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P868' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 443 C</td>
<td>&#177; 7042</td>
<td>-</td>
<td>= 9889-03</td>
<td>&#177; 712</td>
<td>M7-123</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P868' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>&#177; 443 C</td>
<td>&#177; 7042</td>
<td>-</td>
<td>= 9889-03</td>
<td>&#177; 712</td>
<td>M7-123</td>
  </tr>

  </tbody></table>';

-- SKU P898 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P898' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>-</td>
<td>-</td>
<td>-</td>
<td>= 9898-00</td>
<td>-</td>
<td>-</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P898' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>-</td>
<td>-</td>
<td>-</td>
<td>= 9898-00</td>
<td>-</td>
<td>-</td>
  </tr>

  </tbody></table>';

-- SKU P899 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P899' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>-</td>
<td>-</td>
<td>= 740</td>
<td>= 9899-00</td>
<td>= 00</td>
<td>M7-105</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'P899' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>Pantone</th>
<th>RAL</th>
<th>Avery 700</th>
<th>Mactac 9800 Pro</th>
<th>Oracal 551</th>
<th>Metamark M7</th>
  </tr>
  <tr>
    <td>-</td>
<td>-</td>
<td>= 740</td>
<td>= 9899-00</td>
<td>= 00</td>
<td>M7-105</td>
  </tr>

  </tbody></table>';

-- SKU 8470 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '8470' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>150<sup>o</sup>C</td>
<td>20 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Cool</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '8470' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>150<sup>o</sup>C</td>
<td>20 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Cool</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>60<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
  </tr>
  </tbody></table>';

-- SKU ST491 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'ST491' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Cool</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>40<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'ST491' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Cool</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>40<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU ST492 : _dt_tab_one_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_name', 'Application & Aftercare Instructions' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'ST492' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Application & Aftercare Instructions';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_one_content', '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Cool</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>40<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
  </tr>
  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = 'ST492' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>PRESS TEMP</th>
<th>PRESS TIME</th>
<th>PRESSURE</th>
<th>EASY WEED</th>
<th>PEEL</th>
  </tr>
  <tr>
    <td>160<sup>o</sup>C</td>
<td>15 secs</td>
<td>Medium<br></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>Cool</td>
  </tr>

<tr>
    <th>LAYER</th>
<th>REVERSE IMAGE</th>
<th>WASH</th>
<th>TUMBLE DRY</th>
<th>DRY CLEAN</th>
  </tr>
  <tr>
    <td><span style="font-size:30px;">&#10005;</span></td>
<td><span style="font-size:30px;">&#10003;</span></td>
<td>40<sup>o</sup>C</td>
<td><span style="font-size:30px; color: white;">&#10003;</span></td>
<td><span style="font-size:30px; color: white;">&#10005;</span></td>
  </tr>
  </tbody></table>';

-- SKU 4901 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4901' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#FFFFFF</td>
<td>255, 255, 255</td>
<td>0, 0, 0, 0</td>
<td>000 C</td>
<td>9003</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4901' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#FFFFFF</td>
<td>255, 255, 255</td>
<td>0, 0, 0, 0</td>
<td>000 C</td>
<td>9003</td>
  </tr>

  </tbody></table>';

-- SKU 4902 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4902' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#1D1D1B</td>
<td>5, 5, 5</td>
<td>0, 0, 0, 100</td>
<td>3 C</td>
<td>9017</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4902' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#1D1D1B</td>
<td>5, 5, 5</td>
<td>0, 0, 0, 100</td>
<td>3 C</td>
<td>9017</td>
  </tr>

  </tbody></table>';

-- SKU 4903 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4903' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#009EDD</td>
<td>0, 158, 221</td>
<td>76, 21, 0, 0</td>
<td>2925 C</td>
<td>5012</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4903' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#009EDD</td>
<td>0, 158, 221</td>
<td>76, 21, 0, 0</td>
<td>2925 C</td>
<td>5012</td>
  </tr>

  </tbody></table>';

-- SKU 4904 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4904' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#007348</td>
<td>0, 115, 72</td>
<td>88, 29, 82, 17</td>
<td>349 C</td>
<td>6029</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4904' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#007348</td>
<td>0, 115, 72</td>
<td>88, 29, 82, 17</td>
<td>349 C</td>
<td>6029</td>
  </tr>

  </tbody></table>';

-- SKU 4905 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4905' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#333D4F</td>
<td>51, 61, 79</td>
<td>82, 67, 45, 43</td>
<td>296 C</td>
<td>5003</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4905' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#333D4F</td>
<td>51, 61, 79</td>
<td>82, 67, 45, 43</td>
<td>296 C</td>
<td>5003</td>
  </tr>

  </tbody></table>';

-- SKU 4906 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4906' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#005895</td>
<td>0, 88, 149</td>
<td>95, 63, 14, 2</td>
<td>647 C</td>
<td>5005</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4906' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#005895</td>
<td>0, 88, 149</td>
<td>95, 63, 14, 2</td>
<td>647 C</td>
<td>5005</td>
  </tr>

  </tbody></table>';

-- SKU 4908 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4908' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#CA0928</td>
<td>202, 9, 40</td>
<td>13, 100, 85, 4</td>
<td>49-8 C</td>
<td>3001</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4908' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#CA0928</td>
<td>202, 9, 40</td>
<td>13, 100, 85, 4</td>
<td>49-8 C</td>
<td>3001</td>
  </tr>

  </tbody></table>';

-- SKU 4909 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4909' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#71273b</td>
<td>113, 39, 59</td>
<td>30,86, 44, 51</td>
<td>19-1930 TCX</td>
<td>3004</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4909' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#71273b</td>
<td>113, 39, 59</td>
<td>30,86, 44, 51</td>
<td>19-1930 TCX</td>
<td>3004</td>
  </tr>

  </tbody></table>';

-- SKU 4910 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4910' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#F8AA27</td>
<td>248, 170, 39</td>
<td>0, 39, 89, 0</td>
<td>1235 C</td>
<td>1033</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4910' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#F8AA27</td>
<td>248, 170, 39</td>
<td>0, 39, 89, 0</td>
<td>1235 C</td>
<td>1033</td>
  </tr>

  </tbody></table>';

-- SKU 4912 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4912' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#7A7D7F</td>
<td>122, 125, 127</td>
<td>50, 40, 38, 21</td>
<td>430 C</td>
<td>9023</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4912' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#7A7D7F</td>
<td>122, 125, 127</td>
<td>50, 40, 38, 21</td>
<td>430 C</td>
<td>9023</td>
  </tr>

  </tbody></table>';

-- SKU 4914 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4914' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#494885</td>
<td>73, 72, 133</td>
<td>84, 77, 16, 4</td>
<td>273 C</td>
<td>300 30 40</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4914' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#494885</td>
<td>73, 72, 133</td>
<td>84, 77, 16, 4</td>
<td>273 C</td>
<td>300 30 40</td>
  </tr>

  </tbody></table>';

-- SKU 4915 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4915' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#ED6E15</td>
<td>237, 110, 21</td>
<td>0, 67, 96, 0</td>
<td>30-8 C</td>
<td>2004</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4915' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#ED6E15</td>
<td>237, 110, 21</td>
<td>0, 67, 96, 0</td>
<td>30-8 C</td>
<td>2004</td>
  </tr>

  </tbody></table>';

-- SKU 4916 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4916' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#4C3B34</td>
<td>76, 59, 52</td>
<td>49, 57, 58, 64</td>
<td>7533 C</td>
<td>330-6</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4916' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#4C3B34</td>
<td>76, 59, 52</td>
<td>49, 57, 58, 64</td>
<td>7533 C</td>
<td>330-6</td>
  </tr>

  </tbody></table>';

-- SKU 4917 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4917' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#FADFBD</td>
<td>250, 233, 189</td>
<td>3, 8, 32, 0</td>
<td>7401 C</td>
<td>1015</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4917' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#FADFBD</td>
<td>250, 233, 189</td>
<td>3, 8, 32, 0</td>
<td>7401 C</td>
<td>1015</td>
  </tr>

  </tbody></table>';

-- SKU 4918 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4918' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#FAB600</td>
<td>250, 182, 0</td>
<td>0, 32, 100, 0</td>
<td>123 C</td>
<td>1003</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4918' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#FAB600</td>
<td>250, 182, 0</td>
<td>0, 32, 100, 0</td>
<td>123 C</td>
<td>1003</td>
  </tr>

  </tbody></table>';

-- SKU 4919 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4919' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#FFEE47</td>
<td>255, 238, 71</td>
<td>4, 0, 78, 0</td>
<td>3945 U</td>
<td>1016</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4919' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#FFEE47</td>
<td>255, 238, 71</td>
<td>4, 0, 78, 0</td>
<td>3945 U</td>
<td>1016</td>
  </tr>

  </tbody></table>';

-- SKU 4920 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4920' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#87744D</td>
<td>135, 116, 77</td>
<td>31, 36, 64, 39</td>
<td>872 C</td>
<td>085 60 60</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4920' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#87744D</td>
<td>135, 116, 77</td>
<td>31, 36, 64, 39</td>
<td>872 C</td>
<td>085 60 60</td>
  </tr>

  </tbody></table>';

-- SKU 4922 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4922' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#B16B5E</td>
<td>177, 107, 94</td>
<td>21, 60, 54, 19</td>
<td>54-12 C</td>
<td>3012</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4922' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#B16B5E</td>
<td>177, 107, 94</td>
<td>21, 60, 54, 19</td>
<td>54-12 C</td>
<td>3012</td>
  </tr>

  </tbody></table>';

-- SKU 4924 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4924' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#A95C3B</td>
<td>169, 92, 59</td>
<td>26, 67, 77, 19</td>
<td>39-6 C</td>
<td>8004</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4924' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#A95C3B</td>
<td>169, 92, 59</td>
<td>26, 67, 77, 19</td>
<td>39-6 C</td>
<td>8004</td>
  </tr>

  </tbody></table>';

-- SKU 4925 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4925' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#A5336D</td>
<td>165, 51, 109</td>
<td>33, 90, 24, 10</td>
<td>675 C</td>
<td>350 40 50</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4925' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#A5336D</td>
<td>165, 51, 109</td>
<td>33, 90, 24, 10</td>
<td>675 C</td>
<td>350 40 50</td>
  </tr>

  </tbody></table>';

-- SKU 4926 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4926' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#871C1D</td>
<td>135, 28, 29</td>
<td>29, 98, 87, 35</td>
<td>7620 C</td>
<td>3003</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4926' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#871C1D</td>
<td>135, 28, 29</td>
<td>29, 98, 87, 35</td>
<td>7620 C</td>
<td>3003</td>
  </tr>

  </tbody></table>';

-- SKU 4927 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4927' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#5184AC</td>
<td>81, 132, 172</td>
<td>71, 39, 18, 3</td>
<td>2156 C</td>
<td>5014</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4927' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#5184AC</td>
<td>81, 132, 172</td>
<td>71, 39, 18, 3</td>
<td>2156 C</td>
<td>5014</td>
  </tr>

  </tbody></table>';

-- SKU 4928 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4928' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#686382</td>
<td>104, 99, 130</td>
<td>65, 59, 29, 13</td>
<td>4121 C</td>
<td>4011</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4928' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#686382</td>
<td>104, 99, 130</td>
<td>65, 59, 29, 13</td>
<td>4121 C</td>
<td>4011</td>
  </tr>

  </tbody></table>';

-- SKU 4929 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4929' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#FF6A51</td>
<td>255, 106, 81</td>
<td>0, 58, 68, 0</td>
<td>172 U</td>
<td>040 60 60</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4929' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#FF6A51</td>
<td>255, 106, 81</td>
<td>0, 58, 68, 0</td>
<td>172 U</td>
<td>040 60 60</td>
  </tr>

  </tbody></table>';

-- SKU 4930 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4930' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#94958E</td>
<td>148, 149, 142</td>
<td>43, 32, 38, 13</td>
<td>877 C</td>
<td>7042</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4930' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#94958E</td>
<td>148, 149, 142</td>
<td>43, 32, 38, 13</td>
<td>877 C</td>
<td>7042</td>
  </tr>

  </tbody></table>';

-- SKU 4940 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4940' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#FFFF00</td>
<td>255, 255, 0</td>
<td>10, 0, 95, 0</td>
<td>809 C</td>
<td>1026</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4940' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#FFFF00</td>
<td>255, 255, 0</td>
<td>10, 0, 95, 0</td>
<td>809 C</td>
<td>1026</td>
  </tr>

  </tbody></table>';

-- SKU 4941 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4941' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#33E13D</td>
<td>51, 255, 61</td>
<td>62, 0, 100, 0</td>
<td>802 C</td>
<td>6038</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4941' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#33E13D</td>
<td>51, 255, 61</td>
<td>62, 0, 100, 0</td>
<td>802 C</td>
<td>6038</td>
  </tr>

  </tbody></table>';

-- SKU 4942 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4942' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#FF6C11</td>
<td>255, 108, 17</td>
<td>0, 68, 91, 0</td>
<td>804 C</td>
<td>2003</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4942' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#FF6C11</td>
<td>255, 108, 17</td>
<td>0, 68, 91, 0</td>
<td>804 C</td>
<td>2003</td>
  </tr>

  </tbody></table>';

-- SKU 4943 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4943' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#EA4893</td>
<td>234, 72, 147</td>
<td>0, 83, 0, 0</td>
<td>806 C</td>
<td>-</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4943' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#EA4893</td>
<td>234, 72, 147</td>
<td>0, 83, 0, 0</td>
<td>806 C</td>
<td>-</td>
  </tr>

  </tbody></table>';

-- SKU 4944 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4944' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#FF404B</td>
<td>255, 64, 75</td>
<td>0, 84, 60, 0</td>
<td>805 C</td>
<td>3024</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4944' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#FF404B</td>
<td>255, 64, 75</td>
<td>0, 84, 60, 0</td>
<td>805 C</td>
<td>3024</td>
  </tr>

  </tbody></table>';

-- SKU 4945 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4945' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#C00B6B</td>
<td>192, 11, 107</td>
<td>20, 100, 20, 5</td>
<td>233 C</td>
<td>4010</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4945' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#C00B6B</td>
<td>192, 11, 107</td>
<td>20, 100, 20, 5</td>
<td>233 C</td>
<td>4010</td>
  </tr>

  </tbody></table>';

-- SKU 4951 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4951' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#BDBFBF</td>
<td>198, 191, 191</td>
<td>25, 22, 21, 3</td>
<td>428 C</td>
<td>7047</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4951' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#BDBFBF</td>
<td>198, 191, 191</td>
<td>25, 22, 21, 3</td>
<td>428 C</td>
<td>7047</td>
  </tr>

  </tbody></table>';

-- SKU 4952 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4952' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#00A544</td>
<td>0, 165, 68</td>
<td>80, 1, 94, 0</td>
<td>354 C</td>
<td>6038</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4952' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#00A544</td>
<td>0, 165, 68</td>
<td>80, 1, 94, 0</td>
<td>354 C</td>
<td>6038</td>
  </tr>

  </tbody></table>';

-- SKU 4953 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4953' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#B071A4</td>
<td>176, 113, 164</td>
<td>36, 64, 8, 0</td>
<td>2351 C</td>
<td>4003</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4953' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#B071A4</td>
<td>176, 113, 164</td>
<td>36, 64, 8, 0</td>
<td>2351 C</td>
<td>4003</td>
  </tr>

  </tbody></table>';

-- SKU 4954 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4954' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#D6B093</td>
<td>214, 176, 147</td>
<td>16, 32, 42, 4</td>
<td>4675 C</td>
<td>1001</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4954' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#D6B093</td>
<td>214, 176, 147</td>
<td>16, 32, 42, 4</td>
<td>4675 C</td>
<td>1001</td>
  </tr>

  </tbody></table>';

-- SKU 4955 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4955' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#B9A8D8</td>
<td>185, 168, 216</td>
<td>32, 37, 0, 0</td>
<td>2567 C</td>
<td>300 70 25</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4955' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#B9A8D8</td>
<td>185, 168, 216</td>
<td>32, 37, 0, 0</td>
<td>2567 C</td>
<td>300 70 25</td>
  </tr>

  </tbody></table>';

-- SKU 4956 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4956' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#8C5848</td>
<td>140, 88, 72</td>
<td>32, 62, 62, 33</td>
<td>4014 C</td>
<td>8002</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4956' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#8C5848</td>
<td>140, 88, 72</td>
<td>32, 62, 62, 33</td>
<td>4014 C</td>
<td>8002</td>
  </tr>

  </tbody></table>';

-- SKU 4957 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4957' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#E2D2BB</td>
<td>226, 210, 187</td>
<td>13, 17, 28, 1</td>
<td>7499 C</td>
<td>1013</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4957' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#E2D2BB</td>
<td>226, 210, 187</td>
<td>13, 17, 28, 1</td>
<td>7499 C</td>
<td>1013</td>
  </tr>

  </tbody></table>';

-- SKU 4958 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4958' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#F8E295</td>
<td>248, 226, 149</td>
<td>5, 10, 51, 0</td>
<td>600 U</td>
<td>100 90 40</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4958' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#F8E295</td>
<td>248, 226, 149</td>
<td>5, 10, 51, 0</td>
<td>600 U</td>
<td>100 90 40</td>
  </tr>

  </tbody></table>';

-- SKU 4961 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4961' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#EB9EB6</td>
<td>235, 158, 182</td>
<td>4, 49, 12, 0</td>
<td>189 C</td>
<td>3015</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4961' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#EB9EB6</td>
<td>235, 158, 182</td>
<td>4, 49, 12, 0</td>
<td>189 C</td>
<td>3015</td>
  </tr>

  </tbody></table>';

-- SKU 4962 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4962' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#B64974</td>
<td>182, 73, 116</td>
<td>25, 81, 27, 8</td>
<td>215 C</td>
<td>4010</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4962' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#B64974</td>
<td>182, 73, 116</td>
<td>25, 81, 27, 8</td>
<td>215 C</td>
<td>4010</td>
  </tr>

  </tbody></table>';

-- SKU 4965 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4965' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#71B5DA</td>
<td>113, 181, 218</td>
<td>57, 15, 7, 0</td>
<td>2915 C</td>
<td>5024</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4965' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#71B5DA</td>
<td>113, 181, 218</td>
<td>57, 15, 7, 0</td>
<td>2915 C</td>
<td>5024</td>
  </tr>

  </tbody></table>';

-- SKU 4967 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4967' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#A0B826</td>
<td>160, 184, 38</td>
<td>45, 10, 97, 0</td>
<td>375 C</td>
<td>100 70 70</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4967' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#A0B826</td>
<td>160, 184, 38</td>
<td>45, 10, 97, 0</td>
<td>375 C</td>
<td>100 70 70</td>
  </tr>

  </tbody></table>';

-- SKU 4968 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4968' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#009193</td>
<td>0, 145, 147</td>
<td>81, 20, 43, 4</td>
<td>322 C</td>
<td>5018</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4968' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#009193</td>
<td>0, 145, 147</td>
<td>81, 20, 43, 4</td>
<td>322 C</td>
<td>5018</td>
  </tr>

  </tbody></table>';

-- SKU 4973 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4973' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#D73538</td>
<td>215, 53, 56</td>
<td>9, 90, 77, 1</td>
<td>032 C</td>
<td>3028</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4973' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#D73538</td>
<td>215, 53, 56</td>
<td>9, 90, 77, 1</td>
<td>032 C</td>
<td>3028</td>
  </tr>

  </tbody></table>';

-- SKU 4975 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4975' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#B8DEF2</td>
<td>184, 222, 242</td>
<td>32, 2, 3, 0</td>
<td>291 C</td>
<td>-</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4975' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#B8DEF2</td>
<td>184, 222, 242</td>
<td>32, 2, 3, 0</td>
<td>291 C</td>
<td>-</td>
  </tr>

  </tbody></table>';

-- SKU 4977 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4977' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#9ED7B9</td>
<td>158, 215, 185</td>
<td>43, 0, 36, 0</td>
<td>337 M</td>
<td>6019</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4977' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#9ED7B9</td>
<td>158, 215, 185</td>
<td>43, 0, 36, 0</td>
<td>337 M</td>
<td>6019</td>
  </tr>

  </tbody></table>';

-- SKU 4987 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4987' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#8146BB</td>
<td>129, 70, 187</td>
<td>69, 78, 0, 0</td>
<td>2602 C</td>
<td>4008</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '4987' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#8146BB</td>
<td>129, 70, 187</td>
<td>69, 78, 0, 0</td>
<td>2602 C</td>
<td>4008</td>
  </tr>

  </tbody></table>';

-- SKU 6978 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '6978' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#FFBE98</td>
<td>254, 190, 152</td>
<td>0, 33, 41, 0</td>
<td>13-1023</td>
<td>-</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '6978' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#FFBE98</td>
<td>254, 190, 152</td>
<td>0, 33, 41, 0</td>
<td>13-1023</td>
<td>-</td>
  </tr>

  </tbody></table>';

-- SKU 6985 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '6985' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#5C6CAE</td>
<td>92, 108, 174</td>
<td>72, 58, 2, 0</td>
<td>17-3938 TPG</td>
<td>290 50 40</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '6985' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#5C6CAE</td>
<td>92, 108, 174</td>
<td>72, 58, 2, 0</td>
<td>17-3938 TPG</td>
<td>290 50 40</td>
  </tr>

  </tbody></table>';

-- SKU 6986 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '6986' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#BE3455</td>
<td>190, 52, 85</td>
<td>19, 90, 48, 8</td>
<td>18-1750 TPG</td>
<td>3027</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '6986' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#BE3455</td>
<td>190, 52, 85</td>
<td>19, 90, 48, 8</td>
<td>18-1750 TPG</td>
<td>3027</td>
  </tr>

  </tbody></table>';

-- SKU 6988 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '6988' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#A9D3AB</td>
<td>169, 211, 171</td>
<td>40, 0, 42, 0</td>
<td>2404 C</td>
<td>6019</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '6988' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#A9D3AB</td>
<td>169, 211, 171</td>
<td>40, 0, 42, 0</td>
<td>2404 C</td>
<td>6019</td>
  </tr>

  </tbody></table>';

-- SKU 6989 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '6989' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#4C4B4E</td>
<td>76, 75, 78</td>
<td>64, 55, 49, 47</td>
<td>6217 C</td>
<td>7024</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '6989' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#4C4B4E</td>
<td>76, 75, 78</td>
<td>64, 55, 49, 47</td>
<td>6217 C</td>
<td>7024</td>
  </tr>

  </tbody></table>';

-- SKU 6995 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '6995' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#84985B</td>
<td>132, 152, 91</td>
<td>54, 25, 73, 8</td>
<td>577 U</td>
<td>6021</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '6995' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#84985B</td>
<td>132, 152, 91</td>
<td>54, 25, 73, 8</td>
<td>577 U</td>
<td>6021</td>
  </tr>

  </tbody></table>';

-- SKU 6996 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '6996' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#AA4B59</td>
<td>170, 75, 89</td>
<td>25, 78, 48, 17</td>
<td>18-1629 TCX</td>
<td>020 50 40</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '6996' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#AA4B59</td>
<td>170, 75, 89</td>
<td>25, 78, 48, 17</td>
<td>18-1629 TCX</td>
<td>020 50 40</td>
  </tr>

  </tbody></table>';

-- SKU 6997 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '6997' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#F5D0B2</td>
<td>245, 208, 178</td>
<td>3, 22, 32, 0</td>
<td>475 C</td>
<td>060 90 15</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '6997' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#F5D0B2</td>
<td>245, 208, 178</td>
<td>3, 22, 32, 0</td>
<td>475 C</td>
<td>060 90 15</td>
  </tr>

  </tbody></table>';

-- SKU 6998 : _dt_tab_two_content
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_name', 'Colour References' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '6998' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = 'Colour References';
INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT p.ID, '_dt_tab_two_content', '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#916F57</td>
<td>145, 111, 87</td>
<td>33, 48, 58, 29</td>
<td>4715 C</td>
<td>330-M</td>
  </tr>

  </tbody></table>' FROM wp_posts p JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value = '6998' WHERE p.post_type = 'product' ON DUPLICATE KEY UPDATE meta_value = '<table id="data">
  <tbody><tr>
    <th>HEX</th>
<th>RGB</th>
<th>CMYK</th>
<th>PANTONE</th>
<th>RAL</th>
  </tr>
  <tr>
    <td>#916F57</td>
<td>145, 111, 87</td>
<td>33, 48, 58, 29</td>
<td>4715 C</td>
<td>330-M</td>
  </tr>

  </tbody></table>';
