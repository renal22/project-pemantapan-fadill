<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'todo_app');
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$todos = [];
$subtasks = [];
$search = $_GET['search'] ?? '';
$sort_by = $_GET['sort_by'] ?? '';
$current_date = date('l, d F Y');

// Query Statistik Tugas Sekolah
$query_sekolah = "SELECT 
            COUNT(*) AS total_tasks, 
            SUM(completed) AS completed_tasks,
            SUM(CASE WHEN deadline < NOW() AND completed = 0 THEN 1 ELSE 0 END) AS overdue_tasks
          FROM tasks 
          WHERE user_id = ?";
$stmt_sekolah = $conn->prepare($query_sekolah);
$stmt_sekolah->bind_param('i', $user_id);
$stmt_sekolah->execute();
$result_sekolah = $stmt_sekolah->get_result();
$data_sekolah = $result_sekolah->fetch_assoc() ?? [
    'total_tasks' => 0,
    'completed_tasks' => 0,
    'overdue_tasks' => 0
];


// Query untuk tugas
$query = "SELECT * FROM tasks WHERE user_id = ? AND task LIKE ?";
if ($sort_by == 'completed') {
    $query .= " ORDER BY completed DESC";
} elseif ($sort_by == 'deadline') {
    $query .= " ORDER BY deadline ASC";
} elseif ($sort_by == 'priority') {
    $query .= " ORDER BY CASE 
                WHEN priority = 'Penting' THEN 1
                WHEN priority = 'Sedang' THEN 2
                WHEN priority = 'Biasa' THEN 3
                ELSE 4 
              END ASC";
} else {
    $query .= " ORDER BY created_at DESC";
}

$stmt = $conn->prepare($query);
$like_search = "%$search%";
$stmt->bind_param('is', $user_id, $like_search);
$stmt->execute();
$todos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

