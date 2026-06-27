# 📘 Hướng Dẫn Phân Chia Công Việc & Quy Tắc Viết Code MVC

Tài liệu này giúp các thành viên trong nhóm biết chính xác **mình cần viết tính năng gì ở thư mục nào** và phân chia công việc để tránh conflict code khi làm việc chung.

---

## 1. Quy Tắc Viết Code Theo Luồng MVC (Quyết định viết code ở đâu?)

Khi cần code một tính năng mới (ví dụ: hiển thị danh sách phim, đặt vé, đăng nhập...), hãy tuân theo quy tắc phân chia nhiệm vụ của các thư mục dưới đây:

### 📂 Models (`app/Models/`) — Tương tác Database
*   **Viết gì ở đây:** Các câu lệnh truy vấn SQL (SELECT, INSERT, UPDATE, DELETE), các hàm lấy danh sách dữ liệu từ bảng.
*   **Quy tắc:**
    *   Mỗi bảng trong database nên có một Model tương ứng kế thừa `BaseModel` (ví dụ: bảng `movies` có `MovieModel`).
    *   **TUYỆT ĐỐI KHÔNG** dùng `echo`, `die()`, không render HTML hay gọi redirect trang ở đây. Model chỉ nhận tham số và trả về dữ liệu (mảng/đối tượng) hoặc trạng thái true/false.

### 📂 Services (`app/Services/`) — Xử lý Logic Nghiệp vụ Phức Tạp
*   **Viết gì ở đây:** Các logic tính toán nâng cao mà Model hay Controller viết vào sẽ bị dài dòng.
    *   *Ví dụ:* Tính tổng tiền vé (cộng thêm phụ thu ghế VIP + phụ thu suất chiếu tối + áp mã giảm giá), kiểm tra mật khẩu trùng khớp, xử lý mã hóa, gọi API cổng thanh toán VNPay/Momo.
*   **Quy tắc:** Controller sẽ gọi sang Service để lấy kết quả xử lý, tránh viết logic tính toán dài dòng trong Controller.

### 📂 Controllers (`app/Controllers/`) — Điều hướng & Xử lý Yêu Cầu
*   **Viết gì ở đây:** Nơi tiếp nhận yêu cầu đầu tiên từ URL.
    1.  Nhận tham số người dùng gửi lên (từ form POST, hoặc URL GET qua Request).
    2.  Kiểm tra (validate) xem dữ liệu hợp lệ không (ví dụ: email có trống không).
    3.  Gọi Model hoặc Service tương ứng để xử lý hoặc lưu dữ liệu vào DB.
    4.  Truyền dữ liệu thu được sang View để hiển thị hoặc thực hiện `redirect()` sang trang khác.
*   **Quy tắc:** Giữ Controller ngắn gọn (chỉ làm nhiệm vụ nhận input -> gọi xử lý -> trả về view/redirect).

### 📂 Views (`app/Views/`) — Giao diện & Hiển thị
*   **Viết gì ở đây:** File HTML + CSS + JS và mã PHP cơ bản để hiển thị dữ liệu.
*   **Quy tắc:**
    *   **TUYỆT ĐỐI KHÔNG** kết nối database hay viết câu lệnh SQL trong View.
    *   Chỉ dùng vòng lặp PHP (`foreach`, `if`) để hiển thị dữ liệu dạng mảng mà Controller đã truyền sang.

### 📂 Tài nguyên Tĩnh & File Upload (`public/`)
*   **`public/assets/css/` hoặc `public/assets/js/`:** Chứa file CSS, JS tĩnh tự viết.
*   **`public/uploads/`:** Nơi lưu trữ các file do người dùng upload lên (như ảnh poster phim khi admin thêm phim, ảnh avatar). Cấm lưu file upload vào trong thư mục `app/`.

---

## 2. Bảng Phân Chia Phạm Vi Tác Động File (File Scope)

Để tránh conflict, nguyên tắc cốt lõi là: **Hạn chế tối đa việc 2 người cùng sửa một file cùng một lúc.**

