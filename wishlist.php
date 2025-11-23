<?php
session_start();
require_once __DIR__ . "/Backend/db_connect.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT c.id, c.title, c.image, c.price, c.instructor 
    FROM wishlist w 
    JOIN courses c ON w.course_id = c.id
    WHERE w.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$items = $res->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
<title>My Wishlist - eduflect</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>

<div class="container py-5">
    <h2 class="mb-4">My Wishlist ❤️</h2>

    <div class="row g-4">
        <?php if (empty($items)): ?>
            <div class="text-muted">Your wishlist is empty.</div>
        <?php else: ?>
            <?php foreach ($items as $c): ?>
                <div class="col-md-3">
                    <div class="card shadow-sm">
                        <img src="uploads/courses/<?= htmlspecialchars($c['image']) ?>" class="card-img-top">
                        <div class="card-body">
                            <h5><?= htmlspecialchars($c['title']) ?></h5>
                            <p class="text-muted"><?= htmlspecialchars($c['instructor']) ?></p>
                            <p class="fw-bold">₹<?= number_format($c['price'], 2) ?></p>

                            <a href="view_course.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-primary">View</a>
                            <a href="wishlist_remove.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-danger">Remove</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif ?>
    </div>
</div>
<footer class="bg-dark text-white pt-5 pb-4">
        <div class="container text-center text-md-start">
            <div class="row">
                <div class="col-md-3 mx-auto mt-3">
                    <h5 class="text-uppercase mb-4 fw-bold">eduflect</h5>
                    <p>Learn on your schedule. Anytime, anywhere.</p>
                </div>
                <div class="col-md-2 mx-auto mt-3">
                    <h5 class="text-uppercase mb-4 fw-bold">Links</h5>
                    <p><a href="#" class="text-white text-decoration-none">About Us</a></p>
                    <p><a href="#" class="text-white text-decoration-none">Careers</a></p>
                    <p><a href="#" class="text-white text-decoration-none">Help and Support</a></p>
                </div>
                <div class="col-md-3 mx-auto mt-3">
                    <h5 class="text-uppercase mb-4 fw-bold">Contact</h5>
                    <p><i class="fas fa-home me-3"></i> New Delhi, India</p>
                    <p><i class="fas fa-envelope me-3"></i> info@eduflect.com</p>
                    <p><i class="fas fa-phone me-3"></i> +91 7091987466</p>
                </div>
            </div>
            <hr class="my-4">
            <div class="row align-items-center">
                <div class="col-md-7 col-lg-8">
                    <p class="mb-0">© 2025 eduflect, Inc. All rights reserved.</p>
                </div>
                <div class="col-md-5 col-lg-4">
                    <div class="text-center text-md-end">
                        <a href="#" class="text-white me-3 fs-5"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white me-3 fs-5"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white fs-5"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>