foreach ($todos as $todo) {
    $stmt = $conn->prepare("SELECT * FROM subtasks WHERE task_id = ?");
    $stmt->bind_param('i', $todo['id']);
    $stmt->execute();
    $subtasks[$todo['id']] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   // Menambah tugas
if (isset($_POST['add_task'])) {
    $task = trim($_POST['task']);
    $description = trim($_POST['description']);
    $deadline = $_POST['deadline'];
    $priority = $_POST['priority']; 
    $user_id = $_SESSION['user_id'];

    // Cek apakah semua kolom diisi
    if (empty($task) || empty($description) || empty($deadline) || empty($priority)) {
        $_SESSION['error'] = "Harap isi semua kolom!";
        header('Location: todo.php');
        exit;
    }

    // Validasi deadline agar tidak bisa memasukkan tanggal kemarin
    $today = date('Y-m-d');
    if ($deadline < $today) {
        $_SESSION['error'] = "Tanggal deadline tidak boleh di masa lalu!";
        header('Location: todo.php');
        exit;
    }

    // Validasi prioritas (harus 'Penting', 'Sedang', atau 'Biasa')
    $allowed_priorities = ['Penting', 'Sedang', 'Biasa'];
    if (!in_array($priority, $allowed_priorities)) {
        $_SESSION['error'] = "Prioritas tidak valid!";
        header('Location: todo.php');
        exit;
    }

    // Simpan ke database menggunakan prepared statement
    $stmt = $conn->prepare("INSERT INTO tasks (user_id, task, description, deadline, priority) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('issss', $user_id, $task, $description, $deadline, $priority); // 's' untuk string

    if ($stmt->execute()) {
        $_SESSION['success'] = "Tugas berhasil ditambahkan!";
    } else {
        $_SESSION['error'] = "Gagal menambahkan tugas!";
    }

    header('Location: todo.php');
    exit;
}


    // Menghapus tugas dan subtugas terkait
    if (isset($_POST['delete_task'])) {
        $task_id = $_POST['task_id'];

        // Hapus subtugas terlebih dahulu
        $stmt = $conn->prepare("DELETE FROM subtasks WHERE task_id = ?");
        $stmt->bind_param('i', $task_id);
        $stmt->execute();

        // Hapus tugas utama
        $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
        $stmt->bind_param('i', $task_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo "Tugas berhasil dihapus.";
        } else {
            echo "Gagal menghapus, ID tidak ditemukan atau error.";
        }

        // Refresh halaman agar tugas yang dihapus hilang dari tampilan
        header("Location: todo.php");
        exit;
    }

    // Menghapus subtugas
    if (isset($_POST['delete_subtask'])) {
        $stmt = $conn->prepare("DELETE FROM subtasks WHERE id = ?");
        $stmt->bind_param('i', $_POST['subtask_id']);
        $stmt->execute();
        header('Location: todo.php');
        exit;
    }

    // Mengedit tugas
    if (isset($_POST['edit_task'])) {
        $task_id = $_POST['task_id'];
        $task_name = $_POST['task'];
        $description = $_POST['description'];
        $priority = $_POST['priority'];

        if (!empty($task_name) && !empty($description) && !empty($priority)) {
            $stmt = $conn->prepare("UPDATE tasks SET task = ?, description = ?, priority = ? WHERE id = ?");
            $stmt->bind_param('sssi', $task_name, $description, $priority, $task_id);
            $stmt->execute();
            header('Location: todo.php');
            exit;
        } else {
            echo "Harap isi semua kolom!";
        }
    }

    // Menandai tugas selesai
    if (isset($_POST['complete_task'])) {
        $task_id = $_POST['task_id'];

        // Update tugas
        $stmt = $conn->prepare("UPDATE tasks SET completed = 1 WHERE id = ?");
        $stmt->bind_param('i', $task_id);
        $stmt->execute();

        // Update subtugas
        $stmt = $conn->prepare("UPDATE subtasks SET completed = 1 WHERE task_id = ?");
        $stmt->bind_param('i', $task_id);
        $stmt->execute();

        header('Location: todo.php');
        exit;
    }

    // Menandai subtugas selesai
    if (isset($_POST['complete_subtask'])) {
        $subtask_id = $_POST['subtask_id'];
        $stmt = $conn->prepare("UPDATE subtasks SET completed = 1 WHERE id = ?");
        $stmt->bind_param('i', $subtask_id);
        $stmt->execute();
        header('Location: todo.php');
        exit;
    }

    // Menambah subtugas
    if (isset($_POST['add_subtask'])) {
        $task_id = $_POST['task_id'];
        $subtask = $_POST['subtask'] ?? null;

        if (!empty($subtask)) {
            $stmt = $conn->prepare("INSERT INTO subtasks (task_id, subtask) VALUES (?, ?)");
            $stmt->bind_param('is', $task_id, $subtask);
            $stmt->execute();
            header('Location: todo.php');
            exit;
        } else {
            echo "Subtugas tidak boleh kosong!";
        }
    }
    if (isset($_POST['edit_subtask'])) {
        $subtask_id = $_POST['subtask_id'];
        $subtask_name = $_POST['subtask_name'];
        $stmt = $conn->prepare("UPDATE subtasks SET subtask = ? WHERE id = ?");
        $stmt->bind_param('si', $subtask_name, $subtask_id);
        $stmt->execute();
        header("Location: todo.php");
        exit;
    }
    
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Tugas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
   body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
            /* background-image: url("bulan.png"); */
            background-repeat: no-repeat;
            background-size: cover;
        }
        .container {
            max-width: 960px;
            margin-top: 30px;
        }
        .card {
        border: 1px solid #ddd; 
        border-radius: 8px;
        margin-bottom: 20px;
        background-color: #fff;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.51);
        padding: 20px; /* Perbesar padding dalam kartu */
    }
        .card-body {
            padding: 15px;
        }
        
        .card-title {
        font-weight: bold;
        font-size: 1.5rem;  /* Ukuran lebih besar agar jelas */
        margin-bottom: 10px;
        }
        .card-text {
        font-size: 1rem;
        color: #555;
        margin-bottom: 10px; /* Menambahkan sedikit jarak antara teks */
    }
    .statistik-sekolah .card-text {
    font-size: 1.5rem; /* Perbesar ukuran teks */
    font-weight: bold;
}

    .priority-label {
    padding: 5px 15px;
    font-size: 0.875rem;
    border-radius: 15px;
    font-weight: bold;
    text-transform: uppercase;
    }
        .badge-important { background-color: #f44336; color: white; }
        .badge-medium { background-color: #ff9800; color: white; }
        .badge-normal { background-color: #292929; color: white; }
        .btn {
            border-radius: 5px;
            font-weight: bold;
        }
        .btn-primary { background-color: #007bff; }
        .btn-danger { background-color: #dc3545; }
        .btn-warning { background-color: #ffc107; }
        .btn-success { background-color: #28a745; }
        .btn-sm {
            padding: 5px 10px;
        }
        .form-control {
            border-radius: 5px;
            padding: 10px;
            font-size: 0.875rem;
        }
        .task-actions button {
            margin-right: 5px;
        }
        .subtask-list {
            margin-top: 10px;
            padding-left: 20px;
        }
        .subtask {
            margin-top: 15px; /* Memberi jarak antara tugas dan subtugas */
            padding: 10px;
           background-color: #f9f9f9; /* Memberi background lebih ringan pada subtugas */
          border-left: 4px solidrgb(255, 0, 0); /* Menambahkan garis kiri untuk menandakan subtugas */
            }
        .subtask-item {
         display: flex;
        align-items: center;
        justify-content: space-between;
      margin-bottom: 10px;
        }
     .subtask-item label {
    margin-left: 10px;
    font-size: 1rem; /* Ukuran font sedikit lebih besar agar lebih jelas */
}     
.subtask-actions {
    display: flex;
    gap: 5px;
}

.subtask-actions button {
    padding: 5px 10px;
    font-size: 0.875rem;
}

    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="#">Daftar Tugas</a>
        <div class="d-flex">
            <span class="text-white"><?= $current_date ?></span>
            <a href="logout.php" class="btn btn-danger ms-3">
                <i class="bi bi-box-arrow-right"></i> Keluar
            </a>
        </div>
    </div>
</nav>
    <div class="container my-5">
        <h1 class="text-center mb-4">Daftar Tugas</h1>

        <!-- Form Pencarian -->
        <form method="GET" class="mb-4">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="Cari tugas...">
        </form>
        <!-- Button untuk menambah tugas -->
        <button class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#addTaskModal">
            <i class="bi bi-plus-circle"></i> Tambah Tugas
        </button>
        <h3 class="text-center mb-4">Statistik Tugas Sekolah</h3>
<div class="row g-3 mb-4 statistik-sekolah">
    <div class="col-md-4">
        <div class="card border-primary shadow-sm">
            <div class="card-header">📚 Total Tugas</div>
            <div class="card-body">
                <p class="card-text"> <?= $data_sekolah['total_tasks'] ?> tugas</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-success shadow-sm">
            <div class="card-header">✅ Tugas Selesai</div>
            <div class="card-body">
                <p class="card-text"> <?= $data_sekolah['completed_tasks'] ?> tugas selesai</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-danger shadow-sm">
            <div class="card-header">⏳ Tugas Terlambat</div>
            <div class="card-body">
                <p class="card-text"> <?= $data_sekolah['overdue_tasks'] ?> tugas terlambat</p>
            </div>
        </div>
    </div>
</div>
        <label for="sort" class="form-label">Sortir Berdasarkan:</label>
        <select id="sort" class="form-select" onchange="location = this.value;">
            <option value="todo.php?sort_by=">Pilih</option>
            <option value="todo.php?sort_by=completed">Selesai</option>
            <option value="todo.php?sort_by=deadline">Tenggat Waktu</option>
            <option value="todo.php?sort_by=priority">Prioritas</option> 
        </select>

<!-- Daftar Tugas -->
<div class="row g-3 mt-4">
    <?php if ($todos): ?>
        <?php foreach ($todos as $todo): ?>
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($todo['task']) ?></h5>
                        <p class="card-text"><?= htmlspecialchars($todo['description']) ?></p>
                        <p class="card-text"><strong>Tenggat Waktu:</strong> <?= htmlspecialchars($todo['deadline']) ?></p>
                        <p class="badge <?= $todo['priority'] == 'Penting' ? 'badge-important' : ($todo['priority'] == 'Sedang' ? 'badge-medium' : 'badge-normal') ?>">
                          <?= $todo['priority'] == 'Penting' ? 'Penting' : ($todo['priority'] == 'Sedang' ? 'Sedang' : 'Biasa') ?>
                        </p>

                        <!-- Checkbox untuk menyelesaikan tugas -->
                        <form method="POST" class="d-inline-block">
                            <input type="hidden" name="task_id" value="<?= $todo['id'] ?>">
                            <input type="checkbox" name="complete_task" value="1" 
                                   <?= $todo['completed'] ? 'checked disabled' : '' ?>
                                   onchange="this.form.submit();">
                            <label>Selesaikan</label>
                        </form>

                        <!-- Button untuk menghapus tugas -->
                        <button type="button" class="btn btn-danger btn-sm delete-task-btn" 
        data-bs-toggle="modal" 
        data-bs-target="#deleteModal"
        data-task_id="<?= $todo['id'] ?>">
    <i class="bi bi-trash"></i> Hapus
</button>

                        <!-- Button untuk mengedit tugas (disembunyikan jika selesai) -->
                        <?php if (!$todo['completed']): ?>
                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editTaskModal" 
                                    data-task_id="<?= $todo['id'] ?>" data-task="<?= $todo['task'] ?>" 
                                    data-description="<?= $todo['description'] ?>" 
                                    data-deadline="<?= $todo['deadline'] ?>" 
                                    data-priority="<?= $todo['priority'] ?>">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                        <?php endif; ?>
<!-- Subtasks -->
<?php if (isset($subtasks[$todo['id']])): ?>
    <?php foreach ($subtasks[$todo['id']] as $subtask): ?>
        <div class="subtask">
            <div class="subtask-item">
                <span><?= htmlspecialchars($subtask['subtask']) ?></span>
                <div class="subtask-actions">
                    <!-- Tombol hanya muncul jika tugas belum selesai -->
                    <?php if (!$todo['completed']): ?>
                        <button class="btn btn-warning btn-sm edit-subtask-btn"
                                data-bs-toggle="modal"
                                data-bs-target="#editSubtaskModal"
                                data-subtask_id="<?= $subtask['id'] ?>"
                                data-subtask="<?= htmlspecialchars($subtask['subtask']) ?>">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                    <?php endif; ?>

                    <form method="POST" class="d-inline-block">
                        <input type="hidden" name="subtask_id" value="<?= $subtask['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm" name="delete_subtask">
                            <i class="bi bi-trash"></i> Hapus
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
<!-- Modal Edit Subtask -->
<div class="modal fade" id="editSubtaskModal" tabindex="-1" aria-labelledby="editSubtaskModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editSubtaskModalLabel">Edit Subtugas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="subtask_id" id="edit_subtask_id">
                    <div class="mb-3">
                        <label for="edit_subtask_name" class="form-label">Nama Subtugas</label>
                        <input type="text" class="form-control" id="edit_subtask_name" name="subtask_name" required>
                    </div>
                    <button type="submit" name="edit_subtask" class="btn btn-warning">Perbarui Subtugas</button>
                </form>
            </div>
        </div>
    </div>
</div>



                        <!-- Form untuk menambah subtugas -->
                        <form method="POST" class="d-inline-block">
                            <input type="hidden" name="task_id" value="<?= $todo['id'] ?>">
                            <input type="text" name="subtask" class="form-control form-control-sm" placeholder="Subtugas...">
                            <button type="submit" class="btn btn-success btn-sm mt-2" name="add_subtask">
                                <i class="bi bi-plus"></i> Tambah Subtugas
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="text-center">Tidak ada tugas yang ditemukan.</p>
    <?php endif; ?>
</div>
    </div>
   <!-- Modal untuk menambah tugas -->
<div class="modal fade" id="addTaskModal" tabindex="-1" aria-labelledby="addTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTaskModalLabel">Tambah Tugas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="task" class="form-label">Nama Tugas</label>
                        <input type="text" class="form-control" id="task" name="task" placeholder="Masukkan nama tugas" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="description" name="description" placeholder="Tambahkan deskripsi tugas"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="deadline" class="form-label">Tenggat Waktu</label>
                        <input type="datetime-local" class="form-control" id="deadline" name="deadline" required>
                    </div>
                    <div class="mb-3">
    <label for="priority" class="form-label">Prioritas</label>
    <select name="priority" id="priority" class="form-select" required>
        <option value="Penting">Penting</option>
        <option value="Sedang">Sedang</option>
        <option value="Biasa">Biasa</option>
    </select>
</div>

                    <button type="submit" name="add_task" id="addTaskBtn" class="btn btn-primary w-100">
                        <i class="bi bi-plus-circle"></i> Tambah Tugas
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Modal Konfirmasi Hapus -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus tugas ini?</p>
            </div>
            <div class="modal-footer">
                <form method="POST">
                    <input type="hidden" name="task_id" id="delete_task_id">
                    <button type="submit" name="delete_task" class="btn btn-danger">Hapus</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                </form>
            </div>
        </div>
    </div>
</div>


<!-- Modal Edit Tugas -->
<div class="modal fade" id="editTaskModal" tabindex="-1" aria-labelledby="editTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editTaskModalLabel">Edit Tugas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="task_id" id="edit_task_id">
                    <div class="mb-3">
                        <label for="edit_task" class="form-label">Nama Tugas</label>
                        <input type="text" class="form-control" id="edit_task" name="task" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="edit_description" name="description"></textarea>
                    </div>
                    <div class="mb-3">
                 <label for="edit_deadline" class="form-label">Tenggat Waktu</label>
               <input type="datetime-local" class="form-control" id="edit_deadline" name="deadline" readonly>
                </div>

                    <div class="mb-3">
                        <label for="edit_priority">Prioritas:</label>
                        <select id="edit_priority" name="priority" class="form-select">
                            <option value="Biasa">Biasa</option>
                            <option value="Sedang">Sedang</option>
                            <option value="Penting">Penting</option>
                        </select>
                    </div>
                    <button type="submit" name="edit_task" class="btn btn-warning">Perbarui Tugas</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    function setMinDeadline() {
        let now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset()); // Menyesuaikan zona waktu
        document.getElementById('deadline').min = now.toISOString().slice(0, 16);
    }

    // Set minimal deadline saat modal dibuka
    document.addEventListener('DOMContentLoaded', setMinDeadline);

    <!-- Modal Edit Tugas -->
    // Modal Edit Tugas
const editTaskModal = document.getElementById('editTaskModal');
editTaskModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const taskId = button.getAttribute('data-task_id');
    const taskName = button.getAttribute('data-task');
    const description = button.getAttribute('data-description');
    const deadline = button.getAttribute('data-deadline');
    const priority = button.getAttribute('data-priority');

    // Isi data modal edit tugas
    document.getElementById('edit_task_id').value = taskId;
    document.getElementById('edit_task').value = taskName;
    document.getElementById('edit_description').value = description;
    document.getElementById('edit_deadline').value = deadline;

    // Perbaikan prioritas agar tidak salah saat ditampilkan di modal edit
    const editPriority = document.getElementById('edit_priority');
    if (priority === "Penting" || priority === "Sedang" || priority === "Biasa") {
        editPriority.value = priority;
    } else {
        editPriority.value = "Biasa"; // Default jika nilai tidak valid
    }
});

    // Event Listener untuk Edit Subtask (dijalankan sekali saja)
    document.querySelectorAll('.edit-subtask-btn').forEach(button => {
        button.addEventListener('click', function () {
            const subtaskId = this.getAttribute('data-subtask_id');
            const subtaskName = this.getAttribute('data-subtask');

            // Isi data modal edit subtask
            document.getElementById('edit_subtask_id').value = subtaskId;
            document.getElementById('edit_subtask_name').value = subtaskName;
        });
    });

    // Modal Hapus Tugas
    const deleteModal = document.getElementById('deleteModal');
    deleteModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const taskId = button.getAttribute('data-task_id');
        document.getElementById('delete_task_id').value = taskId;
    });

</script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>