<?php
/**
 * Reports & Revenue Analytics View (Chart.js)
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="wrap mbs-admin-wrap">
	<div class="mbs-header-row">
		<h1><i class="fa-solid fa-chart-line" style="color: #4f46e5; margin-right: 8px;"></i> Báo Cáo Doanh Thu & Hiệu Suất Rạp Phim</h1>
		<div class="mbs-header-actions">
			<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=mbs-orders&mbs_export_orders=1' ), 'mbs_export_orders_nonce' ) ); ?>" class="button button-primary" style="display:inline-flex;align-items:center;gap:6px;">
				<i class="fa-solid fa-file-excel"></i> <span>Xuất Báo Cáo Ra Excel/CSV</span>
			</a>
		</div>
	</div>

	<!-- KPI Stat Cards -->
	<div class="mbs-stats-grid">
		<div class="mbs-stat-card">
			<span class="mbs-stat-icon" style="color: #10b981;"><i class="fa-solid fa-sack-dollar"></i></span>
			<div class="mbs-stat-data">
				<span class="mbs-stat-label">Tổng Doanh Thu</span>
				<h2 id="mbs-stat-revenue">Đang tải...</h2>
			</div>
		</div>
		<div class="mbs-stat-card">
			<span class="mbs-stat-icon" style="color: #6366f1;"><i class="fa-solid fa-ticket"></i></span>
			<div class="mbs-stat-data">
				<span class="mbs-stat-label">Tổng Vé Đã Bán</span>
				<h2 id="mbs-stat-tickets">Đang tải...</h2>
			</div>
		</div>
		<div class="mbs-stat-card">
			<span class="mbs-stat-icon" style="color: #0ea5e9;"><i class="fa-solid fa-box-archive"></i></span>
			<div class="mbs-stat-data">
				<span class="mbs-stat-label">Tổng Đơn Hàng</span>
				<h2 id="mbs-stat-orders">Đang tải...</h2>
			</div>
		</div>
		<div class="mbs-stat-card">
			<span class="mbs-stat-icon" style="color: #f59e0b;"><i class="fa-solid fa-chart-column"></i></span>
			<div class="mbs-stat-data">
				<span class="mbs-stat-label">Giá Trị Đơn Trung Bình</span>
				<h2 id="mbs-stat-avg">Đang tải...</h2>
			</div>
		</div>
	</div>

	<!-- Charts Grid -->
	<div class="mbs-charts-grid">
		<!-- Line Chart: 7 Days Revenue -->
		<div class="mbs-chart-box">
			<h3>Doanh Thu 7 Ngày Gần Nhất (VNĐ)</h3>
			<canvas id="mbsRevenueChart" height="110"></canvas>
		</div>

		<!-- Bar/Pie Chart: Top Movies by Revenue -->
		<div class="mbs-chart-box">
			<h3>Top Phim Có Doanh Thu Cao Nhất</h3>
			<canvas id="mbsMovieChart" height="110"></canvas>
		</div>
	</div>
</div>
