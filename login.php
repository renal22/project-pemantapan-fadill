<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: todo.php');
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'todo_app');
if ($conn->connect_error) die("Koneksi database gagal: " . $conn->connect_error);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param('s', $_POST['email']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($_POST['password'], $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name']; // Simpan nama pengguna
            $_SESSION['profile_picture'] = $user['profile_picture']; // Simpan foto profil
            header('Location: todo.php');
            exit;
        } else {
            $error = "Password salah.";
        }
    } else {
        $error = "Email tidak ditemukan.";
    }
}

// Pesan Hari

$pesanHari = [
    "Monday" => "Selamat Hari Senin!💪",
    "Tuesday" => "Selamat Hari Selasa!🚀",
    "Wednesday" => "Selamat Hari Rabu!☕",
    "Thursday" => "Selamat Hari Kamis!🎉",
    "Friday" => "Selamat Hari Jumat! libur eyy🤙🤙",
    "Saturday" => "Selamat Hari Sabtu!😎",
    "Sunday" => "Selamat Hari Minggu!☀️"
];

$pesanHariIni = $pesanHari[date("l")];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - To-Do List</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <style>



        body {
            background: linear-gradient(135deg,rgb(255, 255, 255),rgb(249, 253, 251));
            font-family: 'Poppins', sans-serif;
            background-image: url("bulan.png");
            background-repeat: no-repeat;
            background-size: cover;
        }
        

        .login-container {
            max-width: 420px;
            margin: 0 auto;
            padding: 25px;
            background-color: #ffffff; 
            border-radius: 12px; 
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            animation: fadeIn 1s ease-in-out;
        }

        .login-container h2 {
            font-size: 1.8rem;
            color: #333;
            text-align: center;
            margin-bottom: 15px;
        }

        .description {
            background: rgb(255, 255, 255);
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            font-size: 1.1rem;
            color: #333;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .bg-message {
            background-color: #FFFFFF
            color: white;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 1.3rem;
            font-weight: bold;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .form-control, .btn {
            border-radius: 8px;
        }

        .btn-primary {
            background-color:  #060809; 
            border: none;
            transition: transform 0.3s ease-in-out;
        }

        .btn-primary:hover {
            transform: scale(1.05);
            background-color: #1e272c
        }

        .input-group-text {
            background: #060809; 
            color: white;
            border-radius: 8px 0 0 8px;
        }

        .toggle-password i {
            font-size: 1.25rem;
            cursor: pointer;
            color: #060809;
        }

        .toggle-password:hover i {
            color: #1e272c;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 575px) {
            .login-container {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="bg-message">
            <?= htmlspecialchars($pesanHariIni) ?>
        </div>

        <div class="container description">
            <h4>Wilujeng Sumping di To-Do List!</h4>
            <p>Kelola tugas harianmu dengan lebih mudah dan efisien.</p>
        </div>

        <div class="d-flex justify-content-center align-items-center" style="min-height: 80vh;">
            <div class="login-container">
                <h2>Login</h2>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"> <?= htmlspecialchars($error) ?> </div>
                <?php endif; ?>

                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" name="password" id="password" class="form-control" required>
                            <button type="button" class="btn btn-outline-secondary toggle-password" onclick="togglePassword()">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-box-arrow-in-right"></i> Login</button>
                    <p class="mt-2 text-center text-muted">Belum punya akun? <a href="register.php">Daftar</a></p>
                </form>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            var passwordField = document.getElementById('password');
            var icon = document.querySelector('.toggle-password i');
            if (passwordField.type === "password") {
                passwordField.type = "text";
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                passwordField.type = "password";
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }
    </script>
</body>
</html>
