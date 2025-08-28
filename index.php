<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Product.php';

$database = new Database();
$db = $database->getConnection();
$product = new Product($db);

$stmt = $product->readAll();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Store - Produk Digital Terpercaya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-digital-tachograph"></i> Digital Store
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">Produk</a>
                    </li>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <?php if($_SESSION['role'] == 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="admin/dashboard.php">
                                    <i class="fas fa-tachometer-alt"></i> Admin Panel
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="cart.php">
                                    <i class="fas fa-shopping-cart"></i> Keranjang
                                </a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i> <?php echo $_SESSION['full_name']; ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="profile.php">Profil</a></li>
                                <li><a class="dropdown-item" href="orders.php">Pesanan Saya</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Daftar</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section bg-gradient-primary text-white py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">Website Produk Digital</h1>
                    <p class="lead mb-4">Dapatkan Akses Berbagai Macam Produk Digital</p>
                    <a href="#products" class="btn btn-light btn-lg">Lihat Produk</a>
                </div>
                <div class="col-lg-6">
                    <img src="images/hero-image.jpg" alt="Digital Products" class="img-fluid rounded">
                </div>
            </div>
        </div>
    </section>

    <!-- Products Section -->
    <section id="products" class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Produk Digital Terpopuler</h2>
            <div class="row">
                <?php foreach($products as $prod): ?>
                <div class="col-md-4 mb-4">
                    <div class="card product-card h-100">
                        <img src="<?php echo $prod['image_url']; ?>" class="card-img-top" alt="<?php echo $prod['name']; ?>">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo $prod['name']; ?></h5>
                            <p class="card-text flex-grow-1"><?php echo substr($prod['description'], 0, 100); ?>...</p>
                            <div class="mt-auto">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="h5 text-primary mb-0">Rp <?php echo number_format($prod['price'], 0, ',', '.'); ?></span>
                                    <button class="btn btn-primary" onclick="addToCart(<?php echo $prod['id']; ?>)">
                                        <i class="fas fa-cart-plus"></i> Beli
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials-section bg-light py-5">
        <div class="container">
            <h2 class="text-center mb-5">APA KATA MEREKA</h2>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="testimonial-card">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <p class="card-text">"Mantap! Akunnya langsung aktif dan bisa dipakai buat daftar Canva Pro for Education. Prosesnya cepat banget!"</p>
                                <footer class="blockquote-footer">
                                    <cite title="Source Title">Andi, Mahasiswa IT</cite>
                                </footer>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="testimonial-card">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <p class="card-text">"Akhirnya bisa akses GitHub Student Developer Pack. Sangat membantu untuk tugas kuliah. Recommended seller!"</p>
                                <footer class="blockquote-footer">
                                    <cite title="Source Title">Citra, Teknik Informatika</cite>
                                </footer>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="testimonial-card">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <p class="card-text">"Awalnya ragu, tapi ternyata amanah. Akunnya work 100% dan dapat banyak bonus. Terima kasih banyak!"</p>
                                <footer class="blockquote-footer">
                                    <cite title="Source Title">Doni, Desain Grafis</cite>
                                </footer>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq-section py-5">
        <div class="container">
            <h2 class="text-center mb-5">TANYA JAWAB (FAQ)</h2>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq1">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1">
                                    Bagaimana cara saya menerima akunnya?
                                </button>
                            </h2>
                            <div id="collapse1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Detail login akun (email dan password) akan dikirimkan secara otomatis ke alamat email yang Anda masukkan saat pemesanan setelah pembayaran berhasil.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq2">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2">
                                    Apa saja keuntungan punya email .ac.id?
                                </button>
                            </h2>
                            <div id="collapse2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Banyak sekali! Anda bisa mendaftar ke layanan premium seperti Canva Pro, GitHub Student Pack, Office 365, dan mendapatkan diskon mahasiswa di berbagai platform lainnya.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq3">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3">
                                    Apakah akun ini legal dan aman?
                                </button>
                            </h2>
                            <div id="collapse3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Tentu. Akun yang kami sediakan adalah akun yang didapatkan secara resmi. Kami memberikan garansi login pertama kali dan keamanan data Anda terjamin.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Digital Store</h5>
                    <p>Platform terpercaya untuk produk digital berkualitas</p>
                </div>
                <div class="col-md-6">
                    <h5>Kontak</h5>
                    <p>Email: support@digitalstore.com<br>
                    WhatsApp: +62 812-3456-7890</p>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p>&copy; 2024 Digital Store. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html>