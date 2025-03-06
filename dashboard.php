<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Halaman Utama</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: #f0f4f8;
            background-image: ('bulan.png')
            color: #333;
        }

        .container {
            max-width: 1100px;
            margin-top: 60px;
        }

        /* Navbar */
        .navbar {
            background: linear-gradient(135deg,rgb(0, 0, 0),rgb(1, 17, 36)3b7d);
            border-radius: 0 0 20px 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand, .navbar-nav .nav-link {
            color: #fff !important;
        }

        .hero-section {
            background: linear-gradient(135deg,rgb(0, 0, 0),rgb(0, 0, 0));
            color: #fff;
            padding: 80px 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
        }

        .hero-section h1 {
            font-size: 2.5rem;
            font-weight: bold;
        }

        .hero-section .btn {
            background: #ff6600;
            color: #fff;
            padding: 12px 30px;
            font-size: 1.2rem;
            border-radius: 30px;
            transition: all 0.3s ease;
        }

        .hero-section .btn:hover {
            background: #e65c00;
            transform: scale(1.05);
        }

        /* Cards */
        .card {
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
        }

        .card .btn {
            background: #0056b3;
            color: #fff;
            padding: 12px 25px;
            border-radius: 30px;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .card .btn:hover {
            background: #003b7d;
            transform: scale(1.05);
        }

        /* Footer */
        footer {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, #0056b3, #003b7d);
            color: white;
            border-radius: 12px;
            margin-top: 60px;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Halaman Utama</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php"><i class="bi bi-person-circle"></i> Profil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Keluar</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero-section">
        <h1>Selamat Datang!</h1>
        <p>Kelola tugas Anda dengan mudah. Pilih kategori tugas di bawah.</p>
        <a href="#categories" class="btn">Mulai Sekarang</a>
    </section>

    <div class="container my-5" id="categories">
        <h2 class="text-center mb-5">Pilih Kategori Tugas Anda</h2>
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Tugas Sekolah</h5>
                        <p class="card-text">Kelola tugas sekolah Anda dengan cepat dan mudah.</p>
                        <a href="tugas_sekolah.php" class="btn w-100">Masuk ke Tugas Sekolah</a>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Tugas Rumah</h5>
                        <p class="card-text">Selesaikan tugas rumah Anda dengan cara yang lebih terorganisir.</p>
                        <a href="tugas_rumah.php" class="btn w-100">Masuk ke Tugas Rumah</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; 2025 Manajemen Tugas. Semua Hak Dilindungi.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
