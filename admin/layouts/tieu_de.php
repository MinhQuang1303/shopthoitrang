<?php
// admin/layouts/tieu_de.php
if (session_status() === PHP_SESSION_NONE) session_start();
ob_start();

require_once __DIR__ . '/../../includes/ket_noi_db.php';
require_once __DIR__ . '/../../includes/ham_chung.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

$base_url = "/shopthoitrang/admin/";
$current_page = basename($_SERVER['PHP_SELF']);

// Logic Logo động (Giữ nguyên)
function get_logo_icons($current_page) {
    $icon_map = [
        'indexadmin.php'        => ['chart-line', 'tachometer-alt', 'gauge'],
        'quan_ly_danh_muc.php'  => ['folder-tree', 'tags', 'folder-open'],
        'quan_ly_san_pham.php'  => ['box', 'cubes', 'dolly'],
        'quan_ly_don_hang.php'  => ['receipt', 'shopping-bag', 'file-invoice'],
    ];
    if (isset($icon_map[$current_page])) {
        return $icon_map[$current_page];
    }
    return ['cube', 'cogs', 'star'];
}
$logo_icons = get_logo_icons($current_page);
?>

<!DOCTYPE html>
<html lang="vi" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Quản trị hệ thống</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --primary: #4361ee;
            --primary-hover: #3a56d4;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b; /* Màu vàng cam */
            --dark: #1f2937;
            --light: #f8fafc;
            --gray: #94a3b8;
            --border: #e2e8f0;
            --sidebar-bg: #1e293b;
            --sidebar-hover: #334155;
            --sidebar-glow: 0 0 20px rgba(67, 97, 238, 0.3);
        }

        [data-theme="dark"] {
            --primary: #5b7aff;
            --light: #1e293b;
            --dark: #f1f5f9;
            --gray: #64748b;
            --border: #334155;
            --sidebar-bg: #0f172a;
            --sidebar-hover: #1e293b;
            --sidebar-glow: 0 0 20px rgba(91, 122, 255, 0.4);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            color: var(--dark);
            transition: all 0.4s ease;
            margin: 0;
            overflow-x: hidden;
        }

        [data-theme="dark"] body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #e2e8f0;
        }

        /* TOPBAR */
        .topbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 64px;
            background: white;
            border-bottom: 1px solid var(--border);
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.5rem;
            z-index: 1001;
            backdrop-filter: blur(10px);
        }

        [data-theme="dark"] .topbar {
            background: rgba(30, 41, 59, 0.95);
            border-bottom: 1px solid #334155;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }

        .menu-toggle {
            font-size: 1.6rem;
            color: var(--primary);
            cursor: pointer;
            padding: 10px;
            border-radius: 50%;
            background: rgba(67, 97, 238, 0.1);
            transition: all 0.3s;
            position: relative;
            z-index: 1002;
        }

        .menu-toggle:hover {
            background: var(--primary);
            color: white;
            transform: rotate(90deg) scale(1.1);
            box-shadow: 0 0 15px rgba(67, 97, 238, 0.4);
        }

        .topbar h5 {
            margin: 0;
            font-weight: 700;
            font-size: 1.3rem;
            color: var(--dark);
            background: linear-gradient(90deg, var(--primary), #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        [data-theme="dark"] .topbar h5 {
            color: #e2e8f0;
        }

        /* LOGO 3D CUBE (Giữ nguyên) */
        .logo-container { width: 40px; height: 40px; perspective: 800px; cursor: pointer; position: relative; }
        .logo-cube { width: 100%; height: 100%; position: relative; transform-style: preserve-3d; animation: cubeRotate 8s infinite linear; transition: all 0.4s ease; }
        .logo-cube:hover { animation-play-state: paused; transform: scale(1.2) rotateX(-15deg) rotateY(-15deg); filter: drop-shadow(0 0 20px rgba(67, 97, 238, 0.6)); }
        .logo-cube .face { position: absolute; width: 40px; height: 40px; background: linear-gradient(135deg, var(--primary), #7c3aed); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; color: white; backface-visibility: hidden; box-shadow: inset 0 0 10px rgba(255,255,255,0.3); }
        .logo-cube .face i { animation: iconPulse 2s infinite; }
        .face.front  { transform: translateZ(20px); } .face.back   { transform: rotateY(180deg) translateZ(20px); } .face.right  { transform: rotateY(90deg) translateZ(20px); } .face.left   { transform: rotateY(-90deg) translateZ(20px); } .face.top    { transform: rotateX(90deg) translateZ(20px); } .face.bottom { transform: rotateX(-90deg) translateZ(20px); }
        @keyframes cubeRotate { 0% { transform: rotateX(0deg) rotateY(0deg); } 100% { transform: rotateX(360deg) rotateY(360deg); } }
        @keyframes iconPulse { 0%, 100% { transform: scale(1); opacity: 0.8; } 50% { transform: scale(1.2); opacity: 1; } }
        [data-theme="dark"] .logo-cube .face { background: linear-gradient(135deg, #5b7aff, #8b5cf6); }
        [data-theme="dark"] .logo-cube:hover { filter: drop-shadow(0 0 25px rgba(91, 122, 255, 0.7)); }


        /* Nút Quay về Shop */
        .btn-shop-link {
            background: var(--warning);
            color: var(--dark);
            border: none;
            font-weight: 600;
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.3s;
            text-decoration: none;
            box-shadow: 0 2px 10px rgba(245, 158, 11, 0.4);
        }
        
        .btn-shop-link:hover {
            background: #e6910a;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.6);
        }
        
        [data-theme="dark"] .btn-shop-link {
             background: var(--warning);
             color: var(--dark);
        }
        
        /* SIDEBAR (Giữ nguyên) */
        .sidebar { position: fixed; top: 0; left: 0; width: 280px; height: 100vh; background: var(--sidebar-bg); color: #e2e8f0; padding-top: 80px; transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1); transform: translateX(-220px); z-index: 1000; overflow: hidden; box-shadow: 2px 0 20px rgba(0,0,0,0.15); }
        [data-theme="dark"] .sidebar { box-shadow: 2px 0 25px rgba(0,0,0,0.4); }
        .sidebar.mini { width: 70px; transform: translateX(0); }
        .sidebar.mini .nav-text, .sidebar.mini .logout-btn span { display: none; }
        .sidebar.mini .nav-link i { font-size: 1.3rem; }
        .sidebar.mini .nav-link { justify-content: center; padding: 1rem; margin: 0.4rem 0.8rem; }
        .sidebar.mini .logout-btn { padding: 0.8rem; text-align: center; }
        .sidebar:hover { transform: translateX(0) !important; width: 280px !important; box-shadow: var(--sidebar-glow), 2px 0 30px rgba(0,0,0,0.2); }
        .sidebar:hover .nav-text, .sidebar:hover .logout-btn span { display: inline; }
        .sidebar .nav-link { color: #94a3b8; padding: 1rem 1.3rem; margin: 0.4rem 1rem; border-radius: 14px; font-size: 0.95rem; font-weight: 500; display: flex; align-items: center; gap: 0.9rem; transition: all 0.3s ease; position: relative; overflow: hidden; }
        .sidebar .nav-link::before { content: ''; position: absolute; left: 0; top: 0; height: 100%; width: 4px; background: var(--primary); transform: scaleY(0); transition: transform 0.3s; }
        .sidebar .nav-link:hover::before, .sidebar .nav-link.active::before { transform: scaleY(1); }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: var(--sidebar-hover); color: #fff; transform: translateX(4px); box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        .sidebar .nav-link i { width: 24px; text-align: center; font-size: 1.1rem; }
        .logout-btn { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; margin: 2rem 1rem 1rem; padding: 1rem; border-radius: 14px; text-align: center; font-weight: 600; font-size: 0.95rem; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3); }
        .logout-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4); }
        .content { margin-top: 74px; padding: 2rem; transition: margin-left 0.4s ease; }
        @media (min-width: 992px) { .sidebar { transform: translateX(0); } .content { margin-left: 70px; } .sidebar:not(.mini):hover ~ .content, .sidebar:hover ~ .content { margin-left: 280px; } }
        .theme-toggle { background: rgba(67, 97, 238, 0.1); border: none; font-size: 1.3rem; color: var(--primary); cursor: pointer; padding: 10px; border-radius: 50%; transition: all 0.3s; }
        .theme-toggle:hover { background: var(--primary); color: white; transform: rotate(360deg); }
        .nav-tooltip { position: absolute; left: 80px; background: #1f2937; color: white; padding: 8px 12px; border-radius: 8px; font-size: 0.85rem; white-space: nowrap; opacity: 0; pointer-events: none; transition: opacity 0.3s; z-index: 1002; box-shadow: 0 4px 15px rgba(0,0,0,0.3); }
        .sidebar.mini .nav-link:hover .nav-tooltip { opacity: 1; }
    </style>
</head>
<body>

<div class="topbar">
    <i class="fas fa-bars menu-toggle" id="menuToggle"></i>

    <div class="d-flex align-items-center gap-3">
        <div class="logo-container" title="Admin Panel">
            <div class="logo-cube">
                <div class="face front"><i class="fas fa-cube"></i></div>
                <div class="face back"><i class="fas fa-cogs"></i></div>
                <div class="face right"><i class="fas fa-star"></i></div>
                <div class="face left"><i class="fas fa-shield-alt"></i></div>
                <div class="face top"><i class="fas fa-gem"></i></div>
                <div class="face bottom"><i class="fas fa-bolt"></i></div>
            </div>
        </div>
        <h5 class="m-0"><span class="d-none d-md-inline">Admin Panel</span></h5>
    </div>

    <div class="d-flex align-items-center gap-3">
        
        <a href="../index.php" class="btn-shop-link" title="Quay lại Trang Chủ">
            <i class="fas fa-home me-1"></i> Shop
        </a>
        
        <button class="theme-toggle" id="themeToggle" title="Đổi giao diện">
            <i class="fas fa-moon"></i>
        </button>
        <span class="text-muted small">Chào, <strong><?= $_SESSION['user']['full_name'] ?? 'Admin' ?></strong></span>
    </div>
</div>

<div class="sidebar mini" id="sidebar">
    <nav class="nav flex-column">
        <?php
        $menu_items = [
            'indexadmin.php' => ['Bảng điều khiển', 'fa-gauge'],
            'quan_ly_danh_muc.php' => ['Quản lý Danh mục', 'fa-folder-tree'],
            'quan_ly_san_pham.php' => ['Quản lý Sản phẩm', 'fa-box'],
            'quan_ly_bien_the.php' => ['Quản lý Biến thể', 'fa-tags'],
            'quan_ly_ton_kho.php' => ['Quản lý Tồn kho', 'fa-warehouse'],
            'quan_ly_don_hang.php' => ['Quản lý Đơn hàng', 'fa-receipt'],
            'chi_tiet_don_hang.php' => ['Chi tiết Đơn hàng', 'fa-file-invoice'],
            'quan_ly_ma_giam_gia.php' => ['Mã giảm giá', 'fa-ticket-alt'],
            'quan_ly_nguoi_dung.php' => ['Quản lý Người dùng', 'fa-users'],
            'quan_ly_danh_gia.php' => ['Đánh giá', 'fa-star'],
            'quan_ly_su_kien.php' => ['Sự kiện / Bài viết', 'fa-calendar-check'],
            'quan_ly_diem_tich_luy.php' => ['Điểm Tích lũy', 'fa-coins'],
            'quan_ly_tu_khoa.php' => ['Từ khóa tìm kiếm', 'fa-search'],
            'quan_ly_thanh_toan.php' => ['Giao dịch Thanh toán', 'fa-credit-card'],
            'nhat_ky_admin.php' => ['Nhật ký Hoạt động', 'fa-scroll'],
        ];

        foreach ($menu_items as $file => $data) {
            [$text, $icon] = $data;
            $active = $current_page === $file ? 'active' : '';
            echo "<a href=\"$base_url$file\" class=\"nav-link $active\">
                    <i class=\"fas $icon\"></i>
                    <span class=\"nav-text\">$text</span>
                    <span class=\"nav-tooltip\">$text</span>
                  </a>";
        }
        ?>
    </nav>

    <a href="<?= $base_url ?>dang_xuat.php" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i> <span>Đăng xuất</span>
    </a>
</div>

<div class="content">
