<?php
require_once '../includes/ket_noi_db.php';
include 'layouts/tieu_de.php';
?>

<div class="container mt-4">
    <h3 class="mb-4 text-primary">🔍 Quản lý Từ khóa tìm kiếm nổi bật</h3>

    <?php
    // Xử lý thêm từ khóa
    if (isset($_POST['them_tu_khoa'])) {
        $tu_khoa = trim($_POST['tu_khoa']);
        if ($tu_khoa != '') {
            $stmt = $conn->prepare("INSERT INTO tu_khoa_noi_bat (tu_khoa) VALUES (?)");
            $stmt->bind_param("s", $tu_khoa);
            $stmt->execute();
        }
    }

    // Xử lý xóa
    if (isset($_GET['xoa'])) {
        $id = $_GET['xoa'];
        $conn->query("DELETE FROM tu_khoa_noi_bat WHERE id = $id");
    }

    $result = $conn->query("SELECT * FROM tu_khoa_noi_bat ORDER BY id DESC");
    ?>

    <form method="POST" class="d-flex mb-3">
        <input type="text" name="tu_khoa" class="form-control me-2" placeholder="Nhập từ khóa nổi bật..." required>
        <button class="btn btn-success" name="them_tu_khoa">Thêm</button>
    </form>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>#</th>
                <th>Từ khóa</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $stt = 1;
            while ($row = $result->fetch_assoc()):
            ?>
            <tr>
                <td><?= $stt++ ?></td>
                <td><?= htmlspecialchars($row['tu_khoa']) ?></td>
                <td>
                    <a href="?xoa=<?= $row['id'] ?>" onclick="return confirm('Xóa từ khóa này?')" class="btn btn-sm btn-danger">Xóa</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include 'layouts/chan_trang.php'; ?>
