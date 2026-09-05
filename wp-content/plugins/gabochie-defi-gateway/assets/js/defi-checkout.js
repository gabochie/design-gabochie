(function($) {
	'use strict';

	$(document).ready(function() {
		$(document.body).on('click', '.gd-chain-btn', function(e) {
			e.preventDefault();
			var $btn = $(this);
			var chain   = $btn.data('chain');
			var addr    = $btn.data('addr');
			var token   = $btn.data('token');
			var network = $btn.data('network');

			$('.gd-chain-btn').removeClass('active');
			$btn.addClass('active');

			$('#gdDefiAddr').text(addr);
			$('#gdNetName').text(network);
			$('#gdTokenName').text(token);
			$('#gabochie_defi_chain').val(chain);

			if (addr) {
				var qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' + encodeURIComponent(addr);
				$('#gdDefiQR').attr('src', qrUrl);
			}
		});

		$(document.body).on('click', '#gdCopyAddrBtn', function(e) {
			e.preventDefault();
			var $btn = $(this);
			var addr = $('#gdDefiAddr').text().trim();
			if (!addr) return;

			if (navigator.clipboard && window.isSecureContext) {
				navigator.clipboard.writeText(addr).then(function() {
					showCopied($btn);
				});
			} else {
				var $temp = $('<input>');
				$('body').append($temp);
				$temp.val(addr).select();
				document.execCommand('copy');
				$temp.remove();
				showCopied($btn);
			}
		});

		function showCopied($btn) {
			var orig = $btn.text();
			$btn.text('COPIED!').css('background', '#2ea368').css('color', '#fff');
			setTimeout(function() {
				$btn.text(orig).css('background', '#C9A96E').css('color', '#0A4840');
			}, 1800);
		}
	});

})(jQuery);
