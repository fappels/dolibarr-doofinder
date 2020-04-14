<?php

global $conf, $dolibarr_main_url_root;

if ($conf->global->DOOFINDER_FILTER) {
	$filter = $conf->global->DOOFINDER_FILTER;
} else {
	$filter = "stock_reel: { gt: 0.0 } , type: ['0', '1']";
}


echo <<<HTML
<!-- doofinder snippet -->
<script>
var doofinder_script ='//cdn.doofinder.com/media/js/doofinder-fullscreen.7.latest.min.js';
(function(d,t){var f=d.createElement(t),s=d.getElementsByTagName(t)[0];f.async=1;
f.src=('https:'==location.protocol?'https:':'http:')+doofinder_script;
f.setAttribute('charset','utf-8');
s.parentNode.insertBefore(f,s)}(document,'script'));
var price_by_qty_values = [];
var dfFullscreenLayers = [{
	"toggleInput": "input[id='search_idprod']",
	"hashid": "$hashid",
	"zone": "eu1",
	"display": {
		"lang": "fr",
		"align": "auto",
		"dtop": -500,
		"dleft": 100,
		"facets": {
			"attached": "right"
		},
		translations: {
			"Select": "Sélectionner",
			"For": "Pour ",
			"pieces": "pieces"
		},
		results: {
			template: document.getElementById('my-results-template').innerHTML,
			initialLayout: 'grid'
		}
	},
	callbacks: {
		/**
		* @param  {Node}   item The DOM node that represents the item.
		*/
		hit: function(item) {
			console.log(item);
			item.setAttribute('href',null);
			console.log(item.getAttribute('href'));
		// item.dfClicked             `true` if the item was already clicked
		// item.getAttribute('href')  returns the URL if the item is a link
		},
		/**
		* @param  {Object} response Object that contains the search response.
		*                           Use your browser dev tools to inspect it.
		*/
		resultsReceived: function(response) {
			var resultView,
				price_level,
				price_level_by_qty = [],
				has_price_level_by_qty,
				singlePrice;

			$.each(response.results, function(index, product) {
				console.log(product);
				if (! product.image_link) {
					$('#nophoto-' + product.id).prop('src', '$dolibarr_main_url_root/public/theme/common/nophoto.png');
				}
				price_by_qty_values[product.id] = [];
				singlePrice = true;
				$.each(product.prices_by_qty_list, function(index, price_by_qty) {
					price_level_by_qty[index] = price_by_qty.index;
					price_by_qty_values[product.id] = price_by_qty.value;
					resultView = $('[data-price_level_by_qty="'+price_level_by_qty[index]+'"][data-id="'+product.id+'"]');
					if (price_level_by_qty[index] == $price_level) {
						resultView.prop('hidden', false);
						singlePrice = false;
					} else {
						resultView.prop('hidden', true);
					}
					
				});
				$.each(product.multiprices, function(index, price) {
					price_level = price.index;
					has_price_level_by_qty = false;
					resultView = $('[data-price_level="'+price_level+'"][data-id="'+product.id+'"]');
					$.each(price_level_by_qty, function(index, price_level_by_qty) {
						if (price_level == price_level_by_qty && price_level == $price_level) has_price_level_by_qty = true;
					});
					if (! has_price_level_by_qty && price_level == $price_level) {
						resultView.prop('hidden', false);
						singlePrice = false;
					} else {
						resultView.prop('hidden', true);
					}
				});
				resultView = $('[data-no_pricel_level_id="'+product.id+'"]');
				if (singlePrice) {
					resultView.prop('hidden', false);
				} else {
					resultView.prop('hidden', true);
				}
			});
		},
		loaded: function(instance) {
			var	value,
				nextValue;

			instance.layer.layer.on('click', '[data-price]', function(e){
				// Get the product ID
				var idprod = parseInt($(this).data('id'), 10),
					title = $(this).data('title'),
					ref = $(this).data('mpn'),
					price = price2numjs($(this).data('price')),
					price_level = 0,
					qty = 1,
					remise_percent,
					tva_tx_el,
					tva_tx = 0;
				
				if ($(this).parent().data('price_level')) {
					price_level = parseInt($(this).parent().data('price_level'));
				} else if ($(this).parent().data('price_level_by_qty_value')) {
					price_level = parseInt($(this).parent().data('price_level_by_qty_value'));
					qty = price2numjs($(this).data('qty'));
					remise_percent = price2numjs($(this).data('remise_percent'));
				}
				tva_tx_el = $('#multiprices_tva_tx-' + idprod + '-' + price_level);
				if (! tva_tx_el.data('tva_tx')) {
					tva_tx_el = $('#tva_tx-' + idprod);
				}
				if (tva_tx_el.data('tva_tx')) {
					tva_tx = price2numjs(tva_tx_el.data('tva_tx'));
				}
				// Do something with it, like adding it to the shopping cart
				// set element values idprod + array('qty'=>'qty','remise_percent' => 'discount', 'price_ht' => 'price_ht', 'tva_tx' => 'tva_tx', 'min_qty' => 'min_qty', 'max_qty' => 'max_qty')
				//alert('The product with ID #' + idprod + ' and price ' + price + ' was added to your shopping cart.');
				$('#idprod').val(idprod).trigger("change");
				$('#price_ht').val(price).trigger("change");
				$('#qty').val(qty).trigger("change");
				$('#discount').val(remise_percent).trigger("change");
				$('#tva_tx').val(tva_tx).trigger("change");
				$('#dp_desc').val(title);
				$('#dp_ref').val(ref);
				// set min - max hidden fields
				nextValue = null;
				$.each(price_by_qty_values[idprod], function(index, price_by_qty_value) {
					if (! nextValue) {
						value = price_by_qty_value;
					} else {
						value = nextValue;
					}
					if (price_by_qty_value.quantity <= qty) {
						if (index < (price_by_qty_values[idprod].length -1)) {
						nextValue = price_by_qty_values[idprod][index+1];
						$('#max_qty').val(nextValue.quantity);
						} else {
							$('#max_qty').val(0);
						}
						$('#min_qty').val(value.quantity)
					}
				}); 
				$('#close-helper').trigger('click'); // close layer
				// goto orderline add button
				$([document.documentElement, document.body]).animate({
					scrollLeft: $("#addline").offset().left,
					scrollTop: $("#addline").offset().top - 200
				}, 1000);
			});
		}
	},
	searchParams: {
		filter: {
			$filter
		}
	}
}];
</script>
HTML
?>