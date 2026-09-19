<?php
/**
 * Demo Data Seeder for Movie Booking System
 * Populates realistic movies, cinemas, screening rooms, seats, showtimes, coupons, and orders
 *
 * @package MovieBookingSystem
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MBS_Seeder {

	/**
	 * Run all seeders
	 */
	public static function run() {
		$cinemas = self::seed_cinemas();
		$rooms   = self::seed_rooms( $cinemas );
		$movies  = self::seed_movies();
		$showtimes = self::seed_showtimes( $movies, $cinemas, $rooms );
		self::seed_coupons();
		self::seed_sample_orders( $showtimes );

		update_option( 'mbs_demo_data_seeded', 1 );
	}

	/**
	 * Seed Cinemas
	 */
	public static function seed_cinemas() {
		$cinemas_data = array(
			array(
				'title'   => 'Cinemagic Vincom Bà Triệu (Hà Nội)',
				'address' => 'Tầng 6, Vincom Center, 191 Bà Triệu, Phường Lê Đại Hành, Quận Hai Bà Trưng, Hà Nội',
				'city'    => 'Hà Nội',
				'hotline' => '1900 6868 (Nhánh 1)',
				'content' => 'Tọa lạc tại trung tâm thủ đô Hà Nội, sở hữu cụm rạp tiêu chuẩn thế giới với phòng chiếu IMAX Laser màn hình siêu đại và hệ thống âm thanh vòm Dolby Atmos 64 kênh đa chiều.',
			),
			array(
				'title'   => 'Cinemagic Landmark 81 (TP.HCM)',
				'address' => 'Tầng B1, TTTM Vincom Center Landmark 81, 720A Điện Biên Phủ, Phường 22, Quận Bình Thạnh, TP. Hồ Chí Minh',
				'city'    => 'Hồ Chí Minh',
				'hotline' => '1900 6868 (Nhánh 2)',
				'content' => 'Nằm trong tòa tháp cao nhất Việt Nam, Cinemagic Landmark 81 mang đến trải nghiệm điện ảnh thượng lưu với phòng chiếu Gold Class Suite phục vụ đồ uống tại chỗ và công nghệ màn chiếu cong IMAX Laser.',
			),
			array(
				'title'   => 'Cinemagic Royal City (Hà Nội)',
				'address' => 'Tầng B2, TTTM Vincom Mega Mall Royal City, 72A Nguyễn Trãi, Thượng Đình, Quận Thanh Xuân, Hà Nội',
				'city'    => 'Hà Nội',
				'hotline' => '1900 6868 (Nhánh 3)',
				'content' => 'Cụm rạp giải trí quy mô lớn dành cho giới trẻ và gia đình, tích hợp công nghệ rung lắc hiệu ứng sống động 4DX Motion cùng phòng chiếu 2D/3D độ phân giải 4K sắc nét.',
			),
			array(
				'title'   => 'Cinemagic Dragon City (Đà Nẵng)',
				'address' => '155 Nguyễn Văn Linh, Phường Nam Dương, Quận Hải Châu, TP. Đà Nẵng',
				'city'    => 'Đà Nẵng',
				'hotline' => '1900 6868 (Nhánh 4)',
				'content' => 'Điểm đến điện ảnh số 1 miền Trung bên bờ sông Hàn thơ mộng, quy tụ hệ thống máy chiếu Laser thế hệ mới, sảnh chờ VIP sang trọng và bắp rang bơ đa vị tươi mới mỗi ngày.',
			),
			array(
				'title'   => 'Cinemagic Thảo Điền (TP.HCM)',
				'address' => 'Tầng 5, Vincom Mega Mall Thảo Điền, 161 Xa Lộ Hà Nội, Phường Thảo Điền, TP. Thủ Đức, TP. Hồ Chí Minh',
				'city'    => 'Hồ Chí Minh',
				'hotline' => '1900 6868 (Nhánh 5)',
				'content' => 'Thiết kế theo phong cách Indochine hiện đại, trang bị ghế da cao cấp công thái học, hệ thống điều hòa kháng khuẩn và không gian check-in cực chất cho tín đồ yêu phim.',
			),
			array(
				'title'   => 'CGV Vincom Center Đồng Khởi',
				'address' => 'Tầng 3, TTTM Vincom Center, 72 Lê Thánh Tôn, Bến Nghé, Quận 1, TP. Hồ Chí Minh',
				'city'    => 'Hồ Chí Minh',
				'hotline' => '1900 6017',
				'content' => 'Cụm rạp chuẩn quốc tế hiện đại bậc nhất tại trung tâm Sài Gòn với công nghệ chiếu phim IMAX Laser và dàn âm thanh vòm Dolby Atmos đỉnh cao.',
			),
			array(
				'title'   => 'Galaxy Cinema Nguyễn Du',
				'address' => '116 Nguyễn Du, Phường Bến Thành, Quận 1, TP. Hồ Chí Minh',
				'city'    => 'Hồ Chí Minh',
				'hotline' => '1900 2224',
				'content' => 'Không gian điện ảnh trẻ trung, phòng chiếu Gold Class sang trọng, bắp nước hảo hạng cùng phong cách phục vụ tận tâm.',
			),
		);

		$cinema_ids = array();
		foreach ( $cinemas_data as $data ) {
			$existing = get_page_by_title( $data['title'], OBJECT, 'cinema' );
			if ( ! $existing ) {
				$post_id = wp_insert_post( array(
					'post_title'   => $data['title'],
					'post_content' => $data['content'],
					'post_status'  => 'publish',
					'post_type'    => 'cinema',
				) );
				update_post_meta( $post_id, '_mbs_address', $data['address'] );
				update_post_meta( $post_id, '_mbs_city', $data['city'] );
				update_post_meta( $post_id, '_mbs_hotline', $data['hotline'] );
				$cinema_ids[] = $post_id;
			} else {
				update_post_meta( $existing->ID, '_mbs_address', $data['address'] );
				update_post_meta( $existing->ID, '_mbs_city', $data['city'] );
				update_post_meta( $existing->ID, '_mbs_hotline', $data['hotline'] );
				$cinema_ids[] = $existing->ID;
			}
		}

		return $cinema_ids;
	}

	/**
	 * Seed Rooms & Seats
	 */
	public static function seed_rooms( $cinema_ids ) {
		if ( empty( $cinema_ids ) ) {
			return array();
		}

		$room_ids = array();

		foreach ( $cinema_ids as $idx => $cid ) {
			$existing_rooms = MBS_DB::get_rooms_by_cinema( $cid );
			if ( ! empty( $existing_rooms ) ) {
				foreach ( $existing_rooms as $er ) {
					$room_ids[ $cid ][] = $er->id;
				}
				continue;
			}

			// Screen 1: IMAX Laser or Gold Class
			$type1 = ( $idx % 2 === 0 ) ? 'IMAX Laser 3D' : 'Gold Class VIP';
			$name1 = ( $idx % 2 === 0 ) ? 'Phòng Chiếu IMAX Laser 01' : 'Phòng Chiếu Gold Class VIP 01';
			$r1_id = MBS_DB::save_room( array(
				'cinema_id'   => $cid,
				'room_name'   => $name1,
				'room_type'   => $type1,
				'total_rows'  => 8,
				'total_cols'  => 10,
			) );
			$room_ids[ $cid ][] = $r1_id;

			// Screen 2: Standard 2D Digital
			$r2_id = MBS_DB::save_room( array(
				'cinema_id'   => $cid,
				'room_name'   => 'Phòng Chiếu Cinema 02',
				'room_type'   => 'Standard 2D',
				'total_rows'  => 8,
				'total_cols'  => 10,
			) );
			$room_ids[ $cid ][] = $r2_id;
		}

		return $room_ids;
	}

	/**
	 * Seed Movies
	 */
	public static function seed_movies() {
		$movies_data = array(
			array(
				'title'         => 'Dune: Hành Tinh Cát - Phần 2',
				'excerpt'       => 'Paul Atreides hợp lực cùng Chani và tộc người Fremen để trả thù những kẻ đã hủy diệt gia tộc mình, đối mặt với vận mệnh vũ trụ.',
				'content'       => 'Dune: Part Two tiếp nối cuộc hành trình huyền thoại của Paul Atreides khi anh cùng Chani và tộc người Fremen chuẩn bị cho một cuộc chiến không khoan nhượng chống lại những kẻ đã hủy hoại gia đình anh. Đứng trước những lựa chọn giữa tình yêu đích thực của cuộc đời mình và số phận của toàn thể vũ trụ, anh phải cố gắng ngăn chặn một tương lai khủng khiếp mà chỉ anh mới có thể thấy trước.',
				'genres'        => array( 'Hành Động', 'Khoa Học Viễn Tưởng', 'Phiêu Lưu' ),
				'formats'       => array( 'IMAX 2D', '2D Phụ Đề' ),
				'trailer_url'   => 'https://www.youtube.com/watch?v=Way9Dexny3w',
				'duration'      => 166,
				'director'      => 'Denis Villeneuve',
				'cast'          => 'Timothée Chalamet, Zendaya, Rebecca Ferguson, Austin Butler, Florence Pugh',
				'release_date'  => '2024-03-01',
				'rating_score'  => '8.9',
				'age_rating'    => 'T16',
				'is_hot'        => 1,
				'movie_status'  => 'now_showing',
				'poster_url'    => 'https://image.tmdb.org/t/p/w500/1pdfLvkbY9ohJlCjQH2CZjjYVvJ.jpg',
			),
			array(
				'title'         => 'Avatar: Dòng Chảy Của Nước',
				'excerpt'       => 'Hơn một thập kỷ sau sự kiện phần một, Jake Sully và Neytiri xây dựng tổ ấm và phải chiến đấu bảo vệ đại dương Pandora khỏi hiểm họa diệt vong.',
				'content'       => 'Lấy bối cảnh hơn một thập kỷ sau các sự kiện của phần phim đầu tiên, Avatar: The Way of Water bắt đầu kể câu chuyện về gia đình Sully (Jake, Neytiri và các con của họ), những rắc rối theo sau họ, những nỗ lực mà họ phải trải qua để giữ an toàn cho nhau, những trận chiến mà họ phải chiến đấu để sinh tồn và những bi kịch mà họ phải chịu đựng giữa thế giới đại dương bao la của Pandora.',
				'genres'        => array( 'Khoa Học Viễn Tưởng', 'Phiêu Lưu', 'Hành Động' ),
				'formats'       => array( '3D Phụ Đề', 'IMAX 3D' ),
				'trailer_url'   => 'https://www.youtube.com/watch?v=d9MyW72ELq0',
				'duration'      => 192,
				'director'      => 'James Cameron',
				'cast'          => 'Sam Worthington, Zoe Saldana, Sigourney Weaver, Stephen Lang, Kate Winslet',
				'release_date'  => '2024-01-15',
				'rating_score'  => '8.2',
				'age_rating'    => 'P',
				'is_hot'        => 1,
				'movie_status'  => 'now_showing',
				'poster_url'    => 'https://image.tmdb.org/t/p/w500/t6HIqrRAclMCA60NsSmeqe9RmNV.jpg',
			),
			array(
				'title'         => 'Lật Mặt 7: Một Điều Ước',
				'excerpt'       => 'Câu chuyện cảm động thắt lòng về tình mẫu tử của bà Hai 73 tuổi cùng 5 người con trưởng thành đang sinh sống ở khắp mọi miền đất nước.',
				'content'       => 'Lật Mặt 7 xoay quanh câu chuyện gia đình bà Hai (73 tuổi) sống một mình ở làng K’Long K’Lanh (Lạc Dương, Lâm Đồng). Bà có 5 người con nhưng đều đã rời xa quê hương lập nghiệp. Sau một tai nạn bất ngờ, câu chuyện đặt ra bài toán ai sẽ là người chăm sóc mẹ già khi mỗi người con đều có những khúc mắc và gánh nặng riêng trong cuộc sống hiện đại.',
				'genres'        => array( 'Tâm Lý', 'Gia Đình', 'Chính Kịch' ),
				'formats'       => array( '2D' ),
				'trailer_url'   => 'https://www.youtube.com/watch?v=zJae8bKqO7w',
				'duration'      => 138,
				'director'      => 'Lý Hải',
				'cast'          => 'Thanh Hiền, Trương Minh Cường, Đinh Y Nhung, Quách Ngọc Tuyên, Trâm Anh',
				'release_date'  => '2024-04-26',
				'rating_score'  => '8.7',
				'age_rating'    => 'P',
				'is_hot'        => 1,
				'movie_status'  => 'now_showing',
				'poster_url'    => 'https://image.tmdb.org/t/p/w500/wTnV3PCVW5O92JMrWh2vrFicst9.jpg',
			),
			array(
				'title'         => 'Mai',
				'excerpt'       => 'Cuộc gặp gỡ định mệnh giữa Mai - người phụ nữ massage trị liệu chịu nhiều định kiến, và Dương - chàng nhạc công đào hoa phong nhã.',
				'content'       => 'Bộ phim kể về cuộc đời đầy trắc trở của Mai, một người phụ nữ gần 40 tuổi làm nghề massage trị liệu tại một khu chung cư cũ. Cô luôn mang trong mình mặc cảm quá khứ và sự dè dặt trước tình yêu. Mọi chuyện thay đổi khi cô tình cờ gặp gỡ Trùng Dương, một chàng trai trẻ tuổi, phóng khoáng, khát khao được tự do theo đuổi âm nhạc.',
				'genres'        => array( 'Tình Cảm', 'Tâm Lý', 'Chính Kịch' ),
				'formats'       => array( '2D' ),
				'trailer_url'   => 'https://www.youtube.com/watch?v=vVj_82_x0fU',
				'duration'      => 131,
				'director'      => 'Trấn Thành',
				'cast'          => 'Phương Anh Đào, Tuấn Trần, Trấn Thành, Hồng Đào, Uyển Ân',
				'release_date'  => '2024-02-10',
				'rating_score'  => '8.4',
				'age_rating'    => 'T18',
				'is_hot'        => 0,
				'movie_status'  => 'now_showing',
				'poster_url'    => 'https://image.tmdb.org/t/p/w500/9gk7adHYeDvHkCSEqAvQNLV5Uge.jpg',
			),
			array(
				'title'         => 'Kung Fu Panda 4',
				'excerpt'       => 'Chú gấu trúc Po được giao trọng trách trở thành Thủ lĩnh Tinh thần của Thung lũng Bình Yên và phải huấn luyện một Chiến Binh Rồng mới.',
				'content'       => 'Sau ba cuộc phiêu lưu nguy hiểm sinh tử đánh bại những kẻ phản diện hàng đầu thế giới bằng lòng dũng cảm vô song và kỹ năng võ thuật điêu luyện, Po - Thần Long Đại Hiệp - được định mệnh kêu gọi... hãy nghỉ ngơi. Cụ thể hơn, anh được chọn trở thành Thủ lĩnh Tinh thần của Thung lũng Bình Yên và cần tìm kiếm người kế thừa danh hiệu Chiến Binh Rồng.',
				'genres'        => array( 'Hoạt Hình', 'Hài Hước', 'Hành Động' ),
				'formats'       => array( '2D Lồng Tiếng', '3D Lồng Tiếng' ),
				'trailer_url'   => 'https://www.youtube.com/watch?v=_inKs4eeHiI',
				'duration'      => 94,
				'director'      => 'Mike Mitchell',
				'cast'          => 'Jack Black, Awkwafina, Viola Davis, Dustin Hoffman, James Hong',
				'release_date'  => '2024-03-08',
				'rating_score'  => '8.0',
				'age_rating'    => 'P',
				'is_hot'        => 0,
				'movie_status'  => 'now_showing',
				'poster_url'    => 'https://image.tmdb.org/t/p/w500/kDp1vUBnMpe8ak4rjgl3cLELqjU.jpg',
			),
			array(
				'title'         => 'Deadpool & Wolverine',
				'excerpt'       => 'Cặp đôi siêu bựa tái xuất vũ trụ điện ảnh Marvel trong chuyến phiêu lưu đa vũ trụ giải cứu thế giới đầy máu lửa và tiếng cười.',
				'content'       => 'Wade Wilson (Deadpool) đang sống một cuộc đời bình lặng sau khi từ giã sự nghiệp lính đánh thuê. Tuy nhiên, Cơ quan Phương sai Thời gian (TVA) bất ngờ kéo anh vào một nhiệm vụ khẩn cấp có thể đe dọa sự tồn vong của toàn bộ dòng thời gian. Để hoàn thành nhiệm vụ, Deadpool buộc phải tìm kiếm sự trợ giúp từ một biến thể Wolverine đang chìm đắm trong đau khổ và thất bại.',
				'genres'        => array( 'Hành Động', 'Hài Hước', 'Khoa Học Viễn Tưởng' ),
				'formats'       => array( 'IMAX 3D', '4DX', '2D Phụ Đề' ),
				'trailer_url'   => 'https://www.youtube.com/watch?v=73_1biulkYk',
				'duration'      => 127,
				'director'      => 'Shawn Levy',
				'cast'          => 'Ryan Reynolds, Hugh Jackman, Emma Corrin, Matthew Macfadyen, Morena Baccarin',
				'release_date'  => '2024-07-26',
				'rating_score'  => '8.9',
				'age_rating'    => 'T18',
				'is_hot'        => 1,
				'movie_status'  => 'now_showing',
				'poster_url'    => 'https://image.tmdb.org/t/p/w500/8cdWjvZQUExUUTzyp4t6EDMubfO.jpg',
			),
			array(
				'title'         => 'Inside Out 2 (Những Mảnh Ghép Cảm Xúc 2)',
				'excerpt'       => 'Riley bước vào tuổi dậy thì với sự xuất hiện của những cảm xúc hoàn toàn mới: Lo Âu, Ganh Tị, Xấu Hổ và Chán Nản.',
				'content'       => 'Bộ phim hoạt hình xuất sắc nhất mùa hè đưa khán giả trở lại với tâm trí của cô bé Riley lúc này đã bước sang tuổi 13. Tổng hành dinh cảm xúc bất ngờ bị phá hủy để nhường chỗ cho những vị khách không mời: Lo Âu (Anxiety), Ghen Tị (Envy), Xấu Hổ (Embarrassment) và Chán Nản (Ennui). Nhóm cảm xúc cũ do Vui Vẻ dẫn đầu phải tìm cách lấy lại quyền điều khiển để bảo vệ bản sắc của Riley.',
				'genres'        => array( 'Hoạt Hình', 'Gia Đình', 'Hài Hước' ),
				'formats'       => array( '2D Lồng Tiếng', '2D Phụ Đề', '3D' ),
				'trailer_url'   => 'https://www.youtube.com/watch?v=LEjhY15eCx0',
				'duration'      => 96,
				'director'      => 'Kelsey Mann',
				'cast'          => 'Amy Poehler, Maya Hawke, Kensington Tallman, Liza Lapira, Tony Hale',
				'release_date'  => '2024-06-14',
				'rating_score'  => '8.8',
				'age_rating'    => 'P',
				'is_hot'        => 1,
				'movie_status'  => 'now_showing',
				'poster_url'    => 'https://image.tmdb.org/t/p/w500/vpnVM9B6NMmQpWeZvzLvDESb2QY.jpg',
			),
			array(
				'title'         => 'Godzilla x Kong: Đế Chế Mới',
				'excerpt'       => 'Hai đại quái thú huyền thoại hợp lực chống lại một mối đe dọa khổng lồ chưa từng được khám phá ẩn sâu trong lòng Trái Đất.',
				'content'       => 'Tiếp nối chiến thắng vang dội trong trận đối đầu trước, Godzilla và Kong buộc phải gác lại lãnh địa riêng để cùng nhau đối mặt với một hiểm họa diệt vong tiềm ẩn trong Trái Đất Rỗng: Vua Skar - thủ lĩnh tàn bạo của loài linh trưởng cổ đại và quái thú băng giá Shimo.',
				'genres'        => array( 'Hành Động', 'Khoa Học Viễn Tưởng', 'Phiêu Lưu' ),
				'formats'       => array( 'IMAX 3D', '4DX', '2D' ),
				'trailer_url'   => 'https://www.youtube.com/watch?v=lV1OOlGwExg',
				'duration'      => 115,
				'director'      => 'Adam Wingard',
				'cast'          => 'Rebecca Hall, Brian Tyree Henry, Dan Stevens, Kaylee Hottle, Alex Ferns',
				'release_date'  => '2024-03-29',
				'rating_score'  => '8.3',
				'age_rating'    => 'K',
				'is_hot'        => 1,
				'movie_status'  => 'now_showing',
				'poster_url'    => 'https://image.tmdb.org/t/p/w500/tMefBSflR6PGQLv7WvFPpKLZkyk.jpg',
			),
			array(
				'title'         => 'Oppenheimer',
				'excerpt'       => 'Kiệt tác tiểu sử kịch tính đoạt 7 giải Oscar của đạo diễn Christopher Nolan về người cha đẻ của bom nguyên tử J. Robert Oppenheimer.',
				'content'       => 'Câu chuyện lịch sử đầy kịch tính về cuộc đời của nhà vật lý lý thuyết lỗi lạc J. Robert Oppenheimer, người đứng đầu Dự án Manhattan tại Phòng thí nghiệm Los Alamos trong Thế chiến thứ hai, dẫn đến việc phát minh ra vũ khí hạt nhân đầu tiên của nhân loại và những dằn vặt lương tâm khôn nguôi.',
				'genres'        => array( 'Tâm Lý', 'Lịch Sử', 'Chính Kịch' ),
				'formats'       => array( 'IMAX Laser 2D', '2D Phụ Đề' ),
				'trailer_url'   => 'https://www.youtube.com/watch?v=uYPbbksJxIg',
				'duration'      => 180,
				'director'      => 'Christopher Nolan',
				'cast'          => 'Cillian Murphy, Emily Blunt, Matt Damon, Robert Downey Jr., Florence Pugh',
				'release_date'  => '2023-07-21',
				'rating_score'  => '9.2',
				'age_rating'    => 'T16',
				'is_hot'        => 1,
				'movie_status'  => 'now_showing',
				'poster_url'    => 'https://image.tmdb.org/t/p/w500/8Gxv8gSFCU0XGDykEGv7zR1n2ua.jpg',
			),
			array(
				'title'         => 'Thám Tử Lừng Danh Conan: Ngôi Sao 5 Cánh 1 Triệu Đô',
				'excerpt'       => 'Conan, Heiji Hattori và Siêu trộm Kaito Kid bước vào cuộc đấu trí tại Hakodate xoay quanh thanh kiếm báu thời Mạc Phủ.',
				'content'       => 'Một bức thư thông báo trộm kiếm của Kaito Kid được gửi đến gia đình tài phiệt Onoe ở Hakodate, Hokkaido. Lần này, mục tiêu của Kid không phải là đá quý mà là hai thanh kiếm Nhật cổ có liên quan đến Toshizo Hijikata - phó cục trưởng của Shinsengumi thời Mạc Phủ.',
				'genres'        => array( 'Hoạt Hình', 'Anime', 'Trinh Thám', 'Hành Động' ),
				'formats'       => array( '2D Lồng Tiếng', '2D Phụ Đề' ),
				'trailer_url'   => 'https://www.youtube.com/watch?v=s0f_V_5K3dM',
				'duration'      => 110,
				'director'      => 'Chika Nagaoka',
				'cast'          => 'Minami Takayama, Wakana Yamazaki, Rikiya Koyama, Kappei Yamaguchi',
				'release_date'  => '2024-08-02',
				'rating_score'  => '8.6',
				'age_rating'    => 'P',
				'is_hot'        => 0,
				'movie_status'  => 'now_showing',
				'poster_url'    => 'https://image.tmdb.org/t/p/w500/pmsaD0jFqVvIqQoP91K4aF3zUuX.jpg',
			),
			array(
				'title'         => 'A Quiet Place: Ngày Một (Vùng Đất Câm Lặng)',
				'excerpt'       => 'Trải nghiệm khoảnh khắc thế giới rơi vào câm lặng khi New York bị tấn công bởi bầy sinh vật săn mồi bằng âm thanh cực nhạy.',
				'content'       => 'Phần tiền truyện gay cấn nghẹt thở đưa khán giả về ngày đầu tiên khi các quái vật ngoài hành tinh rơi xuống Trái Đất và biến thành phố New York nhộn nhịp bậc nhất thế giới thành một biển chết trong sự im lặng tuyệt đối.',
				'genres'        => array( 'Kinh Dị', 'Khoa Học Viễn Tưởng', 'Giật Gân' ),
				'formats'       => array( '2D Phụ Đề', 'Dolby Atmos 2D' ),
				'trailer_url'   => 'https://www.youtube.com/watch?v=YPY7J-flzE8',
				'duration'      => 100,
				'director'      => 'Michael Sarnoski',
				'cast'          => 'Lupita Nyong\'o, Joseph Quinn, Alex Wolff, Djimon Hounsou',
				'release_date'  => '2024-06-28',
				'rating_score'  => '8.1',
				'age_rating'    => 'T18',
				'is_hot'        => 0,
				'movie_status'  => 'now_showing',
				'poster_url'    => 'https://image.tmdb.org/t/p/w500/hU1Q9YVzdYwh89696o977V3WzS5.jpg',
			),
			array(
				'title'         => 'Ma Da',
				'excerpt'       => 'Huyền tích dân gian vùng sông nước miền Tây về linh hồn kéo người chết đuối dưới đáy nước tăm tối lạnh lẽo.',
				'content'       => 'Phim kinh dị dân gian Việt Nam kể về bà Lệ - một người phụ nữ làm nghề vớt xác trên sông nhiều năm. Bỗng một ngày, con gái bà bị mất tích một cách bí ẩn sau khi nhặt được một con búp bê kỳ lạ dưới nước, mở ra hàng loạt hiện tượng tâm linh kinh hoàng.',
				'genres'        => array( 'Kinh Dị', 'Tâm Linh', 'Bí Ẩn' ),
				'formats'       => array( '2D' ),
				'trailer_url'   => 'https://www.youtube.com/watch?v=680g9n0s2rM',
				'duration'      => 102,
				'director'      => 'Nguyễn Hữu Hoàng',
				'cast'          => 'Việt Hương, NSƯT Thành Lộc, Cẩm Ly, Trung Dân',
				'release_date'  => '2024-08-16',
				'rating_score'  => '8.0',
				'age_rating'    => 'T16',
				'is_hot'        => 0,
				'movie_status'  => 'now_showing',
				'poster_url'    => 'https://image.tmdb.org/t/p/w500/7Z7Rz8Yj9MvYnB9Q3L8R8L3Q8Y.jpg',
			),
			array(
				'title'         => 'Alien: Romulus (Quái Vật Không Gian)',
				'excerpt'       => 'Nhóm thám hiểm trẻ tuổi đối đầu với dạng sống chết chóc nhất vũ trụ trong một trạm vũ trụ bị bỏ hoang.',
				'content'       => 'Lấy bối cảnh giữa các sự kiện của Alien (1979) và Aliens (1986), một nhóm thực dân không gian trẻ tuổi dọn rác tại một trạm vũ trụ bị bỏ hoang thì vô tình đánh thức loài quái vật nguy hiểm bậc nhất dải ngân hà Xenomorph.',
				'genres'        => array( 'Khoa Học Viễn Tưởng', 'Kinh Dị', 'Giật Gân' ),
				'formats'       => array( 'IMAX Laser 2D', '4DX', '2D Phụ Đề' ),
				'trailer_url'   => 'https://www.youtube.com/watch?v=x0XDEhP4-Gg',
				'duration'      => 119,
				'director'      => 'Fede Álvarez',
				'cast'          => 'Cailee Spaeny, David Jonsson, Archie Renaux, Isabela Merced, Spike Fearn',
				'release_date'  => '2024-08-16',
				'rating_score'  => '8.5',
				'age_rating'    => 'T18',
				'is_hot'        => 1,
				'movie_status'  => 'coming_soon',
				'poster_url'    => 'https://image.tmdb.org/t/p/w500/b33nnKl1GSFbao4l3fZDDqsMx0F.jpg',
			),
			array(
				'title'         => 'Kẻ Trộm Mặt Trăng 4 (Despicable Me 4)',
				'excerpt'       => 'Gru, Lucy cùng bầy Minions siêu quậy đối mặt với kẻ thù không đội trời chung Maxime Le Mal và bạn gái Valentina.',
				'content'       => 'Gia đình Gru chào đón thành viên mới Gru Jr., cậu bé luôn tìm cách quấy rối bố. Nhưng cuộc sống yên bình của họ nhanh chóng bị đảo lộn khi Maxime Le Mal trốn thoát khỏi tù và thề sẽ trả thù Gru, buộc cả gia đình phải tham gia chương trình bảo vệ nhân chứng.',
				'genres'        => array( 'Hoạt Hình', 'Hài Hước', 'Gia Đình' ),
				'formats'       => array( '2D Lồng Tiếng', '2D Phụ Đề', '3D' ),
				'trailer_url'   => 'https://www.youtube.com/watch?v=qQlr9-rF32A',
				'duration'      => 95,
				'director'      => 'Chris Renaud',
				'cast'          => 'Steve Carell, Kristen Wiig, Will Ferrell, Sofia Vergara, Joey King',
				'release_date'  => '2024-07-05',
				'rating_score'  => '8.2',
				'age_rating'    => 'P',
				'is_hot'        => 0,
				'movie_status'  => 'coming_soon',
				'poster_url'    => 'https://image.tmdb.org/t/p/w500/wWba3TaojhK7N6cMgrRPMPUgqXi.jpg',
			),
			array(
				'title'         => 'Furiosa: Câu Chuyện Từ Max Điên',
				'excerpt'       => 'Hành trình sinh tồn khốc liệt và báo thù tàn nhẫn của nữ chiến binh Furiosa giữa chốn sa mạc hoang tàn hậu tận thế.',
				'content'       => 'Khi thế giới sụp đổ, cô bé Furiosa bị bắt cóc khỏi Vùng Xanh của Nhiều Người Mẹ và rơi vào tay Băng đảng Xe máy Vĩ đại do Warlord Dementus chỉ huy. Khi hai Bạo chúa tranh giành quyền thống trị Thành Trì, Furiosa phải sống sót qua nhiều thử thách để tìm đường trở về nhà.',
				'genres'        => array( 'Hành Động', 'Phiêu Lưu', 'Khoa Học Viễn Tưởng' ),
				'formats'       => array( 'IMAX Laser', '4DX', '2D Phụ Đề' ),
				'trailer_url'   => 'https://www.youtube.com/watch?v=XJMuhwVlca4',
				'duration'      => 148,
				'director'      => 'George Miller',
				'cast'          => 'Anya Taylor-Joy, Chris Hemsworth, Tom Burke, Alyla Browne, Lachy Hulme',
				'release_date'  => '2024-05-24',
				'rating_score'  => '8.6',
				'age_rating'    => 'T18',
				'is_hot'        => 1,
				'movie_status'  => 'coming_soon',
				'poster_url'    => 'https://image.tmdb.org/t/p/w500/iADOJ8Zymht2JPMoy3R7xUMZ51f.jpg',
			),
			array(
				'title'         => 'Nhà Bà Nữ',
				'excerpt'       => 'Câu chuyện đa góc nhìn về mâu thuẫn thế hệ, định kiến gia đình và khát vọng tự do của những người trẻ tại một quán bánh canh gia truyền.',
				'content'       => 'Nhà Bà Nữ xoay quanh gia đình bà Nữ (Lê Giang thủ vai) - chủ một quán bánh canh cua nức tiếng Sài Gòn. Gia đình gồm ba thế hệ phụ nữ sống chung dưới một mái nhà với sự áp đặt hà khắc của người mẹ. Khi cô con gái út Nhi quyết định bứt phá khỏi sự bao bọc để đi theo tiếng gọi con tim, những rạn nứt và bi kịch gia đình bắt đầu bùng nổ.',
				'genres'        => array( 'Tâm Lý', 'Gia Đình', 'Hài Hước' ),
				'formats'       => array( '2D Digital' ),
				'trailer_url'   => 'https://www.youtube.com/watch?v=FqS7MhBfvOQ',
				'duration'      => 102,
				'director'      => 'Trấn Thành',
				'cast'          => 'Trấn Thành, Lê Giang, Uyển Ân, Song Luân, Khả Như, NSND Ngọc Giàu',
				'release_date'  => '2023-01-22',
				'rating_score'  => '8.5',
				'age_rating'    => 'T16',
				'is_hot'        => 0,
				'movie_status'  => 'now_showing',
				'poster_url'    => 'https://image.tmdb.org/t/p/w500/3bhkrj58Vtu7enYsRolD1fZdja1.jpg',
			),
			array(
				'title'         => 'Người Nhện: Du Hành Vũ Trụ Nhện (Spider-Man: Across the Spider-Verse)',
				'excerpt'       => 'Miles Morales cùng Gwen Stacy du hành xuyên qua đa vũ trụ, đối mặt với Quân đoàn Người Nhện bảo vệ cấu trúc thực tại.',
				'content'       => 'Sau khi tái hợp với Gwen Stacy, Người Nhện thân thiện của khu phố Brooklyn được đưa qua Đa vũ trụ, nơi anh chạm trán với một nhóm Người Nhện chịu trách nhiệm bảo vệ sự tồn tại của nó. Khi những người hùng xung đột về cách xử lý mối đe dọa mới, Miles nhận thấy mình phải chống lại những Người Nhện khác để cứu lấy người anh yêu thương nhất.',
				'genres'        => array( 'Hoạt Hình', 'Hành Động', 'Khoa Học Viễn Tưởng', 'Phiêu Lưu' ),
				'formats'       => array( 'IMAX 3D', '3D Lồng Tiếng', '2D Phụ Đề' ),
				'trailer_url'   => 'https://www.youtube.com/watch?v=cqGjhVJWtEg',
				'duration'      => 140,
				'director'      => 'Joaquim Dos Santos, Kemp Powers',
				'cast'          => 'Shameik Moore, Hailee Steinfeld, Oscar Isaac, Daniel Kaluuya',
				'release_date'  => '2023-06-02',
				'rating_score'  => '9.1',
				'age_rating'    => 'P',
				'is_hot'        => 1,
				'movie_status'  => 'now_showing',
				'poster_url'    => 'https://image.tmdb.org/t/p/w500/8Vt6mWEReuy4Of61Lnj5Xj704m8.jpg',
			),
			array(
				'title'         => 'The Batman (Vạch Trần Sự Thật)',
				'excerpt'       => 'Hiệp Sĩ Bóng Đêm truy lùng kẻ sát nhân hàng loạt Riddler gieo rắc kinh hoàng cho giới thượng lưu thành phố Gotham.',
				'content'       => 'Trong năm thứ hai làm thám tử chống tội phạm, Batman khám phá ra sự thối nát thâm căn cố đế ở Gotham có liên hệ mật thiết với chính gia đình anh trong lúc truy lùng The Riddler - một kẻ sát nhân hàng loạt tàn nhẫn nhắm vào các quan chức cấp cao của thành phố.',
				'genres'        => array( 'Hành Động', 'Tội Phạm', 'Trinh Thám', 'Bí Ẩn' ),
				'formats'       => array( 'IMAX Laser', '2D Phụ Đề' ),
				'trailer_url'   => 'https://www.youtube.com/watch?v=mqqft2x_Aa4',
				'duration'      => 176,
				'director'      => 'Matt Reeves',
				'cast'          => 'Robert Pattinson, Zoë Kravitz, Paul Dano, Jeffrey Wright, Colin Farrell',
				'release_date'  => '2022-03-04',
				'rating_score'  => '8.7',
				'age_rating'    => 'T16',
				'is_hot'        => 0,
				'movie_status'  => 'now_showing',
				'poster_url'    => 'https://image.tmdb.org/t/p/w500/74xTEgt7R36Fpooo50r9T25onhq.jpg',
			),
			array(
				'title'         => 'Exhuma: Quật Mộ Trùng Ma',
				'excerpt'       => 'Hai pháp sư, một thầy phong thủy và chuyên gia mai táng vướng vào chuỗi hiểm họa chết người khi di dời ngôi mộ tổ bí ẩn.',
				'content'       => 'Một gia đình giàu có gốc Hàn tại Los Angeles trải qua hàng loạt sự kiện siêu nhiên kỳ dị và cầu cứu hai pháp sư trẻ Hwarim và Bong-gil. Họ cùng thầy phong thủy Sang-deok và người làm nghề mai táng Yeong-geun tiến hành quật một ngôi mộ cổ ở vùng núi hẻo lánh mà không ngờ rằng mình vừa giải thoát một tà ác cổ xưa khát máu.',
				'genres'        => array( 'Kinh Dị', 'Bí Ẩn', 'Giật Gân', 'Tâm Linh' ),
				'formats'       => array( '2D Dolby Atmos', '2D Phụ Đề' ),
				'trailer_url'   => 'https://www.youtube.com/watch?v=wXF_1A63V5g',
				'duration'      => 134,
				'director'      => 'Jang Jae-hyun',
				'cast'          => 'Choi Min-sik, Kim Go-eun, Yoo Hae-jin, Lee Do-hyun',
				'release_date'  => '2024-03-15',
				'rating_score'  => '8.8',
				'age_rating'    => 'T18',
				'is_hot'        => 1,
				'movie_status'  => 'now_showing',
				'poster_url'    => 'https://image.tmdb.org/t/p/w500/kCGlIMHnOm8JPXq3rXM6c5wMxcT.jpg',
			),
			array(
				'title'         => 'Interstellar (Hố Đen Tử Thần)',
				'excerpt'       => 'Hành trình vượt qua các chiều không gian và thời gian của nhóm phi hành gia nhằm tìm kiếm ngôi nhà thứ hai cho nhân loại.',
				'content'       => 'Khi Trái Đất dần trở nên không thể sinh sống được do biến đổi khí hậu và nạn đói, một nhóm nhà thám hiểm do cựu phi công NASA Cooper dẫn đầu thực hiện sứ mệnh vĩ đại nhất trong lịch sử nhân loại: du hành xuyên qua một lỗ sâu mới xuất hiện trong vũ trụ để tìm kiếm hành tinh có thể duy trì sự sống.',
				'genres'        => array( 'Khoa Học Viễn Tưởng', 'Phiêu Lưu', 'Tâm Lý', 'Chính Kịch' ),
				'formats'       => array( 'IMAX Laser 70mm', '2D Phụ Đề' ),
				'trailer_url'   => 'https://www.youtube.com/watch?v=zSWdZVtXT7E',
				'duration'      => 169,
				'director'      => 'Christopher Nolan',
				'cast'          => 'Matthew McConaughey, Anne Hathaway, Jessica Chastain, Michael Caine',
				'release_date'  => '2024-09-01',
				'rating_score'  => '9.2',
				'age_rating'    => 'P',
				'is_hot'        => 1,
				'movie_status'  => 'now_showing',
				'poster_url'    => 'https://image.tmdb.org/t/p/w500/gEU2QniE6E77NI6lCU6MxlNBvIx.jpg',
			),
			array(
				'title'         => 'Suzume: Khóa Chặt Cửa Nào',
				'excerpt'       => 'Cô nữ sinh Suzume cùng chàng trai bí ẩn Souta đóng những cánh cửa tai ương đang mở ra khắp đất nước Nhật Bản.',
				'content'       => 'Suzume, một nữ sinh 17 tuổi sống tại thị trấn Kyushu yên bình, tình cờ gặp Souta - một chàng trai trẻ đang tìm kiếm "những cánh cửa". Lần theo dấu vết của anh, cô phát hiện ra một cánh cửa cũ kỹ nằm giữa phế tích. Khi Suzume mở cánh cửa, hàng loạt thảm họa thiên tai chực chờ nuốt chửng đất nước, buộc cả hai phải lên đường đóng chặt mọi cánh cửa trên khắp nước Nhật.',
				'genres'        => array( 'Anime', 'Kỳ Ảo', 'Phiêu Lưu', 'Tình Cảm', 'Hoạt Hình' ),
				'formats'       => array( '2D Phụ Đề', '2D Lồng Tiếng' ),
				'trailer_url'   => 'https://www.youtube.com/watch?v=TGbPn_l_X2M',
				'duration'      => 122,
				'director'      => 'Makoto Shinkai',
				'cast'          => 'Nanoka Hara, Hokuto Matsumura, Eri Fukatsu, Shota Sometani',
				'release_date'  => '2023-03-10',
				'rating_score'  => '8.9',
				'age_rating'    => 'P',
				'is_hot'        => 0,
				'movie_status'  => 'now_showing',
				'poster_url'    => 'https://image.tmdb.org/t/p/w500/vIeu8WysZrTS7vxaxhu0jSTNjUM.jpg',
			),
			array(
				'title'         => 'Sát Thủ John Wick 4 (John Wick: Chapter 4)',
				'excerpt'       => 'John Wick khám phá ra con đường đánh bại Hội Đồng Tối Cao nhưng phải đối đầu với một kẻ thù mới có liên minh hùng mạnh.',
				'content'       => 'John Wick tìm ra cách để đánh bại Hội Đồng Tối Cao High Table. Nhưng trước khi có thể giành lấy sự tự do cho bản thân, Wick phải đối mặt với Hầu tước Marquis de Gramont đầy tham vọng tàn bạo, cùng những người bạn cũ nay bị biến thành kẻ thù tử vì danh dự.',
				'genres'        => array( 'Hành Động', 'Tội Phạm', 'Giật Gân' ),
				'formats'       => array( 'IMAX 2D', '4DX', '2D Phụ Đề' ),
				'trailer_url'   => 'https://www.youtube.com/watch?v=qEVUtrk8_B4',
				'duration'      => 169,
				'director'      => 'Chad Stahelski',
				'cast'          => 'Keanu Reeves, Donnie Yen, Bill Skarsgård, Laurence Fishburne, Hiroyuki Sanada',
				'release_date'  => '2023-03-24',
				'rating_score'  => '8.9',
				'age_rating'    => 'T18',
				'is_hot'        => 1,
				'movie_status'  => 'now_showing',
				'poster_url'    => 'https://image.tmdb.org/t/p/w500/vZloFAK7NKnMGKEslUsZ2VoXVjc.jpg',
			),
			array(
				'title'         => 'Hành Tinh Khỉ: Vương Quốc Mới (Kingdom of the Planet of the Apes)',
				'excerpt'       => 'Nhiều thế hệ sau triều đại của Caesar, một thủ lĩnh khỉ bạo chúa xây dựng đế chế trong khi chú khỉ trẻ Noa dấn thân vào hành trình định đoạt tương lai.',
				'content'       => 'Lấy bối cảnh nhiều thế hệ trong tương lai sau sự ra đi của Caesar, loài khỉ nay trở thành giống loài thống trị hành tinh sống hài hòa, còn con người bị thoái hóa thành loài hoang dã. Khi một thủ lĩnh khỉ độc tài mới xây dựng đế chế của mình, Noa - chú khỉ trẻ dũng cảm - bắt đầu một hành trình gian khổ khiến cậu đặt câu hỏi về mọi điều cậu từng biết về quá khứ.',
				'genres'        => array( 'Hành Động', 'Khoa Học Viễn Tưởng', 'Phiêu Lưu' ),
				'formats'       => array( 'IMAX 3D', '4DX', '2D Phụ Đề' ),
				'trailer_url'   => 'https://www.youtube.com/watch?v=Kdr5oedn7q8',
				'duration'      => 145,
				'director'      => 'Wes Ball',
				'cast'          => 'Owen Teague, Freya Allan, Kevin Durand, Peter Macon, William H. Macy',
				'release_date'  => '2024-05-10',
				'rating_score'  => '8.3',
				'age_rating'    => 'T13',
				'is_hot'        => 0,
				'movie_status'  => 'now_showing',
				'poster_url'    => 'https://image.tmdb.org/t/p/w500/gKkl37BQuKTanygYQG1pyYgLVgf.jpg',
			),
			array(
				'title'         => 'Gặp Lại Chị Bầu',
				'excerpt'       => 'Phúc - chàng thanh niên mồ côi bất ngờ quay ngược thời gian về năm 1997 và bước vào hành trình tìm lại ý nghĩa của tình thân và tình yêu.',
				'content'       => 'Phúc là một diễn viên nghiệp dư mồ côi từ nhỏ, luôn cảm thấy cuộc sống bế tắc. Sau một sự cố bất ngờ, anh rơi về quá khứ năm 1997 và được cưu mang tại xóm trọ bà Lê. Tại đây, anh gặp Huyền, Ngọc và Tuấn - những người bạn tràn đầy nhiệt huyết thanh xuân, và dần tìm ra bí mật về cội nguồn của chính mình.',
				'genres'        => array( 'Hài Hước', 'Tình Cảm', 'Gia Đình' ),
				'formats'       => array( '2D Digital' ),
				'trailer_url'   => 'https://www.youtube.com/watch?v=rX_GzVbJ6l0',
				'duration'      => 115,
				'director'      => 'Đoàn Nhất Trung',
				'cast'          => 'Anh Tú, Diệu Nhi, Ngọc Phước, Quốc Khánh, Lê Giang',
				'release_date'  => '2024-02-10',
				'rating_score'  => '8.1',
				'age_rating'    => 'T13',
				'is_hot'        => 0,
				'movie_status'  => 'now_showing',
				'poster_url'    => 'https://image.tmdb.org/t/p/w500/wSbs5e94vAonjV6Z2P4w68s18r.jpg',
			),
			array(
				'title'         => 'Mufasa: Vua Sư Tử (Mufasa: The Lion King)',
				'excerpt'       => 'Hành trình từ chú sư tử mồ côi cô độc đến vị vua vĩ đại của Vùng Đất Kiêu Hãnh, mở ra tình anh em định mệnh với Taka.',
				'content'       => 'Bộ phim kể lại câu chuyện về sự trỗi dậy của một trong những vị vua vĩ đại nhất của Vùng Đất Kiêu Hãnh. Rafiki truyền lại truyền thuyết về Mufasa cho cô sư tử con Kiara - con gái của Simba và Nala. Câu chuyện bắt đầu từ khi Mufasa là một chú sư tử con mồ côi lạc lõng cho đến khi gặp Taka - người thừa kế ngai vàng đầy hứa hẹn.',
				'genres'        => array( 'Hoạt Hình', 'Phiêu Lưu', 'Gia Đình', 'Chính Kịch' ),
				'formats'       => array( 'IMAX 3D', '3D Lồng Tiếng', '2D Phụ Đề' ),
				'trailer_url'   => 'https://www.youtube.com/watch?v=o17MF9vnabg',
				'duration'      => 125,
				'director'      => 'Barry Jenkins',
				'cast'          => 'Aaron Pierre, Kelvin Harrison Jr., Seth Rogen, Billy Eichner, Beyoncé',
				'release_date'  => '2024-12-20',
				'rating_score'  => '8.5',
				'age_rating'    => 'P',
				'is_hot'        => 1,
				'movie_status'  => 'coming_soon',
				'poster_url'    => 'https://image.tmdb.org/t/p/w500/lurEK87kukWNaHd0z3WsSJ2ohvs.jpg',
			),
		);

		$movie_ids = array();
		foreach ( $movies_data as $data ) {
			$existing = get_page_by_title( $data['title'], OBJECT, 'movie' );
			$post_id = 0;
			if ( ! $existing ) {
				$post_id = wp_insert_post( array(
					'post_title'   => $data['title'],
					'post_excerpt' => $data['excerpt'],
					'post_content' => $data['content'],
					'post_status'  => 'publish',
					'post_type'    => 'movie',
				) );
			} else {
				$post_id = $existing->ID;
				wp_update_post( array(
					'ID'           => $post_id,
					'post_excerpt' => $data['excerpt'],
					'post_content' => $data['content'],
				) );
			}

			if ( $post_id ) {
				update_post_meta( $post_id, '_mbs_trailer_url', $data['trailer_url'] );
				update_post_meta( $post_id, '_mbs_duration', $data['duration'] );
				update_post_meta( $post_id, '_mbs_director', $data['director'] );
				update_post_meta( $post_id, '_mbs_cast', $data['cast'] );
				update_post_meta( $post_id, '_mbs_release_date', $data['release_date'] );
				update_post_meta( $post_id, '_mbs_rating_score', $data['rating_score'] );
				update_post_meta( $post_id, '_mbs_age_rating', $data['age_rating'] );
				update_post_meta( $post_id, '_mbs_is_hot', $data['is_hot'] );
				update_post_meta( $post_id, '_mbs_movie_status', $data['movie_status'] );
				update_post_meta( $post_id, '_mbs_poster_custom', $data['poster_url'] );

				wp_set_object_terms( $post_id, $data['genres'], 'movie_genre' );
				wp_set_object_terms( $post_id, $data['formats'], 'movie_format' );

				$movie_ids[] = $post_id;
			}
		}

		return $movie_ids;
	}

	/**
	 * Seed Showtimes
	 */
	public static function seed_showtimes( $movie_ids, $cinema_ids, $room_ids ) {
		if ( empty( $movie_ids ) || empty( $cinema_ids ) ) {
			return array();
		}

		$times = array( '09:30', '13:15', '16:45', '19:30', '22:15' );
		$showtime_ids = array();

		// Schedule across next 7 days
		for ( $day = 0; $day < 7; $day++ ) {
			$date_str = date( 'Y-m-d', strtotime( "+{$day} days", current_time( 'timestamp' ) ) );

			foreach ( $movie_ids as $m_idx => $mid ) {
				foreach ( $cinema_ids as $c_idx => $cid ) {
					$rooms_for_cinema = $room_ids[ $cid ] ?? array();
					if ( empty( $rooms_for_cinema ) ) continue;

					$chosen_room = $rooms_for_cinema[ $m_idx % count( $rooms_for_cinema ) ];
					$chosen_time = $times[ ( $m_idx + $c_idx + $day ) % count( $times ) ];

					$existing = get_posts( array(
						'post_type'      => 'showtime',
						'posts_per_page' => 1,
						'meta_query'     => array(
							array( 'key' => '_mbs_movie_id', 'value' => $mid ),
							array( 'key' => '_mbs_cinema_id', 'value' => $cid ),
							array( 'key' => '_mbs_show_date', 'value' => $date_str ),
							array( 'key' => '_mbs_start_time', 'value' => $chosen_time ),
						),
					) );

					if ( empty( $existing ) ) {
						$movie = get_post( $mid );
						$title = sprintf( '%s - %s %s', $movie->post_title, $date_str, $chosen_time );
						$base_price = ( $m_idx === 0 ) ? 120000 : 95000;
						$format = ( $m_idx === 0 ) ? 'IMAX 2D Phụ Đề' : ( ( $m_idx === 1 ) ? '3D Phụ Đề' : '2D Tiếng Việt' );

						$st_id = wp_insert_post( array(
							'post_title'  => $title,
							'post_status' => 'publish',
							'post_type'   => 'showtime',
						) );

						update_post_meta( $st_id, '_mbs_movie_id', $mid );
						update_post_meta( $st_id, '_mbs_cinema_id', $cid );
						update_post_meta( $st_id, '_mbs_room_id', $chosen_room );
						update_post_meta( $st_id, '_mbs_show_date', $date_str );
						update_post_meta( $st_id, '_mbs_start_time', $chosen_time );
						update_post_meta( $st_id, '_mbs_end_time', date( 'H:i', strtotime( $chosen_time ) + 7200 ) );
						update_post_meta( $st_id, '_mbs_base_price', $base_price );
						update_post_meta( $st_id, '_mbs_format', $format );

						$showtime_ids[] = $st_id;
					} else {
						$showtime_ids[] = $existing[0]->ID;
					}
				}
			}
		}

		return $showtime_ids;
	}

	/**
	 * Seed Coupons
	 */
	public static function seed_coupons() {
		global $wpdb;
		$table = $wpdb->prefix . 'booking_coupons';

		$coupons = array(
			array(
				'code'             => 'CINEMA50',
				'discount_type'    => 'percentage',
				'discount_value'   => 50.00,
				'min_order_amount' => 100000.00,
				'usage_limit'      => 500,
				'used_count'       => 18,
				'expiry_date'      => date( 'Y-m-d', strtotime( '+90 days' ) ),
			),
			array(
				'code'             => 'WELCOME10',
				'discount_type'    => 'fixed',
				'discount_value'   => 10000.00,
				'min_order_amount' => 50000.00,
				'usage_limit'      => 1000,
				'used_count'       => 35,
				'expiry_date'      => date( 'Y-m-d', strtotime( '+180 days' ) ),
			),
			array(
				'code'             => 'VIPPASS',
				'discount_type'    => 'percentage',
				'discount_value'   => 20.00,
				'min_order_amount' => 150000.00,
				'usage_limit'      => 200,
				'used_count'       => 12,
				'expiry_date'      => date( 'Y-m-d', strtotime( '+60 days' ) ),
			),
			array(
				'code'             => 'HAPPY45K',
				'discount_type'    => 'fixed',
				'discount_value'   => 45000.00,
				'min_order_amount' => 90000.00,
				'usage_limit'      => 300,
				'used_count'       => 8,
				'expiry_date'      => date( 'Y-m-d', strtotime( '+120 days' ) ),
			),
			array(
				'code'             => 'U22STUDENT',
				'discount_type'    => 'percentage',
				'discount_value'   => 30.00,
				'min_order_amount' => 50000.00,
				'usage_limit'      => 500,
				'used_count'       => 24,
				'expiry_date'      => date( 'Y-m-d', strtotime( '+180 days' ) ),
			),
		);

		foreach ( $coupons as $c ) {
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE code = %s", $c['code'] ) );
			if ( ! $exists ) {
				$wpdb->insert( $table, $c );
			}
		}
	}

	/**
	 * Seed Sample Completed Orders & Tickets for Instant Live Reports
	 */
	public static function seed_sample_orders( $showtime_ids ) {
		global $wpdb;
		$table_orders = $wpdb->prefix . 'booking_orders';

		$count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_orders" );
		if ( $count > 0 || empty( $showtime_ids ) ) {
			return; // Don't duplicate sample orders
		}

		$samples = array(
			array( 'name' => 'Nguyễn Văn An', 'phone' => '0901234567', 'email' => 'an.nguyen@gmail.com', 'seats' => array( 1, 2 ), 'combos' => array( 'combo_couple' => 1 ) ),
			array( 'name' => 'Trần Thị Bích', 'phone' => '0912345678', 'email' => 'bich.tran@yahoo.com', 'seats' => array( 15, 16 ), 'combos' => array( 'combo_solo' => 2 ) ),
			array( 'name' => 'Lê Hoàng Long', 'phone' => '0988765432', 'email' => 'long.le@outlook.com', 'seats' => array( 25, 26, 27 ), 'combos' => array( 'combo_party' => 1 ) ),
			array( 'name' => 'Phạm Thu Hương', 'phone' => '0933456789', 'email' => 'huong.pham@gmail.com', 'seats' => array( 33, 34 ), 'combos' => array() ),
		);

		foreach ( $samples as $idx => $sample ) {
			$st_id = $showtime_ids[ $idx % count( $showtime_ids ) ];
			$room_id = absint( get_post_meta( $st_id, '_mbs_room_id', true ) );
			$room_seats = MBS_DB::get_seats_by_room( $room_id );
			if ( empty( $room_seats ) ) continue;

			$target_seat_ids = array();
			$tickets_data = array();
			$tickets_total = 0;
			$base_price = (float) get_post_meta( $st_id, '_mbs_base_price', true ) ?: 95000;

			foreach ( $sample['seats'] as $seat_index ) {
				if ( isset( $room_seats[ $seat_index ] ) ) {
					$s = $room_seats[ $seat_index ];
					$target_seat_ids[] = $s->id;
					$price = $base_price + (float) $s->seat_price_modifier;
					$tickets_total += $price;

					$ticket_code = 'TKDEMO' . ( 1000 + $idx * 10 + $seat_index );
					$tickets_data[] = array(
						'showtime_id'  => $st_id,
						'seat_id'      => $s->id,
						'seat_label'   => $s->seat_row . sprintf( '%02d', $s->seat_number ),
						'seat_type'    => $s->seat_type,
						'price'        => $price,
						'ticket_code'  => $ticket_code,
						'qr_code_hash' => hash( 'sha256', $ticket_code . AUTH_KEY ),
					);
				}
			}

			if ( empty( $tickets_data ) ) continue;

			$order_code = 'MBSDEMO-' . ( 1000 + $idx );
			$combos_total = 0;
			$combos_list = array();
			if ( ! empty( $sample['combos'] ) ) {
				foreach ( $sample['combos'] as $cid => $qty ) {
					$cost = $qty * 109000;
					$combos_total += $cost;
					$combos_list[] = array( 'id' => $cid, 'name' => 'Combo Sweet Couple', 'qty' => $qty, 'price' => 109000, 'subtotal' => $cost );
				}
			}

			$subtotal = $tickets_total + $combos_total;
			$discount = ( $idx % 2 === 0 ) ? 20000 : 0;
			$final = max( 0, $subtotal - $discount );

			$order_data = array(
				'order_code'      => $order_code,
				'user_id'         => 1,
				'customer_name'   => $sample['name'],
				'customer_email'  => $sample['email'],
				'customer_phone'  => $sample['phone'],
				'total_amount'    => $subtotal,
				'discount_amount' => $discount,
				'final_amount'    => $final,
				'combos'          => $combos_list,
				'coupon_code'     => ( $discount > 0 ) ? 'WELCOME10' : '',
				'payment_method'  => ( $idx % 2 === 0 ) ? 'vnpay' : 'momo',
				'payment_status'  => 'paid',
				'transaction_id'  => 'TXN-DEMO-' . ( 1000 + $idx ),
				'session_token'   => '',
			);

			MBS_DB::create_order( $order_data, $tickets_data );
		}
	}
}
