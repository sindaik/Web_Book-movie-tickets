/**
 * Admin Panel JavaScript - Movie Booking System
 */

(function($) {
	'use strict';

	// Global variables for designer
	window.mbsLayoutMatrix = {};
	let adminQrScanner = null;

	$(document).ready(function() {
		// Init Reports if on reports page
		if ($('#mbsRevenueChart').length) {
			initReportCharts();
		}

		// Init Checkin scanner events if on checkin page
		if ($('#mbs-admin-start-cam').length) {
			initAdminScanner();
		}
	});

	/* -------------------------------------------------------------
	 * 1. Chart.js Revenue Analytics
	 * ------------------------------------------------------------- */
	function initReportCharts() {
		$.ajax({
			url: mbsAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'mbs_get_report_data_ajax',
				_ajax_nonce: mbsAdmin.nonce
			},
			success: function(res) {
				if (!res.success) return;
				const data = res.data;

				// Fill Stats
				$('#mbs-stat-revenue').text(data.summary.total_revenue);
				$('#mbs-stat-tickets').text(data.summary.total_tickets + ' vé');
				$('#mbs-stat-orders').text(data.summary.total_orders + ' đơn');
				$('#mbs-stat-avg').text(data.summary.avg_order_value);

				// Render 7-day Line Chart
				const ctxRev = document.getElementById('mbsRevenueChart').getContext('2d');
				new Chart(ctxRev, {
					type: 'line',
					data: {
						labels: data.days.labels,
						datasets: [{
							label: 'Doanh thu (VNĐ)',
							data: data.days.revenue,
							borderColor: '#e11d48',
							backgroundColor: 'rgba(225, 29, 72, 0.1)',
							borderWidth: 3,
							fill: true,
							tension: 0.35,
							pointBackgroundColor: '#e11d48',
							pointRadius: 5
						}]
					},
					options: {
						responsive: true,
						plugins: {
							legend: { display: false }
						},
						scales: {
							y: {
								beginAtZero: true,
								ticks: {
									callback: function(val) { return val.toLocaleString() + 'đ'; }
								}
							}
						}
					}
				});

				// Render Top Movies Bar Chart
				const ctxMov = document.getElementById('mbsMovieChart').getContext('2d');
				new Chart(ctxMov, {
					type: 'bar',
					data: {
						labels: data.movies.labels,
						datasets: [{
							label: 'Doanh thu phim',
							data: data.movies.revenue,
							backgroundColor: [
								'#38bdf8', '#818cf8', '#fb7185', '#fbbf24', '#34d399'
							],
							borderRadius: 8
						}]
					},
					options: {
						responsive: true,
						plugins: {
							legend: { display: false }
						},
						scales: {
							y: {
								beginAtZero: true,
								ticks: {
									callback: function(val) { return (val/1000).toLocaleString() + 'k'; }
								}
							}
						}
					}
				});
			}
		});
	}

	/* -------------------------------------------------------------
	 * 2. Visual Seat Map Designer
	 * ------------------------------------------------------------- */
	window.mbsNewRoomModal = function() {
		$('#mbs-room-id').val(0);
		$('#mbs-editor-title').text('Thêm Phòng Chiếu Mới');
		$('#mbs-room-name').val('');
		$('#mbs-room-cinema').val('');
		$('#mbs-room-rows').val(8);
		$('#mbs-room-cols').val(10);
		window.mbsLayoutMatrix = {};
		mbsRegenerateSeatPreview();
		$('#mbs-room-editor-box').fadeIn(200);
	};

	window.mbsEditRoomLayout = function(id, cinemaId, name, type, rows, cols) {
		$('#mbs-room-id').val(id);
		$('#mbs-editor-title').text('Cấu Hình: ' + name);
		$('#mbs-room-name').val(name);
		$('#mbs-room-cinema').val(cinemaId);
		$('#mbs-room-type').val(type);
		$('#mbs-room-rows').val(rows);
		$('#mbs-room-cols').val(cols);
		window.mbsLayoutMatrix = {};
		mbsRegenerateSeatPreview();
		$('#mbs-room-editor-box').fadeIn(200);
		$('html, body').animate({ scrollTop: $('#mbs-room-editor-box').offset().top - 40 }, 300);
	};

	window.mbsRegenerateSeatPreview = function() {
		const rows = parseInt($('#mbs-room-rows').val()) || 8;
		const cols = parseInt($('#mbs-room-cols').val()) || 10;
		const rowLetters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('');
		const $container = $('#mbs-designer-seat-grid');
		$container.empty();

		for (let r = 0; r < rows; r++) {
			const letter = rowLetters[r];
			const $rowDiv = $('<div class="mbs-designer-row"></div>');
			$rowDiv.append('<span class="mbs-row-letter">' + letter + '</span>');

			for (let c = 1; c <= cols; c++) {
				const key = letter + c;
				let seatType = window.mbsLayoutMatrix[key]?.type;
				if (!seatType) {
					if (r === rows - 1) seatType = 'couple';
					else if (r >= 3 && r <= 5) seatType = 'vip';
					else seatType = 'regular';
					window.mbsLayoutMatrix[key] = { type: seatType };
				}

				const $btn = $('<button type="button" class="mbs-seat-btn seat-' + seatType + '" data-key="' + key + '">' + key + '</button>');
				$btn.on('click', function() {
					const cur = window.mbsLayoutMatrix[key].type;
					let next = 'regular';
					if (cur === 'regular') next = 'vip';
					else if (cur === 'vip') next = 'couple';
					else if (cur === 'couple') next = 'regular';

					window.mbsLayoutMatrix[key].type = next;
					$(this).removeClass('seat-regular seat-vip seat-couple').addClass('seat-' + next);
				});

				$rowDiv.append($btn);
			}
			$container.append($rowDiv);
		}
	};

	window.mbsSaveRoom = function(e) {
		e.preventDefault();
		const btn = $('#mbs-save-room-btn');
		btn.prop('disabled', true).text('Đang lưu...');

		$.ajax({
			url: mbsAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'mbs_save_room_ajax',
				nonce: mbsAdmin.nonce,
				id: $('#mbs-room-id').val(),
				cinema_id: $('#mbs-room-cinema').val(),
				room_name: $('#mbs-room-name').val(),
				room_type: $('#mbs-room-type').val(),
				total_rows: $('#mbs-room-rows').val(),
				total_cols: $('#mbs-room-cols').val(),
				seat_layout: JSON.stringify(window.mbsLayoutMatrix)
			},
			success: function(res) {
				btn.prop('disabled', false).text('💾 Lưu Cấu Hình Phòng & Sơ Đồ Ghế');
				if (res.success) {
					alert(res.data.message);
					location.reload();
				} else {
					alert(res.data.message || 'Lỗi khi lưu phòng.');
				}
			},
			error: function() {
				btn.prop('disabled', false).text('💾 Lưu Cấu Hình Phòng & Sơ Đồ Ghế');
				alert('Lỗi kết nối máy chủ.');
			}
		});
	};

	/* -------------------------------------------------------------
	 * 3. Coupon Actions
	 * ------------------------------------------------------------- */
	window.mbsSaveCouponAdmin = function(e) {
		e.preventDefault();
		$.ajax({
			url: mbsAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'mbs_save_coupon_ajax',
				nonce: mbsAdmin.nonce,
				code: $('#mbs-cp-code').val(),
				discount_type: $('#mbs-cp-type').val(),
				discount_value: $('#mbs-cp-value').val(),
				min_order_amount: $('#mbs-cp-min').val(),
				usage_limit: $('#mbs-cp-limit').val(),
				expiry_date: $('#mbs-cp-expiry').val()
			},
			success: function(res) {
				if (res.success) {
					alert(res.data.message);
					location.reload();
				} else {
					alert(res.data.message);
				}
			}
		});
	};

	window.mbsDeleteCoupon = function(id) {
		if (!confirm('Bạn có chắc chắn muốn xóa mã giảm giá này?')) return;
		$.ajax({
			url: mbsAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'mbs_delete_coupon_ajax',
				nonce: mbsAdmin.nonce,
				id: id
			},
			success: function(res) {
				if (res.success) {
					location.reload();
				}
			}
		});
	};

	/* -------------------------------------------------------------
	 * 4. View Order Detail
	 * ------------------------------------------------------------- */
	window.mbsViewOrderDetail = function(code) {
		$('#mbs-admin-order-modal').fadeIn(150);
		$('#mbs-admin-order-content').html('<p>Đang tải thông tin đơn hàng...</p>');

		$.get(mbsAdmin.restUrl + 'orders/' + code, function(res) {
			if (!res.success || !res.order) {
				$('#mbs-admin-order-content').html('<p>Không tìm thấy đơn hàng.</p>');
				return;
			}
			const o = res.order;
			let html = '<h2>Chi Tiết Đơn Hàng: ' + o.order_code + '</h2>';
			html += '<p><strong>Phim:</strong> ' + (o.movie_title || '') + '<br>';
			html += '<strong>Rạp:</strong> ' + (o.cinema_name || '') + ' - ' + (o.room_name || '') + ' (' + (o.format || '2D') + ')<br>';
			html += '<strong>Suất chiếu:</strong> ' + (o.start_time || '') + ' ngày ' + (o.show_date || '') + '<br>';
			html += '<strong>Khách hàng:</strong> ' + o.customer_name + ' (' + o.customer_phone + ' - ' + o.customer_email + ')</p>';

			html += '<h4>Danh Sách Vé (' + (o.tickets ? o.tickets.length : 0) + ' vé):</h4><ul>';
			if (o.tickets && o.tickets.length) {
				o.tickets.forEach(function(t) {
					const statusText = (t.checkin_status === 'checked_in') ? '<span style="color:#16a34a;font-weight:bold;">ĐÃ SOÁT</span>' : '<span style="color:#ca8a04;">CHƯA SOÁT</span>';
					html += '<li style="margin-bottom:8px;">Ghế: <strong>' + t.seat_label + '</strong> (' + t.seat_type.toUpperCase() + ') - ' + parseInt(t.price).toLocaleString() + 'đ - Mã vé: <code>' + t.ticket_code + '</code> - ' + statusText + '</li>';
				});
			}
			html += '</ul>';

			html += '<div style="background:#f8fafc;padding:12px;border-radius:8px;margin-top:12px;">';
			html += '<strong>Tổng thanh toán:</strong> ' + parseInt(o.final_amount).toLocaleString() + 'đ (' + o.payment_method.toUpperCase() + ' - ' + o.payment_status.toUpperCase() + ')</div>';

			$('#mbs-admin-order-content').html(html);
		});
	};

	/* -------------------------------------------------------------
	 * 5. Admin Camera Scanner & Manual Checkin
	 * ------------------------------------------------------------- */
	function initAdminScanner() {
		$('#mbs-admin-start-cam').on('click', function() {
			if (typeof Html5Qrcode === 'undefined') {
				alert('Thư viện quét camera chưa được nạp.');
				return;
			}
			adminQrScanner = new Html5Qrcode('mbs-admin-qr-reader');
			adminQrScanner.start(
				{ facingMode: 'environment' },
				{ fps: 10, qrbox: { width: 250, height: 250 } },
				function(qrCodeMessage) {
					// Scanned!
					mbsProcessCheckinCode(qrCodeMessage);
					adminQrScanner.stop();
					$('#mbs-admin-start-cam').show();
					$('#mbs-admin-stop-cam').hide();
				},
				function(errorMessage) {
					// Ignore scanning frames
				}
			).then(function() {
				$('#mbs-admin-start-cam').hide();
				$('#mbs-admin-stop-cam').show();
			}).catch(function(err) {
				alert('Không thể mở camera: ' + err);
			});
		});

		$('#mbs-admin-stop-cam').on('click', function() {
			if (adminQrScanner) {
				adminQrScanner.stop().then(function() {
					$('#mbs-admin-start-cam').show();
					$('#mbs-admin-stop-cam').hide();
				});
			}
		});
	}

	window.mbsAdminCheckinSubmit = function(e) {
		e.preventDefault();
		const code = $('#mbs-admin-code-input').val().trim();
		if (code) {
			mbsProcessCheckinCode(code);
		}
	};

	function mbsProcessCheckinCode(code) {
		$('#mbs-admin-checkin-output').html('<p>Đang kiểm tra vé...</p>');
		$.ajax({
			url: mbsAdmin.restUrl + 'admin/checkin',
			type: 'POST',
			headers: { 'X-WP-Nonce': mbsAdmin.restNonce },
			contentType: 'application/json',
			data: JSON.stringify({ ticket_code: code }),
			success: function(res) {
				if (res.success) {
					let html = '<div class="mbs-checkin-success-box">';
					html += '<h2>✅ SOÁT VÉ THÀNH CÔNG!</h2>';
					html += '<p style="font-size:16px;"><strong>Phim:</strong> ' + res.movie_title + '<br>';
					html += '<strong>Rạp:</strong> ' + res.cinema_name + ' - ' + res.room_name + '<br>';
					html += '<strong>Suất:</strong> ' + res.start_time + ' (' + res.show_date + ')<br>';
					html += '<strong>Ghế ngồi:</strong> <span style="font-size:22px;color:#16a34a;font-weight:bold;">' + res.seat_label + '</span> (' + res.seat_type + ')<br>';
					html += '<strong>Khách hàng:</strong> ' + res.customer_name + ' - ' + res.customer_phone + '<br>';
					html += '<strong>Thời gian check-in:</strong> ' + res.checkin_time + '</p>';
					html += '</div>';
					$('#mbs-admin-checkin-output').html(html);
					$('#mbs-admin-code-input').val('').focus();
				}
			},
			error: function(xhr) {
				const err = xhr.responseJSON ? xhr.responseJSON.message : 'Vé không hợp lệ!';
				let html = '<div class="mbs-checkin-error-box">';
				html += '<h2>❌ LỖI SOÁT VÉ!</h2>';
				html += '<p style="font-size:16px;">' + err + '</p>';
				html += '</div>';
				$('#mbs-admin-checkin-output').html(html);
				$('#mbs-admin-code-input').select();
			}
		});
	}

	/* -------------------------------------------------------------
	 * 5. User Management (Thêm, Sửa, Xóa, Đổi Mật Khẩu)
	 * ------------------------------------------------------------- */
	function generateRandomPassword() {
		const upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
		const lower = 'abcdefghijkmnopqrstuvwxyz';
		const digits = '23456789';
		const special = '@#$%&*';
		let pass = '';
		pass += upper.charAt(Math.floor(Math.random() * upper.length));
		pass += lower.charAt(Math.floor(Math.random() * lower.length));
		pass += digits.charAt(Math.floor(Math.random() * digits.length));
		pass += special.charAt(Math.floor(Math.random() * special.length));
		const all = upper + lower + digits + special;
		for (let i = 0; i < 6; i++) {
			pass += all.charAt(Math.floor(Math.random() * all.length));
		}
		return pass;
	}

	window.mbsCloseAdminModal = function(id) {
		$('#' + id).fadeOut(150);
	};

	window.mbsOpenAddUserModal = function() {
		$('#mbs-add-user-form')[0].reset();
		$('#mbs-new-password').val(generateRandomPassword());
		$('#mbs-add-user-modal').fadeIn(200);
	};

	window.mbsGenerateNewUserPass = function() {
		$('#mbs-new-password').val(generateRandomPassword());
	};

	window.mbsSaveNewUser = function(e) {
		e.preventDefault();
		const $btn = $('#mbs-btn-submit-new-user');
		$btn.prop('disabled', true).text('Đang tạo người dùng...');

		const data = {
			action: 'mbs_add_user_ajax',
			_ajax_nonce: mbsAdmin.nonce,
			username: $('#mbs-new-username').val().trim(),
			email: $('#mbs-new-email').val().trim(),
			display_name: $('#mbs-new-displayname').val().trim(),
			phone: $('#mbs-new-phone').val().trim(),
			role: $('#mbs-new-role').val(),
			password: $('#mbs-new-password').val().trim()
		};

		$.post(mbsAdmin.ajaxUrl, data, function(res) {
			$btn.prop('disabled', false).text('💾 Tạo Người Dùng');
			if (res.success) {
				alert(res.data.message || 'Thêm người dùng mới thành công!');
				window.location.reload();
			} else {
				alert('Lỗi: ' + (res.data.message || 'Không thể tạo người dùng.'));
			}
		}).fail(function() {
			$btn.prop('disabled', false).text('💾 Tạo Người Dùng');
			alert('Đã xảy ra lỗi kết nối máy chủ!');
		});
	};

	window.mbsOpenEditUserModal = function(userId) {
		$.post(mbsAdmin.ajaxUrl, {
			action: 'mbs_get_user_ajax',
			_ajax_nonce: mbsAdmin.nonce,
			user_id: userId
		}, function(res) {
			if (res.success) {
				const u = res.data;
				$('#mbs-edit-user-id').val(u.id);
				$('#mbs-edit-title-username').text(u.username);
				$('#mbs-edit-username').val(u.username);
				$('#mbs-edit-displayname').val(u.display_name);
				$('#mbs-edit-email').val(u.email);
				$('#mbs-edit-phone').val(u.phone);
				$('#mbs-edit-role').val(u.role);
				$('#mbs-edit-user-modal').fadeIn(200);
			} else {
				alert('Lỗi: ' + (res.data.message || 'Không tìm thấy người dùng'));
			}
		});
	};

	window.mbsSaveEditUser = function(e) {
		e.preventDefault();
		const $btn = $('#mbs-btn-submit-edit-user');
		$btn.prop('disabled', true).text('Đang lưu...');

		const data = {
			action: 'mbs_update_user_ajax',
			_ajax_nonce: mbsAdmin.nonce,
			user_id: $('#mbs-edit-user-id').val(),
			display_name: $('#mbs-edit-displayname').val().trim(),
			email: $('#mbs-edit-email').val().trim(),
			phone: $('#mbs-edit-phone').val().trim(),
			role: $('#mbs-edit-role').val()
		};

		$.post(mbsAdmin.ajaxUrl, data, function(res) {
			$btn.prop('disabled', false).text('💾 Lưu Thay Đổi');
			if (res.success) {
				alert(res.data.message || 'Cập nhật thành công!');
				window.location.reload();
			} else {
				alert('Lỗi: ' + (res.data.message || 'Không thể cập nhật.'));
			}
		}).fail(function() {
			$btn.prop('disabled', false).text('💾 Lưu Thay Đổi');
			alert('Đã xảy ra lỗi kết nối máy chủ!');
		});
	};

	window.mbsOpenPasswordModal = function(userId, username) {
		$('#mbs-pass-user-id').val(userId);
		$('#mbs-pass-title-username').text(username);
		$('#mbs-pass-success-box').hide();
		$('#mbs-pass-new-val').val(generateRandomPassword());
		$('#mbs-password-user-modal').fadeIn(200);
	};

	window.mbsGenerateModalPassword = function() {
		$('#mbs-pass-new-val').val(generateRandomPassword());
		$('#mbs-pass-success-box').hide();
	};

	window.mbsCopyModalPassword = function() {
		const val = $('#mbs-pass-new-val').val();
		if (!val) {
			alert('Chưa có mật khẩu để sao chép!');
			return;
		}
		navigator.clipboard.writeText(val).then(function() {
			alert('Đã sao chép mật khẩu vào bộ nhớ tạm: ' + val);
		}).catch(function() {
			const $temp = $('<input>').val(val).appendTo('body').select();
			document.execCommand('copy');
			$temp.remove();
			alert('Đã sao chép mật khẩu vào bộ nhớ tạm: ' + val);
		});
	};

	window.mbsSaveUserPassword = function(e) {
		e.preventDefault();
		const $btn = $('#mbs-btn-submit-pass');
		const newPass = $('#mbs-pass-new-val').val().trim();
		if (newPass.length < 6) {
			alert('Mật khẩu tối thiểu phải 6 ký tự!');
			return;
		}

		$btn.prop('disabled', true).text('Đang cập nhật...');

		$.post(mbsAdmin.ajaxUrl, {
			action: 'mbs_update_user_password_ajax',
			_ajax_nonce: mbsAdmin.nonce,
			user_id: $('#mbs-pass-user-id').val(),
			new_password: newPass
		}, function(res) {
			$btn.prop('disabled', false).text('💾 Lưu Mật Khẩu Mới');
			if (res.success) {
				$('#mbs-pass-copied-val').text(newPass);
				$('#mbs-pass-success-box').slideDown(200);
				alert('Đã cập nhật mật khẩu mới thành công!\nMật khẩu mới: ' + newPass);
			} else {
				alert('Lỗi: ' + (res.data.message || 'Không thể đổi mật khẩu.'));
			}
		}).fail(function() {
			$btn.prop('disabled', false).text('💾 Lưu Mật Khẩu Mới');
			alert('Đã xảy ra lỗi kết nối máy chủ!');
		});
	};

	window.mbsDeleteUser = function(userId, username) {
		if (!confirm('Bạn có chắc chắn muốn xóa vĩnh viễn người dùng "' + username + '" (ID: #' + userId + ')?\nThao tác này không thể hoàn tác!')) {
			return;
		}

		$.post(mbsAdmin.ajaxUrl, {
			action: 'mbs_delete_user_ajax',
			_ajax_nonce: mbsAdmin.nonce,
			user_id: userId
		}, function(res) {
			if (res.success) {
				alert(res.data.message || 'Đã xóa người dùng thành công!');
				window.location.reload();
			} else {
				alert('Lỗi: ' + (res.data.message || 'Không thể xóa người dùng.'));
			}
		}).fail(function() {
			alert('Đã xảy ra lỗi kết nối máy chủ!');
		});
	};

})(jQuery);