| Thành viên | Nhiệm vụ | Thư mục & File được phép sửa / tạo mới |
| :--- | :--- | :--- |
| **Hữu Tiền** *(Leader)* | - Core Architecture<br>- Database Schema<br>- Authentication Backend<br>- Booking Backend | - `app/Core/*` (Core Framework)<br>- `config/*` (Cấu hình)<br>- `.env`, `.env.example`<br>- `database/BookingTicketDatabase.sql`<br>- `app/Controllers/AuthController.php`<br>- `app/Models/UserModel.php`<br>- `app/Services/AuthService.php`<br>- `app/Controllers/BookingController.php`<br>- `app/Models/BookingModel.php`<br>- `app/Models/TicketModel.php`<br>- `app/Services/BookingService.php` |
| **Nhân** | - Movie Module<br>- Genre Module | - `app/Controllers/MovieController.php`<br>- `app/Controllers/Admin/MovieAdminController.php`<br>- `app/Models/MovieModel.php`<br>- `app/Models/GenreModel.php`<br>- `app/Services/MovieService.php`<br>- `app/Views/movies/*` (Chi tiết phim...)<br>- `app/Views/admin/movies/*` (Giao diện CRUD phim...) |
| **Tiến** | - Room Module<br>- Seat Module<br>- Showtime Module | - `app/Controllers/ShowtimeController.php`<br>- `app/Models/RoomModel.php`<br>- `app/Models/SeatModel.php`<br>- `app/Models/ShowtimeModel.php`<br>- `app/Views/booking/select_showtime.php`<br>- `app/Views/booking/select_seat.php` |
| **Quỳnh Anh** | - User Frontend (Giao diện người dùng) | - `app/Views/layouts/` (header.php, footer.php)<br>- `app/Views/home/index.php` (Trang chủ)<br>- `app/Views/auth/` (Giao diện login, register)<br>- `public/assets/css/` (`global.css`, `header.css`, `footer.css`, `home.css`) |
| **Thịnh** | - Booking Frontend (Giao diện đặt vé) | - `app/Views/booking/` (movie_list.php, confirm.php, payment.php, success.php)<br>- `public/assets/css/` (`seat.css`, `showtime.css`) |
| **TBD (Cả nhóm)** | - Admin UI<br>- Viết Test, Docs | - `app/Views/admin/*`<br>- `docs/*` |

---

## 3. Quy Tắc Định Tuyến (Routes) Để Tránh Conflict

