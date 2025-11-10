<?php
require_once __DIR__ . '/includes/ket_noi_db.php';
require_once __DIR__.'/views/tieu_de.php';
// Lấy danh sách sự kiện
$stmt = $pdo->query("SELECT * FROM Events WHERE is_published = 1 ORDER BY event_date DESC");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!-- ======= DANH SÁCH SỰ KIỆN ======= -->
<div class="container my-5">
    <div class="row g-4">
        <?php if (empty($events)): ?>
            <div class="col-12 text-center text-muted">
                <h5>Chưa có sự kiện nào được đăng!</h5>
            </div>
        <?php else: ?>
            <?php foreach ($events as $e): ?>
                <div class="col-md-4 col-sm-6">
                    <div class="card event-card h-100">
                        <?php if (!empty($e['image_url'])): ?>
                            <img src="<?= htmlspecialchars($e['image_url']) ?>" class="card-img-top" alt="Sự kiện">
                        <?php else: ?>
                            <img src="https://via.placeholder.com/400x200?text=No+Image" class="card-img-top" alt="Không có ảnh">
                        <?php endif; ?>

                        <div class="card-body">
                            <h5 class="card-title text-primary fw-bold"><?= htmlspecialchars($e['title']) ?></h5>
                            <p class="event-date">📅 <?= date('d/m/Y', strtotime($e['event_date'])) ?></p>
                            <p class="card-text event-content"><?= nl2br(htmlspecialchars(substr($e['content'], 0, 120))) ?>...</p>
                            <a href="su_kien_chi_tiet.php?id=<?= $e['event_id'] ?>" class="btn btn-outline-primary btn-sm mt-2">Xem chi tiết</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/views/chan_trang.php'; ?>
