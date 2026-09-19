/**
 * Staff Check-in Scanner JavaScript for Frontend Page
 *
 * @package MovieBookingSystem
 */

(function($) {
	'use strict';

	let html5QrCode = null;

	$(document).ready(function() {
		if ($('#mbs-checkin-app').length) {
			initCheckinScanner();
		}
	});

	function initCheckinScanner() {
		$('#mbs-btn-start-camera').on('click', function() {
			if (typeof Html5Qrcode === 'undefined') {
				alert('Thư viện camera chưa sẵn sàng.');
				return;
			}

			html5QrCode = new Html5Qrcode('mbs-qr-reader');
			const config = { fps: 10, qrbox: { width: 250, height: 250 } };

			html5QrCode.start(
				{ facingMode: 'environment' },
				config,
				function(qrCodeMessage) {
					// Scanned successfully!
					playSuccessSound();
					verifyTicketCode(qrCodeMessage);
					html5QrCode.stop().then(function() {
						$('#mbs-btn-start-camera').show();
						$('#mbs-btn-stop-camera').hide();
					});
				},
				function(errorMessage) {
					// Ignore scanning frame mismatches
				}
			).then(function() {
				$('#mbs-btn-start-camera').hide();
				$('#mbs-btn-stop-camera').show();
			}).catch(function(err) {
				alert('Không thể mở camera: ' + err);
			});
		});

		$('#mbs-btn-stop-camera').on('click', function() {
			if (html5QrCode) {
				html5QrCode.stop().then(function() {
					$('#mbs-btn-start-camera').show();
					$('#mbs-btn-stop-camera').hide();
				});
			}
		});
	}

	window.mbsManualCheckin = function(e) {
		e.preventDefault();
		const code = $('#mbs-checkin-ticket-code').val().trim();
		if (code) {
			verifyTicketCode(code);
		}
	};

	function verifyTicketCode(code) {
		$('#mbs-checkin-result-placeholder').hide();
		$('#mbs-checkin-result-content').show().html('<div class="mbs-spinner"></div>');

		$.ajax({
			url: mbsData.restUrl + 'admin/checkin',
			type: 'POST',
			headers: { 'X-WP-Nonce': mbsData.restNonce },
			contentType: 'application/json',
			data: JSON.stringify({ ticket_code: code }),
			success: function(res) {
				if (res.success) {
					playSuccessSound();
					let html = `
						<div style="background:rgba(34, 197, 94, 0.15); border: 2px solid #22c55e; border-radius:14px; padding:20px; color:#f0fdf4;">
							<h2 style="color:#4ade80;margin:0 0 10px;font-size:22px;">✅ SOÁT VÉ THÀNH CÔNG!</h2>
							<p style="font-size:15px;line-height:1.6;margin:0;">
								<strong>Phim:</strong> ${res.movie_title}<br>
								<strong>Rạp:</strong> ${res.cinema_name} - ${res.room_name}<br>
								<strong>Suất Chiếu:</strong> ${res.start_time} (${res.show_date})<br>
								<strong>Vị Trí Ghế:</strong> <span style="font-size:24px;color:#ffb800;font-weight:bold;">${res.seat_label}</span> (${res.seat_type})<br>
								<strong>Khách Hàng:</strong> ${res.customer_name} (${res.customer_phone})<br>
								<strong>Mã Vé:</strong> <code>${res.ticket_code}</code><br>
								<small style="color:#94a3b8;">Thời gian quét: ${res.checkin_time}</small>
							</p>
						</div>
					`;
					$('#mbs-checkin-result-content').html(html);
					$('#mbs-checkin-ticket-code').val('').focus();
				}
			},
			error: function(xhr) {
				playErrorSound();
				const msg = xhr.responseJSON ? xhr.responseJSON.message : 'Vé không hợp lệ hoặc đã qua sử dụng.';
				let html = `
					<div style="background:rgba(239, 68, 68, 0.15); border: 2px solid #ef4444; border-radius:14px; padding:20px; color:#fef2f2;">
						<h2 style="color:#f87171;margin:0 0 10px;font-size:22px;">❌ LỖI SOÁT VÉ!</h2>
						<p style="font-size:15px;margin:0;">${msg}</p>
					</div>
				`;
				$('#mbs-checkin-result-content').html(html);
				$('#mbs-checkin-ticket-code').select();
			}
		});
	}

	function playSuccessSound() {
		try {
			const ctx = new (window.AudioContext || window.webkitAudioContext)();
			const osc = ctx.createOscillator();
			const gain = ctx.createGain();
			osc.type = 'sine';
			osc.frequency.setValueAtTime(800, ctx.currentTime);
			osc.frequency.setValueAtTime(1200, ctx.currentTime + 0.1);
			gain.gain.setValueAtTime(0.3, ctx.currentTime);
			gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.3);
			osc.connect(gain);
			gain.connect(ctx.destination);
			osc.start();
			osc.stop(ctx.currentTime + 0.3);
		} catch (e) {}
	}

	function playErrorSound() {
		try {
			const ctx = new (window.AudioContext || window.webkitAudioContext)();
			const osc = ctx.createOscillator();
			const gain = ctx.createGain();
			osc.type = 'sawtooth';
			osc.frequency.setValueAtTime(300, ctx.currentTime);
			osc.frequency.setValueAtTime(200, ctx.currentTime + 0.15);
			gain.gain.setValueAtTime(0.4, ctx.currentTime);
			gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.4);
			osc.connect(gain);
			gain.connect(ctx.destination);
			osc.start();
			osc.stop(ctx.currentTime + 0.4);
		} catch (e) {}
	}

})(jQuery);
