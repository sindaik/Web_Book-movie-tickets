/**
 * Frontend Cinema Application JavaScript
 * Movie Booking System Single Page Experience
 */

(function($) {
	'use strict';

	// Auto-attach WP REST Nonce header for all AJAX requests
	if (typeof mbsData !== 'undefined' && mbsData.restNonce) {
		$.ajaxSetup({
			headers: {
				'X-WP-Nonce': mbsData.restNonce
			}
		});
	}

	// State
	window.mbsState = {
		movies: [],
		genres: [],
		cinemas: [],
		promotions: [],
		coupons: [],
		currentStatusTab: 'now_showing',
		currentPromoTab: 'all',
		currentCinemaCity: 'all',
		currentMovie: null,
		currentShowtime: null,
		selectedSeats: [], // Array of seat objects { id, label, price, type }
		combosCatalog: [],
		selectedCombos: {}, // { combo_id: qty }
		coupon: null, // { code, discount_amount }
		lockTimer: null,
		lockSecondsRemaining: 600,
	};

	$(document).ready(function() {
		// Initialize Main Booking App if element exists
		if ($('#mbs-app').length) {
			initMainApp();
		}

		// Initialize User Dashboard if element exists
		if ($('#mbs-user-dashboard').length) {
			if (mbsData.isLoggedIn) {
				initUserDashboard();
			}
			if (window.location.hash === '#lookup' || window.location.search.indexOf('lookup') !== -1) {
				setTimeout(function() {
					const $inp = $('#mbs-lookup-query');
					if ($inp.length) {
						$inp.focus();
						const lookupElem = document.getElementById('lookup');
						if (lookupElem) lookupElem.scrollIntoView({ behavior: 'smooth' });
					}
				}, 300);
			}
		}
	});

	/* =============================================================
	 * 1. Initialize Main App & Fetch Movies
	 * ============================================================= */
	function initMainApp() {
		initHeroSlider();
		loadMovies();
		loadCombos();
		loadPromotions();
		loadCinemas();
		bindFilterEvents();
		bindPromoAndCinemaEvents();
	}

	/* --- Hero Banner Slider Controller --- */
	let currentSlide = 0;
	let slideInterval = null;

	function initHeroSlider() {
		const $slides = $('.mbs-banner-slide');
		if ($slides.length <= 1) return;

		startSlideTimer();

		$('#mbs-hero-banner').on('mouseenter', function() {
			clearInterval(slideInterval);
		}).on('mouseleave', function() {
			startSlideTimer();
		});
	}

	function startSlideTimer() {
		clearInterval(slideInterval);
		slideInterval = setInterval(function() {
			window.mbsNextSlide();
		}, 5000);
	}

	window.mbsGoToSlide = function(index) {
		const $slides = $('.mbs-banner-slide');
		const $dots = $('.mbs-slider-dot');
		if (!$slides.length) return;

		currentSlide = (index + $slides.length) % $slides.length;

		$slides.removeClass('active').eq(currentSlide).addClass('active');
		$dots.removeClass('active').eq(currentSlide).addClass('active');
		startSlideTimer();
	};

	window.mbsNextSlide = function() {
		const $slides = $('.mbs-banner-slide');
		if (!$slides.length) return;
		window.mbsGoToSlide(currentSlide + 1);
	};

	window.mbsPrevSlide = function() {
		const $slides = $('.mbs-banner-slide');
		if (!$slides.length) return;
		window.mbsGoToSlide(currentSlide - 1);
	};

	function loadMovies() {
		$.ajax({
			url: mbsData.restUrl + 'movies',
			type: 'GET',
			data: { status: 'all' },
			success: function(res) {
				if (!res.success) return;
				mbsState.movies = res.movies;
				mbsState.genres = res.genres;
				mbsState.cinemas = res.cinemas;

				populateFilterDropdowns();
				renderMoviesGrid();
			},
			error: function() {
				$('#mbs-movies-grid').html('<p style="text-align:center;color:#ef4444;">Không thể tải dữ liệu phim từ máy chủ.</p>');
			}
		});
	}

	function loadCombos() {
		$.get(mbsData.restUrl + 'combos', function(res) {
			if (res.success) {
				mbsState.combosCatalog = res.combos;
				renderCombos();
			}
		});
	}

	function loadPromotions() {
		$.get(mbsData.restUrl + 'promotions', function(res) {
			if (!res.success) return;
			mbsState.promotions = res.promotions || [];
			mbsState.coupons = res.coupons || [];
			if (res.combos && res.combos.length) {
				mbsState.combosCatalog = res.combos;
			}
			renderPromotionsGrid();
		});
	}

	function renderPromotionsGrid() {
		const $grid = $('#mbs-promotions-grid');
		if (!$grid.length) return;

		const tab = mbsState.currentPromoTab || 'all';
		let html = '';

		// 1. Render Vouchers
		if (tab === 'all' || tab === 'voucher') {
			(mbsState.promotions || []).forEach(function(p) {
				html += `
					<div class="mbs-promo-card">
						<div class="mbs-promo-tag" style="background:${p.color};"><i class="fa-solid ${p.icon || 'fa-tag'}"></i> ${p.tag}</div>
						<div class="mbs-promo-header">
							<div class="mbs-promo-discount">${p.discount}</div>
							<span class="mbs-promo-badge">${p.badge}</span>
						</div>
						<h3 class="mbs-promo-title">${p.title}</h3>
						<p class="mbs-promo-desc">${p.desc}</p>
						<div class="mbs-promo-footer">
							<div class="mbs-promo-code-box">
								<span>MÃ: <strong>${p.code}</strong></span>
							</div>
							<button type="button" class="mbs-btn mbs-btn-primary mbs-btn-sm" onclick="mbsCopyPromoCode('${p.code}', this)">
								<i class="fa-solid fa-copy"></i> Sao Chép Mã
							</button>
						</div>
					</div>
				`;
			});
		}

		// 2. Render Combos
		if (tab === 'all' || tab === 'combo') {
			(mbsState.combosCatalog || []).forEach(function(c) {
				html += `
					<div class="mbs-promo-card mbs-combo-card">
						<div class="mbs-promo-tag" style="background:#ff3366;"><i class="fa-solid fa-bowl-food"></i> COMBO BẮP NƯỚC</div>
						<div class="mbs-promo-header">
							<div class="mbs-promo-discount">${parseInt(c.price).toLocaleString()}đ</div>
							<span class="mbs-promo-badge">COMBO</span>
						</div>
						<h3 class="mbs-promo-title">${c.name}</h3>
						<p class="mbs-promo-desc">${c.desc}</p>
						<div class="mbs-promo-footer">
							<span class="mbs-combo-price-label">Giá ưu đãi rạp</span>
							<button type="button" class="mbs-btn mbs-btn-primary mbs-btn-sm" onclick="mbsOrderComboAction()">
								<i class="fa-solid fa-ticket"></i> Đặt Vé & Combo
							</button>
						</div>
					</div>
				`;
			});
		}

		$grid.html(html || '<p style="color:#94a3b8;grid-column:1/-1;text-align:center;padding:20px;">Không có ưu đãi nào trong mục này.</p>');
	}

	window.mbsCopyPromoCode = function(code, btn) {
		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(code).then(function() {
				const $btn = $(btn);
				const orig = $btn.html();
				$btn.html('<i class="fa-solid fa-check"></i> Đã Sao Chép!').addClass('mbs-copied-btn');
				setTimeout(function() {
					$btn.html(orig).removeClass('mbs-copied-btn');
				}, 2000);
			}).catch(function() {
				prompt('Mã ưu đãi của bạn (Copy mã bên dưới):', code);
			});
		} else {
			prompt('Mã ưu đãi của bạn (Copy mã bên dưới):', code);
		}
	};

	window.mbsOrderComboAction = function() {
		const moviesEl = document.getElementById('movies');
		if (moviesEl) {
			moviesEl.scrollIntoView({ behavior: 'smooth' });
		}
	};

	function loadCinemas() {
		$.get(mbsData.restUrl + 'cinemas', function(res) {
			if (!res.success || !res.cinemas) return;
			mbsState.cinemas = res.cinemas;
			populateFilterDropdowns();
			renderCinemasGrid();
		});
	}

	function renderCinemasGrid() {
		const $grid = $('#mbs-cinemas-grid');
		if (!$grid.length) return;

		const city = mbsState.currentCinemaCity || 'all';
		const filtered = (mbsState.cinemas || []).filter(function(c) {
			if (city !== 'all' && c.city.indexOf(city) === -1) return false;
			return true;
		});

		let html = '';
		filtered.forEach(function(c) {
			const formatsHtml = (c.formats || []).map(function(f) {
				return `<span class="mbs-format-chip"><i class="fa-solid fa-film"></i> ${f}</span>`;
			}).join(' ');

			html += `
				<div class="mbs-cinema-card">
					<div class="mbs-cinema-card-top">
						<div class="mbs-cinema-name-group">
							<h3 class="mbs-cinema-title">${c.name}</h3>
							<span class="mbs-cinema-city-badge"><i class="fa-solid fa-location-dot"></i> ${c.city}</span>
						</div>
					</div>
					<div class="mbs-cinema-details">
						<p class="mbs-cinema-detail-row">
							<i class="fa-solid fa-location-dot cinema-primary-icon"></i>
							<span>${c.address}</span>
						</p>
						<p class="mbs-cinema-detail-row">
							<i class="fa-solid fa-phone cinema-primary-icon"></i>
							<span>Hotline: <a href="tel:${c.hotline}">${c.hotline}</a></span>
						</p>
						<div class="mbs-cinema-formats">
							${formatsHtml}
						</div>
					</div>
					<div class="mbs-cinema-card-actions">
						<button type="button" class="mbs-btn mbs-btn-primary mbs-btn-sm" onclick="mbsSelectCinemaAndScroll(${c.id})">
							<i class="fa-solid fa-calendar-check"></i> Xem Lịch Chiếu Tại Rạp
						</button>
						<a href="https://maps.google.com/?q=${encodeURIComponent(c.name + ' ' + c.address)}" target="_blank" class="mbs-btn mbs-btn-secondary mbs-btn-sm">
							<i class="fa-solid fa-map-location-dot"></i> Chỉ Đường
						</a>
					</div>
				</div>
			`;
		});

		$grid.html(html || '<p style="color:#94a3b8;grid-column:1/-1;text-align:center;padding:20px;">Đang cập nhật danh sách cụm rạp tại khu vực này.</p>');
	}

	window.mbsSelectCinemaAndScroll = function(cinemaId) {
		$('#mbs-filter-cinema').val(cinemaId).trigger('change');
		const quickSel = document.getElementById('cinema-quick-location');
		if (quickSel) quickSel.value = cinemaId;
		const moviesEl = document.getElementById('movies');
		if (moviesEl) moviesEl.scrollIntoView({ behavior: 'smooth' });
	};

	function bindPromoAndCinemaEvents() {
		$('#mbs-promo-tabs .mbs-tab-btn').on('click', function() {
			$('#mbs-promo-tabs .mbs-tab-btn').removeClass('active');
			$(this).addClass('active');
			mbsState.currentPromoTab = $(this).data('promo-tab');
			renderPromotionsGrid();
		});

		$('#mbs-cinema-city-tabs .mbs-tab-btn').on('click', function() {
			$('#mbs-cinema-city-tabs .mbs-tab-btn').removeClass('active');
			$(this).addClass('active');
			mbsState.currentCinemaCity = $(this).data('city');
			renderCinemasGrid();
		});
	}

	function populateFilterDropdowns() {
		const $cinemaSel = $('#mbs-filter-cinema');
		$cinemaSel.find('option:not(:first)').remove();
		mbsState.cinemas.forEach(function(c) {
			$cinemaSel.append('<option value="' + c.id + '">' + c.name + '</option>');
		});

		// Also populate header quick selector if available
		const $quickSel = $('#cinema-quick-location');
		if ($quickSel.length) {
			$quickSel.empty();
			mbsState.cinemas.forEach(function(c) {
				let cleanName = c.name.replace(/^(Cinemagic|CGV|Lotte Cinema|BHD Star)\s+/i, '');
				$quickSel.append('<option value="' + c.id + '" title="' + c.name + '">' + (cleanName || c.name) + '</option>');
			});
		}

		const $genreSel = $('#mbs-filter-genre');
		$genreSel.find('option:not(:first)').remove();
		mbsState.genres.forEach(function(g) {
			$genreSel.append('<option value="' + g.slug + '">' + g.name + '</option>');
		});
	}

	function bindFilterEvents() {
		$('.mbs-tab-btn').on('click', function() {
			$('.mbs-tab-btn').removeClass('active');
			$(this).addClass('active');
			mbsState.currentStatusTab = $(this).data('tab');
			$('#mbs-filter-status').val(mbsState.currentStatusTab);
			renderMoviesGrid();
		});

		$('#mbs-filter-status').on('change', function() {
			const val = $(this).val();
			mbsState.currentStatusTab = val;
			$('.mbs-tab-btn').removeClass('active');
			$('.mbs-tab-btn[data-tab="' + val + '"]').addClass('active');
			renderMoviesGrid();
		});

		$('#mbs-filter-cinema, #mbs-filter-genre').on('change', renderMoviesGrid);
		$('#mbs-search-input').on('input', renderMoviesGrid);
	}

	function renderMoviesGrid() {
		const $grid = $('#mbs-movies-grid');
		const search = $('#mbs-search-input').val().toLowerCase().trim();
		const genre = $('#mbs-filter-genre').val();
		const status = mbsState.currentStatusTab;

		let filtered = mbsState.movies.filter(function(m) {
			// Status check
			if (status !== 'all' && m.status !== status) return false;
			// Genre check
			if (genre) {
				const hasGenre = m.genres.some(function(g) { return g.toLowerCase() === genre.toLowerCase(); });
				if (!hasGenre) return false;
			}
			// Search check
			if (search) {
				const inTitle = m.title.toLowerCase().includes(search);
				const inDirector = (m.director || '').toLowerCase().includes(search);
				const inCast = (m.cast || '').toLowerCase().includes(search);
				if (!inTitle && !inDirector && !inCast) return false;
			}
			return true;
		});

		if (!filtered.length) {
			$grid.html('<p style="grid-column:1/-1;text-align:center;padding:40px 0;color:#94a3b8;">Không tìm thấy phim phù hợp với bộ lọc.</p>');
			return;
		}

		let html = '';
		filtered.forEach(function(m) {
			const genresStr = m.genres.join(' • ');
			html += `
				<div class="mbs-movie-card">
					<div class="mbs-poster-wrap">
						<img src="${m.poster}" alt="${m.title}" class="mbs-poster-img" loading="lazy" />
						<div class="mbs-card-badges">
							<span class="mbs-badge-rating"><i class="fa-solid fa-star"></i> ${m.rating_score}</span>
							<span class="mbs-age">${m.age_rating}</span>
						</div>
						<div class="mbs-card-overlay">
							${m.trailer_url ? `<button type="button" class="mbs-play-trailer-btn" onclick="mbsPlayTrailer('${m.trailer_url}')" title="Xem Trailer"><i class="fa-solid fa-play"></i></button>` : ''}
						</div>
					</div>
					<div class="mbs-card-info">
						<div>
							<h3 class="mbs-movie-title">${m.title}</h3>
							<div class="mbs-movie-genres">${genresStr}</div>
							<div class="mbs-movie-meta-row">
								<span><i class="fa-solid fa-clock"></i> ${m.duration} phút</span>
								<span class="mbs-format">${m.formats.length ? m.formats[0] : '2D'}</span>
							</div>
						</div>
						<button class="mbs-btn mbs-btn-primary mbs-btn-block" onclick="mbsOpenMovieQuick(${m.id})">
							<i class="fa-solid fa-ticket"></i> Mua Vé
						</button>
					</div>
				</div>
			`;
		});

		$grid.html(html);
	}

	/* =============================================================
	 * 2. Movie Detail Modal & Showtime Selection
	 * ============================================================= */
	window.mbsOpenMovieQuick = function(movieId) {
		$('#mbs-movie-modal-body').html('<div class="mbs-spinner"></div>');
		$('#mbs-movie-modal').addClass('active');

		$.ajax({
			url: mbsData.restUrl + 'movies/' + movieId,
			type: 'GET',
			success: function(res) {
				if (!res.success) return;
				mbsState.currentMovie = res.movie;
				renderMovieDetailsModal(res.movie, res.schedule);
			}
		});
	};

	function renderMovieDetailsModal(movie, schedule) {
		let html = `
			<div class="mbs-movie-detail-grid">
				<div class="mbs-detail-poster">
					<img src="${movie.poster}" alt="${movie.title}" />
				</div>
				<div class="mbs-detail-info">
					<div class="mbs-detail-header">
						<h2>${movie.title}</h2>
						<div class="mbs-detail-meta">
							<span class="mbs-rating"><i class="fa-solid fa-star"></i> ${movie.rating_score}/10</span>
							<span><i class="fa-solid fa-clock"></i> ${movie.duration} phút</span>
							<span class="mbs-age">${movie.age_rating}</span>
						</div>
					</div>
					<div class="mbs-detail-desc">${movie.description || ''}</div>
					<div style="font-size:13px;color:#94a3b8;margin-bottom:20px;">
						<strong>Đạo diễn:</strong> ${movie.director || 'N/A'}<br>
						<strong>Diễn viên:</strong> ${movie.cast || 'N/A'}<br>
						<strong>Bản chiếu:</strong> ${movie.language || 'Phụ đề Tiếng Việt'}
					</div>

					<div class="mbs-schedule-wrap">
						<h3>Chọn Suất Chiếu</h3>
		`;

		if (!schedule || !schedule.length) {
			html += `<p style="color:#94a3b8;">Hiện chưa có lịch chiếu khả dụng cho phim này.</p>`;
		} else {
			schedule.forEach(function(cinema, cIdx) {
				html += `
					<div class="mbs-cinema-block">
						<div class="mbs-cinema-name"><i class="fa-solid fa-location-dot"></i> ${cinema.cinema_name}</div>
						<div class="mbs-cinema-addr">${cinema.cinema_address || ''}</div>
				`;

				if (cinema.dates && cinema.dates.length) {
					cinema.dates.forEach(function(dateObj, dIdx) {
						html += `
							<div style="margin-bottom:12px;">
								<span style="display:block;font-size:13px;font-weight:600;color:#38bdf8;margin-bottom:6px;"><i class="fa-solid fa-calendar-days"></i> ${dateObj.date_formatted}:</span>
								<div class="mbs-time-slots">
						`;
						dateObj.showtimes.forEach(function(st) {
							html += `
								<button type="button" class="mbs-time-btn" onclick="mbsSelectShowtime(${st.showtime_id})">
									<strong>${st.start_time}</strong>
									<span>${st.format} • ${st.room_name}</span>
								</button>
							`;
						});
						html += `</div></div>`;
					});
				}
				html += `</div>`;
			});
		}

		html += `</div></div></div>`;
		$('#mbs-movie-modal-body').html(html);
	}

	/* =============================================================
	 * 3. Seat Map Selection Modal
	 * ============================================================= */
	window.mbsSelectShowtime = function(showtimeId) {
		mbsCloseModal('mbs-movie-modal');
		$('#mbs-seat-modal').addClass('active');
		$('#mbs-seats-canvas').html('<div class="mbs-spinner"></div>');

		// Reset selection
		mbsState.selectedSeats = [];
		mbsState.selectedCombos = {};
		mbsState.coupon = null;
		updateCartSummary();

		$.ajax({
			url: mbsData.restUrl + 'showtimes/' + showtimeId + '/seats',
			type: 'GET',
			data: { session_token: mbsData.sessionToken },
			success: function(res) {
				if (!res.success) return;
				mbsState.currentShowtime = res.showtime;
				$('#mbs-seat-movie-title').text(res.showtime.movie_title);
				$('#mbs-seat-showtime-meta').text(`${res.showtime.cinema_name} • ${res.showtime.room_name} (${res.showtime.format}) • ${res.showtime.start_time} ${res.showtime.show_date}`);
				renderSeatGrid(res.seats, res.showtime);
			}
		});
	};

	function renderSeatGrid(seats, showtime) {
		const $canvas = $('#mbs-seats-canvas');
		$canvas.empty();

		// Group seats by row
		const rows = {};
		seats.forEach(function(s) {
			if (!rows[s.row]) rows[s.row] = [];
			rows[s.row].push(s);
		});

		Object.keys(rows).sort().forEach(function(rowLetter) {
			const $rowDiv = $('<div class="mbs-seat-row"></div>');
			$rowDiv.append(`<span class="mbs-row-lbl">${rowLetter}</span>`);

			rows[rowLetter].forEach(function(seat) {
				const isBooked = seat.status === 'booked';
				const isLocked = seat.status === 'locked';
				const isSelected = seat.status === 'selected';

				let statusClass = `status-${seat.status}`;
				if (isSelected) {
					mbsState.selectedSeats.push(seat);
				}

				const $seatBtn = $(`
					<button type="button" class="mbs-seat-item type-${seat.type} ${statusClass}" 
						data-id="${seat.id}" data-label="${seat.label}" data-price="${seat.price}" data-type="${seat.type}">
						${seat.label}
					</button>
				`);

				if (!isBooked && !isLocked) {
					$seatBtn.on('click', function() {
						toggleSeatSelection($(this), seat);
					});
				}

				$rowDiv.append($seatBtn);
			});

			$canvas.append($rowDiv);
		});

		updateCartSummary();
	}

	function toggleSeatSelection($btn, seat) {
		const idx = mbsState.selectedSeats.findIndex(function(s) { return s.id === seat.id; });

		if (idx > -1) {
			// Deselect
			mbsState.selectedSeats.splice(idx, 1);
			$btn.removeClass('status-selected');
		} else {
			// Select (Max 8 seats per booking)
			if (mbsState.selectedSeats.length >= 8) {
				alert('Bạn chỉ có thể đặt tối đa 8 ghế trong một giao dịch.');
				return;
			}
			mbsState.selectedSeats.push(seat);
			$btn.addClass('status-selected');
		}

		// Hold / lock seats on server
		syncSeatLockOnServer();
		updateCartSummary();
	}

	function syncSeatLockOnServer() {
		if (!mbsState.selectedSeats.length) {
			stopLockTimer();
			$.post(mbsData.restUrl + 'seats/release', {
				showtime_id: mbsState.currentShowtime.id,
				session_token: mbsData.sessionToken
			});
			return;
		}

		const seatIds = mbsState.selectedSeats.map(function(s) { return s.id; });

		$.ajax({
			url: mbsData.restUrl + 'seats/hold',
			type: 'POST',
			contentType: 'application/json',
			data: JSON.stringify({
				showtime_id: mbsState.currentShowtime.id,
				seat_ids: seatIds,
				session_token: mbsData.sessionToken
			}),
			success: function(res) {
				if (res.success) {
					startLockTimer(600); // 10 minutes
				}
			},
			error: function(xhr) {
				const msg = xhr.responseJSON ? xhr.responseJSON.message : 'Ghế vừa có người giữ chỗ.';
				alert(msg);
				// Refresh seat map
				mbsSelectShowtime(mbsState.currentShowtime.id);
			}
		});
	}

	function startLockTimer(seconds) {
		stopLockTimer();
		mbsState.lockSecondsRemaining = seconds;
		$('#mbs-lock-timer').show();

		mbsState.lockTimer = setInterval(function() {
			mbsState.lockSecondsRemaining--;
			if (mbsState.lockSecondsRemaining <= 0) {
				stopLockTimer();
				alert('Hết thời gian giữ chỗ tạm thời (10 phút). Ghế đã được tự động giải phóng.');
				mbsCloseSeatModal();
			} else {
				const mins = Math.floor(mbsState.lockSecondsRemaining / 60);
				const secs = mbsState.lockSecondsRemaining % 60;
				$('#mbs-timer-digits').text(
					String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0')
				);
			}
		}, 1000);
	}

	function stopLockTimer() {
		if (mbsState.lockTimer) {
			clearInterval(mbsState.lockTimer);
			mbsState.lockTimer = null;
		}
		$('#mbs-lock-timer').hide();
	}

	/* =============================================================
	 * 4. Concession Combos & Cart Calculation
	 * ============================================================= */
	function renderCombos() {
		const $list = $('#mbs-combos-list');
		$list.empty();

		mbsState.combosCatalog.forEach(function(combo) {
			$list.append(`
				<div class="mbs-combo-item">
					<div class="mbs-combo-info">
						<strong>${combo.name}</strong>
						<span>${combo.desc}</span>
						<div class="mbs-combo-price">${parseInt(combo.price).toLocaleString()}đ</div>
					</div>
					<div class="mbs-counter">
						<button type="button" class="mbs-counter-btn" onclick="mbsUpdateCombo('${combo.id}', -1)">-</button>
						<span class="mbs-counter-val" id="mbs-combo-qty-${combo.id}">0</span>
						<button type="button" class="mbs-counter-btn" onclick="mbsUpdateCombo('${combo.id}', 1)">+</button>
					</div>
				</div>
			`);
		});
	}

	window.mbsUpdateCombo = function(comboId, change) {
		const cur = mbsState.selectedCombos[comboId] || 0;
		const next = Math.max(0, cur + change);
		mbsState.selectedCombos[comboId] = next;
		$('#mbs-combo-qty-' + comboId).text(next);
		updateCartSummary();
	};

	window.mbsApplyCoupon = function() {
		const code = $('#mbs-coupon-input').val().trim();
		if (!code) return;

		// Calculate current subtotal first
		let subtotal = calculateTicketsTotal() + calculateCombosTotal();

		$.ajax({
			url: mbsData.restUrl + 'coupon/apply',
			type: 'POST',
			contentType: 'application/json',
			data: JSON.stringify({ code: code, amount: subtotal }),
			success: function(res) {
				if (res.success) {
					mbsState.coupon = res.coupon;
					$('#mbs-coupon-message').removeClass('mbs-alert-error').addClass('mbs-alert-success').text(res.message).show();
					updateCartSummary();
				}
			},
			error: function(xhr) {
				mbsState.coupon = null;
				const msg = xhr.responseJSON ? xhr.responseJSON.message : 'Mã giảm giá không hợp lệ.';
				$('#mbs-coupon-message').removeClass('mbs-alert-success').addClass('mbs-alert-error').text(msg).show();
				updateCartSummary();
			}
		});
	};

	function calculateTicketsTotal() {
		return mbsState.selectedSeats.reduce(function(sum, s) { return sum + parseFloat(s.price); }, 0);
	}

	function calculateCombosTotal() {
		let total = 0;
		mbsState.combosCatalog.forEach(function(c) {
			const qty = mbsState.selectedCombos[c.id] || 0;
			total += qty * c.price;
		});
		return total;
	}

	function updateCartSummary() {
		const ticketsTotal = calculateTicketsTotal();
		const combosTotal  = calculateCombosTotal();
		const subtotal     = ticketsTotal + combosTotal;

		let discount = 0;
		if (mbsState.coupon) {
			discount = mbsState.coupon.discount_amount;
		}

		const finalTotal = Math.max(0, subtotal - discount);

		// Update UI elements
		if (mbsState.selectedSeats.length) {
			const labels = mbsState.selectedSeats.map(function(s) { return s.label; }).join(', ');
			$('#mbs-selected-seats-list').text(labels);
			$('#mbs-proceed-checkout-btn').prop('disabled', false);
		} else {
			$('#mbs-selected-seats-list').text('Chưa chọn ghế');
			$('#mbs-proceed-checkout-btn').prop('disabled', true);
		}

		$('#mbs-tickets-total-amount').text(ticketsTotal.toLocaleString() + 'đ');
		$('#mbs-calc-subtotal').text(subtotal.toLocaleString() + 'đ');

		if (discount > 0) {
			$('#mbs-discount-display').show();
			$('#mbs-calc-discount').text('-' + discount.toLocaleString() + 'đ');
		} else {
			$('#mbs-discount-display').hide();
		}

		$('#mbs-calc-final').text(finalTotal.toLocaleString() + 'đ');
		$('#mbs-modal-final-amount').text(finalTotal.toLocaleString() + 'đ');
	}

	/* =============================================================
	 * 5. Checkout & Payment Dispatch
	 * ============================================================= */
	window.mbsOpenCheckoutForm = function() {
		if (!mbsState.selectedSeats.length) {
			alert('Vui lòng chọn ít nhất một ghế.');
			return;
		}
		$('#mbs-checkout-modal').addClass('active');
	};

	window.mbsSubmitCheckout = function(e) {
		e.preventDefault();
		const $btn = $('#mbs-pay-submit-btn');
		$btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Đang xử lý giao dịch...');

		const payload = {
			customer_name:  $('#mbs-cust-name').val().trim(),
			customer_phone: $('#mbs-cust-phone').val().trim(),
			customer_email: $('#mbs-cust-email').val().trim(),
			showtime_id:    mbsState.currentShowtime.id,
			seat_ids:       mbsState.selectedSeats.map(function(s) { return s.id; }),
			combos:         mbsState.selectedCombos,
			coupon_code:    mbsState.coupon ? mbsState.coupon.code : '',
			payment_method: $('input[name="mbs_payment_method"]:checked').val(),
			session_token:  mbsData.sessionToken
		};

		$.ajax({
			url: mbsData.restUrl + 'booking/checkout',
			type: 'POST',
			headers: { 'X-WP-Nonce': mbsData.restNonce },
			contentType: 'application/json',
			data: JSON.stringify(payload),
			success: function(res) {
				$btn.prop('disabled', false).html('<i class="fa-solid fa-lock"></i> Xác Nhận Đặt Vé & Thanh Toán');
				if (res.success) {
					stopLockTimer();
					mbsCloseModal('mbs-checkout-modal');
					mbsCloseModal('mbs-seat-modal');

					if (res.payment_status === 'redirect' && res.redirect_url) {
						// VNPay Sandbox redirect
						window.location.href = res.redirect_url;
					} else {
						// Show Perforated Electronic Ticket Modal
						showSuccessTicketModal(res.order_code);
					}
				}
			},
			error: function(xhr) {
				$btn.prop('disabled', false).html('<i class="fa-solid fa-lock"></i> Xác Nhận Đặt Vé & Thanh Toán');
				const msg = xhr.responseJSON ? xhr.responseJSON.message : 'Lỗi khi đặt vé.';
				alert(msg);
			}
		});
	};

	function showSuccessTicketModal(orderCode) {
		let $modal = $('#mbs-success-modal');
		if (!$modal.length) {
			$('body').append(`
				<div class="mbs-modal" id="mbs-success-modal">
					<div class="mbs-modal-overlay" onclick="mbsCloseModal('mbs-success-modal')"></div>
					<div class="mbs-modal-dialog mbs-modal-ticket">
						<button class="mbs-modal-close" onclick="mbsCloseModal('mbs-success-modal')"><i class="fa-solid fa-xmark"></i></button>
						<div class="mbs-perforated-ticket" id="mbs-ticket-container"></div>
					</div>
				</div>
			`);
			$modal = $('#mbs-success-modal');
		}

		$('#mbs-ticket-container').html('<div class="mbs-spinner"></div>');
		$modal.addClass('active');

		$.get(mbsData.restUrl + 'orders/' + orderCode, function(res) {
			if (!res.success || !res.order) {
				$('#mbs-ticket-container').html('<p style="padding:30px;text-align:center;color:#ef4444;"><i class="fa-solid fa-circle-exclamation"></i> Không thể tải thông tin vé điện tử.</p>');
				return;
			}
			const o = res.order;
			const tickets = o.tickets || [];
			const seatLabels = tickets.map(function(t) { return t.seat_label; }).join(', ');

			let ticketsQrHtml = '';
			if (tickets.length > 1) {
				ticketsQrHtml = '<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(130px, 1fr));gap:12px;margin-top:10px;">';
				tickets.forEach(function(t) {
					ticketsQrHtml += `
						<div style="background:rgba(255,255,255,0.05);padding:10px;border-radius:10px;text-align:center;">
							<div style="font-weight:700;color:#ffb800;font-size:14px;margin-bottom:6px;">Ghế: ${t.seat_label}</div>
							<div class="mbs-qr-wrapper" style="margin:0 auto 6px;">
								${t.qr_svg ? t.qr_svg : `<img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=${t.ticket_code}" />`}
							</div>
							<div class="mbs-ticket-code-str" style="font-size:11px;">${t.ticket_code}</div>
						</div>
					`;
				});
				ticketsQrHtml += '</div>';
			} else {
				const single = tickets[0];
				const code = single ? single.ticket_code : o.order_code;
				ticketsQrHtml = `
					<div class="mbs-qr-wrapper">
						${single && single.qr_svg ? single.qr_svg : `<img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=${code}" />`}
					</div>
					<div class="mbs-ticket-code-str">${code}</div>
				`;
			}

			let html = `
				<div class="mbs-ticket-header">
					<h2><i class="fa-solid fa-ticket"></i> VÉ XEM PHIM ĐIỆN TỬ</h2>
					<p style="margin:4px 0 0;font-size:13px;opacity:0.9;">Mã Đơn: <strong>${o.order_code}</strong></p>
				</div>
				<div class="mbs-ticket-body">
					<div class="mbs-ticket-movie-title">${o.movie_title || 'Phim Chiếu Rạp Cinemagic'}</div>
					<div class="mbs-ticket-info-grid">
						<div class="mbs-ticket-info-item">
							<span>Rạp Chiếu:</span>
							<strong>${o.cinema_name || 'Cinemagic'}</strong>
						</div>
						<div class="mbs-ticket-info-item">
							<span>Phòng Chiếu:</span>
							<strong>${o.room_name || 'Phòng chiếu'} (${o.format || '2D Digital'})</strong>
						</div>
						<div class="mbs-ticket-info-item">
							<span>Suất Chiếu:</span>
							<strong style="color:#4ade80;">${o.start_time} - ${o.show_date}</strong>
						</div>
						<div class="mbs-ticket-info-item">
							<span>Vị Trí Ghế:</span>
							<strong style="color:#ffb800;font-size:16px;">${seatLabels || 'Ghế tiêu chuẩn'}</strong>
						</div>
						<div class="mbs-ticket-info-item">
							<span>Khách Hàng:</span>
							<strong>${o.customer_name}</strong>
						</div>
						<div class="mbs-ticket-info-item">
							<span>Tổng Tiền:</span>
							<strong style="color:#00ff88;">${parseInt(o.final_amount).toLocaleString()}đ</strong>
						</div>
					</div>

					<div class="mbs-perforation"><div class="mbs-perf-dash"></div></div>

					<div class="mbs-ticket-qr-section">
						<p style="margin-top:0;font-size:12px;color:#94a3b8;"><i class="fa-solid fa-qrcode"></i> Quét mã QR tại quầy soát vé rạp chiếu:</p>
						${ticketsQrHtml}
					</div>
				</div>
			`;

			$('#mbs-ticket-container').html(html);
		});
	}

	/* =============================================================
	 * 6. User Dashboard (Vé Của Tôi)
	 * ============================================================= */
	function initUserDashboard() {
		$.ajax({
			url: mbsData.restUrl + 'user/orders',
			type: 'GET',
			headers: { 'X-WP-Nonce': mbsData.restNonce },
			success: function(res) {
				if (!res.success) return;
				if (res.points !== undefined) {
					$('.mbs-points-val').html('<i class="fa-solid fa-star"></i> ' + Number(res.points).toLocaleString() + ' điểm');
				}
				renderUserOrders(res.orders || []);
			},
			error: function() {
				$('#mbs-user-orders-list').html('<p style="color:#94a3b8;text-align:center;padding:20px;">Không thể tải lịch sử vé từ máy chủ.</p>');
			}
		});
	}

	function renderUserOrders(orders) {
		const $list = $('#mbs-user-orders-list');
		if (!orders || !orders.length) {
			$list.html('<p style="color:#94a3b8;padding:30px 0;text-align:center;"><i class="fa-solid fa-ticket-simple" style="font-size:32px;display:block;margin-bottom:12px;opacity:0.5;"></i>Bạn chưa có vé nào. Hãy đặt vé ngay để thưởng thức những bộ phim bom tấn đỉnh cao!</p>');
			return;
		}

		let html = '';
		orders.forEach(function(o) {
			const seatLabels = (o.tickets || []).map(function(t) { return t.seat_label; }).join(', ');
			const isPaid = o.payment_status === 'paid';
			const isPending = o.payment_status === 'pending';
			const isCancelled = o.payment_status === 'cancelled';

			let statusBadge = '<span class="mbs-status-badge mbs-status-paid"><i class="fa-solid fa-circle-check"></i> Đã Thanh Toán</span>';
			if (isPending) {
				statusBadge = '<span class="mbs-status-badge mbs-status-pending"><i class="fa-solid fa-clock"></i> Chờ Thanh Toán</span>';
			} else if (isCancelled) {
				statusBadge = '<span class="mbs-status-badge mbs-status-cancelled"><i class="fa-solid fa-circle-xmark"></i> Đã Hủy Vé</span>';
			}

			let combosHtml = '';
			if (o.combos && o.combos.length) {
				const comboNames = o.combos.map(function(c) { return `${c.name} (x${c.qty})`; }).join(', ');
				combosHtml = `<p class="mbs-order-combo-text"><i class="fa-solid fa-bowl-food cinema-primary-icon"></i> Combo: <strong>${comboNames}</strong></p>`;
			}

			html += `
				<div class="mbs-order-history-card">
					<div class="mbs-order-meta">
						<div class="mbs-order-header-row">
							<strong class="mbs-order-movie-title">${o.movie_title || 'Vé Xem Phim Quốc Tế'}</strong>
							${statusBadge}
						</div>
						<p><i class="fa-solid fa-location-dot cinema-primary-icon"></i> ${o.cinema_name || 'Rạp Cinemagic'} - ${o.room_name || 'Phòng chiếu'} • Suất: <strong>${o.start_time} ${o.show_date}</strong></p>
						<p><i class="fa-solid fa-couch cinema-primary-icon"></i> Ghế: <span style="color:#ffb800;font-weight:bold;">${seatLabels || 'Chưa định danh'}</span> • Tổng tiền: <strong style="color:#00ff88;">${parseInt(o.final_amount).toLocaleString()}đ</strong></p>
						${combosHtml}
						<p style="font-size:12px;opacity:0.75;margin-top:6px;"><i class="fa-solid fa-receipt"></i> Mã đơn: <code>${o.order_code}</code> • Đặt ngày: ${o.created_at}</p>
					</div>
					<div class="mbs-order-actions">
						<button type="button" class="mbs-btn mbs-btn-primary mbs-btn-sm" onclick="mbsViewUserTicket('${o.order_code}')">
							<i class="fa-solid fa-qrcode"></i> Xem Mã QR
						</button>
						${isPaid ? `<button type="button" class="mbs-btn mbs-btn-secondary mbs-btn-sm" onclick="mbsCancelBooking('${o.order_code}')"><i class="fa-solid fa-ban"></i> Hủy Vé</button>` : ''}
					</div>
				</div>
			`;
		});

		$list.html(html);
	}

	window.mbsViewUserTicket = function(orderCode) {
		showSuccessTicketModal(orderCode);
	};

	window.mbsCancelBooking = function(orderCode) {
		if (!confirm('Bạn có chắc chắn muốn hủy đơn vé ' + orderCode + '?')) return;

		$.ajax({
			url: mbsData.restUrl + 'user/cancel-booking',
			type: 'POST',
			headers: { 'X-WP-Nonce': mbsData.restNonce },
			contentType: 'application/json',
			data: JSON.stringify({ order_code: orderCode }),
			success: function(res) {
				if (res.success) {
					alert(res.message);
					initUserDashboard();
				}
			},
			error: function(xhr) {
				const msg = xhr.responseJSON ? xhr.responseJSON.message : 'Không thể hủy vé.';
				alert(msg);
			}
		});
	};

	/* --- Quick Ticket / Order Lookup --- */
	window.mbsLookupTicket = function(e) {
		e.preventDefault();
		const query = $('#mbs-lookup-query').val().trim();
		if (!query) return;

		const $btn = $('#mbs-btn-lookup');
		const $result = $('#mbs-lookup-result');

		$btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Đang tìm...');
		$result.show().html('<div class="mbs-spinner" style="margin:25px auto;"></div>');

		$.ajax({
			url: mbsData.restUrl + 'orders/lookup',
			type: 'POST',
			headers: { 'X-WP-Nonce': mbsData.restNonce },
			contentType: 'application/json',
			data: JSON.stringify({ query: query }),
			success: function(res) {
				$btn.prop('disabled', false).html('<i class="fa-solid fa-search"></i> Tra Cứu Ngay');
				if (res.success && res.orders && res.orders.length) {
					renderLookupResults(res.orders);
				} else {
					$result.html('<div class="mbs-lookup-empty" style="padding:20px;text-align:center;color:#94a3b8;"><i class="fa-solid fa-circle-exclamation" style="font-size:24px;color:#ffb800;display:block;margin-bottom:8px;"></i>Không tìm thấy vé hoặc đơn hàng phù hợp với từ khóa này.</div>');
				}
			},
			error: function(xhr) {
				$btn.prop('disabled', false).html('<i class="fa-solid fa-search"></i> Tra Cứu Ngay');
				const msg = xhr.responseJSON ? xhr.responseJSON.message : 'Lỗi kết nối máy chủ.';
				$result.html(`<div class="mbs-lookup-empty" style="padding:20px;text-align:center;color:#f87171;"><i class="fa-solid fa-circle-xmark" style="font-size:24px;display:block;margin-bottom:8px;"></i>${msg}</div>`);
			}
		});
	};

	function renderLookupResults(orders) {
		const $result = $('#mbs-lookup-result');
		let html = '<div class="mbs-lookup-results-wrap"><h4 style="margin:0 0 15px;color:#00e5ff;font-size:16px;"><i class="fa-solid fa-circle-check" style="color:#22c55e;"></i> Kết quả tra cứu (' + orders.length + ' đơn hàng):</h4>';

		orders.forEach(function(o) {
			const seatLabels = (o.tickets || []).map(function(t) { return t.seat_label; }).join(', ');
			const isPaid = o.payment_status === 'paid';
			const isPending = o.payment_status === 'pending';
			const isCancelled = o.payment_status === 'cancelled';

			let statusBadge = '<span class="mbs-status-badge mbs-status-paid"><i class="fa-solid fa-circle-check"></i> Đã Thanh Toán</span>';
			if (isPending) {
				statusBadge = '<span class="mbs-status-badge mbs-status-pending"><i class="fa-solid fa-clock"></i> Chờ Thanh Toán</span>';
			} else if (isCancelled) {
				statusBadge = '<span class="mbs-status-badge mbs-status-cancelled"><i class="fa-solid fa-circle-xmark"></i> Đã Hủy Vé</span>';
			}

			let combosHtml = '';
			if (o.combos && o.combos.length) {
				const comboNames = o.combos.map(function(c) { return `${c.name} (x${c.qty})`; }).join(', ');
				combosHtml = `<p class="mbs-order-combo-text"><i class="fa-solid fa-bowl-food cinema-primary-icon"></i> Combo: <strong>${comboNames}</strong></p>`;
			}

			html += `
				<div class="mbs-order-history-card" style="margin-bottom:15px;background:rgba(15,23,42,0.8);border:1px solid rgba(0,229,255,0.25);">
					<div class="mbs-order-meta">
						<div class="mbs-order-header-row">
							<strong class="mbs-order-movie-title">${o.movie_title || 'Vé Xem Phim Cinemagic'}</strong>
							${statusBadge}
						</div>
						<p><i class="fa-solid fa-location-dot cinema-primary-icon"></i> ${o.cinema_name || 'Rạp Cinemagic'} - ${o.room_name || 'Phòng chiếu'} • Suất: <strong>${o.start_time} ${o.show_date}</strong></p>
						<p><i class="fa-solid fa-couch cinema-primary-icon"></i> Ghế: <span style="color:#ffb800;font-weight:bold;">${seatLabels || 'Chưa định danh'}</span> • Tổng tiền: <strong style="color:#00ff88;">${parseInt(o.final_amount).toLocaleString()}đ</strong></p>
						${combosHtml}
						<p style="font-size:12px;opacity:0.75;margin-top:6px;"><i class="fa-solid fa-receipt"></i> Mã đơn: <code>${o.order_code}</code> • Khách: <strong>${o.customer_name || 'Khách'}</strong> (${o.customer_phone || ''})</p>
					</div>
					<div class="mbs-order-actions">
						<button type="button" class="mbs-btn mbs-btn-primary mbs-btn-sm" onclick="mbsViewUserTicket('${o.order_code}')">
							<i class="fa-solid fa-qrcode"></i> Xem Vé & Mã QR
						</button>
					</div>
				</div>
			`;
		});

		html += '</div>';
		$result.html(html);
	}

	/* =============================================================
	 * 7. Helpers & YouTube Modal
	 * ============================================================= */
	window.mbsPlayTrailer = function(url) {
		if (!url) return;
		let videoId = '';
		if (url.includes('v=')) {
			videoId = url.split('v=')[1].split('&')[0];
		} else if (url.includes('youtu.be/')) {
			videoId = url.split('youtu.be/')[1].split('?')[0];
		}

		if (videoId) {
			$('#mbs-trailer-container').html(`
				<iframe src="https://www.youtube-nocookie.com/embed/${videoId}?autoplay=1" allow="autoplay; encrypted-media" allowfullscreen></iframe>
			`);
			$('#mbs-trailer-modal').addClass('active');
		} else {
			window.open(url, '_blank');
		}
	};

	window.mbsCloseTrailerModal = function() {
		$('#mbs-trailer-container').empty();
		$('#mbs-trailer-modal').removeClass('active');
	};

	window.mbsCloseSeatModal = function() {
		stopLockTimer();
		$('#mbs-seat-modal').removeClass('active');
		if (mbsState.selectedSeats.length && mbsState.currentShowtime) {
			$.post(mbsData.restUrl + 'seats/release', {
				showtime_id: mbsState.currentShowtime.id,
				session_token: mbsData.sessionToken
			});
		}
	};

	window.mbsCloseModal = function(id) {
		$('#' + id).removeClass('active');
	};

	window.mbsSwitchTab = function(tab) {
		const isHome = window.location.pathname === '/wordpress/' || 
		               window.location.pathname === '/wordpress' || 
		               window.location.pathname === '/' ||
		               document.getElementById('movies') !== null;

		if (!isHome && typeof mbsData !== 'undefined' && mbsData.homeUrl) {
			window.location.href = mbsData.homeUrl + '#movies?tab=' + tab;
			return;
		}

		// Activate tab button
		const $targetBtn = $('.mbs-movies-section .mbs-tab-btn[data-tab="' + tab + '"]');
		if ($targetBtn.length) {
			$targetBtn.trigger('click');
		} else {
			mbsState.currentStatusTab = tab;
			$('#mbs-filter-status').val(tab).trigger('change');
		}

		// Scroll smoothly to movies section
		const el = document.getElementById('movies');
		if (el) {
			el.scrollIntoView({ behavior: 'smooth' });
		}
	};

	// Touch and click support for cinema dropdown navigation
	$(document).ready(function() {
		$('.cinema-dropdown-parent > .cinema-menu-link').on('click', function(e) {
			if (window.innerWidth <= 1024) {
				const $parent = $(this).parent('.cinema-dropdown-parent');
				if (!$parent.hasClass('cinema-dropdown-open')) {
					e.preventDefault();
					$parent.addClass('cinema-dropdown-open');
				}
			}
		});

		$(document).on('click', function(e) {
			if (!$(e.target).closest('.cinema-dropdown-parent').length) {
				$('.cinema-dropdown-parent').removeClass('cinema-dropdown-open');
			}
		});

		// Check for coming_soon in hash or url param
		if (window.location.hash.indexOf('coming_soon') !== -1 || window.location.search.indexOf('tab=coming_soon') !== -1) {
			setTimeout(function() {
				if (window.mbsSwitchTab) window.mbsSwitchTab('coming_soon');
			}, 350);
		}
	});

})(jQuery);