File duy nhất cả nhóm bắt buộc phải sửa chung là [routes/web.php](file:///d:/Downloads/laragon/www/movie-ticket-booking/routes/web.php). Để tránh conflict file này, hãy tuân thủ quy tắc sau:

1. **Phân chia khu vực trong routes:** Đọc kỹ chú thích trong file routes và chỉ thêm route ở khu vực của mình.
2. **Khai báo gọn gàng:** Sử dụng cú pháp array thay vì string để dễ phát hiện lỗi.
    * *Đúng (Chuẩn dự án):* `$router->get('/movies', [\App\Controllers\MovieController::class, 'index']);`
    * *Không dùng:* `$router->get('/movies', 'MovieController@index');`

---

## 4. Ví Dụ Mẫu: Luồng Phát Triển Module Movie (Dành cho cả nhóm tham khảo)

Khi phát triển bất kỳ module nào (Movie, Showtime, Booking...), hãy đi theo luồng chuẩn hóa sau để đồng nhất:

### Bước 4.1: Kiểm tra Database thực tế
Luôn đối chiếu đúng cấu trúc bảng trong [BookingTicketDatabase.sql](file:///d:/Downloads/laragon/www/movie-ticket-booking/database/BookingTicketDatabase.sql). 
*   *Lưu ý:* Bảng liên kết thể loại là `movie_genre` (số ít), cột chứa ảnh là `poster` chứ không phải `poster_url`.

### Bước 4.2: Viết Model (`app/Models/MovieModel.php`)
Kế thừa từ `BaseModel` để tái sử dụng kết nối database và các hàm CRUD có sẵn.
*   *Sử dụng hàm cha:* Hãy dùng trực tiếp hàm `findAll()` và `findById($id)` đã được định nghĩa trong `BaseModel`.
*   *Ví dụ code:*
    ```php
    namespace App\Models;

    class MovieModel extends BaseModel {
        protected string $table = 'movies';
        
        // Chỉ viết thêm các hàm đặc thù của Movie
        public function findByStatus($status) {
            $stmt = $this->db->prepare("SELECT * FROM movies WHERE status = :status AND is_active = 1");
            $stmt->execute(['status' => $status]);
            return $stmt->fetchAll();
        }
    }
    ```

### Bước 4.3: Viết Service (`app/Services/MovieService.php`)
Xử lý logic nghiệp vụ và đóng gói dữ liệu (như tính phân trang) để Controller sử dụng.
*   *Ví dụ:*
    ```php
    namespace App\Services;

    use App\Models\MovieModel;

    class MovieService {
        private MovieModel $movieModel;

        public function __construct() {
            $this->movieModel = new MovieModel();
        }

        public function getMovieList($page = 1, $perPage = 6) {
            // Logic tính phân trang offset...
            $movies = $this->movieModel->findAll(); 
            return [
                'data' => $movies,
                'current_page' => $page,
                'total_pages' => 3
            ];
        }
    }
    ```

### Bước 4.4: Viết Controller (`app/Controllers/MovieController.php`)
Nhận tham số qua `Request`, gọi `MovieService` và render View.
*   *Ví dụ:*
    ```php
    namespace App\Controllers;

    use App\Core\Controller;
    use App\Services\MovieService;

    class MovieController extends Controller {
        private MovieService $movieService;

        public function __construct() {
            $this->movieService = new MovieService();
        }

        public function index() {
            $page = $_GET['page'] ?? 1;
            $data = $this->movieService->getMovieList($page);
            return $this->view('movies/index', $data);
        }
    }
    ```

### Bước 4.5: Đăng ký Route tương thích hệ thống
Khai báo route trong `routes/web.php` bằng dạng Class Array. Vì Router của chúng ta so khớp URL chính xác, nên với các URL chi tiết/tìm kiếm, ta khai báo như sau:
```php
$router->get('/movies', [\App\Controllers\MovieController::class, 'index']);
$router->get('/movies/detail', [\App\Controllers\MovieController::class, 'show']); // Nhận ID qua $_GET['id']
$router->get('/movies/search', [\App\Controllers\MovieController::class, 'search']); // Nhận từ khóa qua $_GET['q']
```

---

## 5. Quy Trình Git Để Tránh Trùng Lặp

Để việc merge code vào nhánh chung (`develop`) không bị lỗi:

1. **Không code trực tiếp trên nhánh `develop` hoặc `main`.**
2. Mỗi khi làm một tính năng mới, hãy tạo một nhánh feature riêng từ `develop`:
   ```bash
   git checkout develop
   git pull origin develop
   git checkout -b feature/tên-tính-năng
   ```
3. **Trước khi tạo Pull Request (PR):** Hãy merge ngược `develop` vào nhánh của bạn trước để giải quyết conflict ở máy local của bạn trước khi đưa lên GitHub:
   ```bash
   git checkout feature/tên-tính-năng
   git merge develop
   # Giải quyết conflict (nếu có) trên VS Code
   # Commit và push lên GitHub để tạo PR
   ```

---

## 6. Cách Tích Hợp Giữa Các Thành Viên (Integration)

* **Giữa Frontend và Backend:**
  * Người làm Frontend (Quỳnh Anh, Thịnh) thiết kế giao diện tĩnh (HTML/CSS) trong thư mục `Views`.
  * Người làm Backend (Tiền, Nhân, Tiến) sẽ lấy file View đó, thay thế dữ liệu tĩnh bằng vòng lặp PHP (`foreach`) lấy từ database qua Model.
* **Tích hợp Authentication:**
  * Các thành viên làm Module khác cứ code bình thường. Khi Leader (Hữu Tiền) làm xong Auth, các thành viên chỉ cần gọi thêm các Middleware hoặc kiểm tra `$_SESSION['user']` ở đầu Controller của mình.

---

*Chúc cả nhóm phối hợp làm việc vui vẻ và hiệu quả!*
