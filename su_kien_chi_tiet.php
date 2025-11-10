<?php

require_once __DIR__ . '/includes/ket_noi_db.php';

// Lấy id sự kiện
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM Events WHERE event_id = ? AND is_published = 1 LIMIT 1");
$stmt->execute([$id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    echo "<div class='container mt-5 text-center'><h4 class='text-danger'>❌ Sự kiện không tồn tại hoặc đã bị ẩn!</h4>
          <a href='su_kien.php' class='btn btn-secondary mt-3'>⬅ Quay lại</a></div>";
    require_once __DIR__ . '/views/chan_trang.php';
    exit;
}
?>

<style>
    .event-detail-header {
        background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)),
                    url('<?= htmlspecialchars($event['image_url'] ?? "https://via.placeholder.com/1200x500?text=Event") ?>') center/cover;
        height: 400px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        color: white;
        text-shadow: 0 2px 8px rgba(0,0,0,0.6);
        text-align: center;
        padding: 30px;
    }

    .event-detail-header h1 {
        font-size: 2.8rem;
        font-weight: 800;
        color: #FFD700;
        max-width: 900px;
    }

    .event-date {
        font-size: 1rem;
        margin-top: 10px;
        color: #f1f1f1;
    }

    .event-content {
        max-width: 900px;
        margin: 50px auto;
        padding: 40px;
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 6px 18px rgba(0,0,0,0.1);
        line-height: 1.7;
        font-size: 1.05rem;
    }

    .event-content img {
        max-width: 100%;
        border-radius: 12px;
        margin: 15px 0;
    }

    .back-btn {
        display: inline-block;
        margin-top: 20px;
        text-decoration: none;
        background: #007bff;
        color: white;
        padding: 8px 18px;
        border-radius: 30px;
        transition: 0.3s;
    }
    .back-btn:hover {
        background: #0056b3;
        transform: translateY(-2px);
    }
</style>

<!-- ======= HEADER ======= -->
<div class="event-detail-header">
    <h1><?= htmlspecialchars($event['title']) ?></h1>
    <div class="event-date">📅 Ngày: <?= date('d/m/Y', strtotime($event['event_date'])) ?></div>
</div>

<!-- ======= NỘI DUNG ======= -->
<div class="event-content">
    <?php if (!empty($event['image_url'])): ?>
        <img src="<?= htmlspecialchars($event['image_url']) ?>" alt="Sự kiện">
    <?php endif; ?>

    <p><?= nl2br(htmlspecialchars($event['content'])) ?></p>

    <div class="text-center">
        <a href="su_kien.php" class="back-btn">⬅ Quay lại danh sách</a>
    </div>
</div>

<?php require_once __DIR__ . '/views/chan_trang.php'; ?>
