<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: signup.php?error=" . urlencode("Please login first."));
    exit;
}

$fullname = $_SESSION['fullname'] ?? 'User';
$initials = strtoupper(substr($fullname, 0, 1));
if (empty($initials)) $initials = 'U';

// ⭐ REQUIRED
require_once __DIR__ . "/Backend/db_connect.php";

// ⭐ Get wishlist categories
$user = $_SESSION['user_id'];

$wish_sql = "
    SELECT DISTINCT courses.category 
    FROM wishlist 
    JOIN courses ON wishlist.course_id = courses.id
    WHERE wishlist.user_id = ?
";

$stmt = $conn->prepare($wish_sql);
$stmt->bind_param("i", $user);
$stmt->execute();
$res = $stmt->get_result();

$wish_categories = [];
while ($row = $res->fetch_assoc()) {
    $wish_categories[] = $row['category'];
}
$stmt->close();

// ⭐ Load popular courses based on wishlist
if (!empty($wish_categories)) {

    $placeholders = implode(",", array_fill(0, count($wish_categories), "?"));
    $in_types = str_repeat("s", count($wish_categories));

    $sql = "SELECT * FROM courses WHERE category IN ($placeholders) ORDER BY id DESC LIMIT 8";
    $stmt2 = $conn->prepare($sql);

    $stmt_params = [];
    $stmt_params[] = &$in_types;

    foreach ($wish_categories as $k => $cat) {
        $stmt_params[] = &$wish_categories[$k];
    }

    call_user_func_array([$stmt2, 'bind_param'], $stmt_params);

    $stmt2->execute();
    $popular_courses = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

} else {

    $popular_courses = [
        ["title" => "The Complete Web Development Bootcamp", "image" => "images/course-web-dev.jpg", "instructor" => "Angela Yu", "price" => 499],
        ["title" => "Python for Data Science", "image" => "images/course-python.jpg", "instructor" => "Jose Portilla", "price" => 499]
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - eduflect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .navbar-logged-in .nav-icon { font-size: 1.2rem; color: #1c1d1f; }

        /* Avatar */
        .profile-avatar {
            width: 40px;
            height: 40px;
            background-color: #1c1d1f;
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            position: relative;
            cursor: pointer;
            transition: 0.2s ease;
            /* Ensure the avatar is aligned nicely with other nav elements */
            line-height: 1; /* Helps vertical alignment */
            font-size: 1.1rem; /* Make the initial a bit bigger */
        }
        .profile-avatar:hover { transform: scale(1.05); }

        .notification-dot {
            width: 10px;
            height: 10px;
            background-color: #5624d0;
            border: 2px solid #fff;
            border-radius: 50%;
            position: absolute;
            top: 0;
            right: 0;
        }

        /* Custom Dropdown */
        .profile-wrapper {
            /* Use padding for separation instead of margin on the avatar */
            position: relative;
            /* Added to vertically center the wrapper content (avatar) within the navbar */
            display: flex;
            align-items: center;
            padding: 0 0.5rem; /* Space from cart/bell icon */
        }

        .profile-dropdown {
            position: absolute;
            top: 50px; /* Adjusted from 50px if needed, but 50px is often fine */
            right: 0;
            width: 230px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            overflow: hidden;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.25s ease;
            z-index: 999;
        }
        .profile-dropdown.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        .profile-dropdown-header {
            background: #f8f9fa;
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }
        .profile-dropdown-header strong { display: block; font-size: 15px; color: #1c1d1f; }
        .profile-dropdown a {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            color: #1c1d1f;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.2s ease;
        }
        .profile-dropdown a:hover { background: #f3f3f3; }
        .profile-dropdown a i { width: 20px; color: #5624d0; margin-right: 10px; }
        .profile-dropdown .logout { color: #dc3545 !important; }
        .profile-dropdown hr { margin: 6px 0; border: none; border-top: 1px solid #eee; }
        .welcome-header { background-color: #1c1d1f; color: white; padding: 4rem 0; }
        
        /* 🔥 Alignment Fix for Search Bar 🔥 */
        .search-form-centered {
            /* Uses a combination of flex properties to center the search bar */
            flex-grow: 1; /* Allows it to take up space between left and right nav */
            display: flex;
            justify-content: center; /* Center the form content */
            padding-left: 1rem; /* Added padding to prevent crowding the Categories dropdown */
            padding-right: 1rem;
        }
        .search-form-centered .input-group {
            max-width: 550px; /* Optional: Sets a maximum width for the search bar */
            width: 100%;
        }

        /* Nav Links alignment (for links like 'My learning') */
        .navbar-nav .nav-link {
            display: flex;
            align-items: center; /* Vertically aligns text and icons */
            height: 100%; /* Ensures they take full height for alignment */
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top navbar-logged-in">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="index.php">eduflect</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">Categories</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">Development</a></li>
                            <li><a class="dropdown-item" href="#">Design</a></li>
                            <li><a class="dropdown-item" href="#">Marketing</a></li>
                        </ul>
                    </li>
                </ul>
                
                <form class="search-form-centered">
                    <div class="input-group">
                        <input type="search" class="form-control" placeholder="Search for anything" aria-label="Search">
                        <button class="btn btn-dark" type="button"><i class="fas fa-search"></i></button>
                    </div>
                </form>

                <div class="navbar-nav ms-auto align-items-center">
                    <a href="#" class="nav-link me-3 py-2">My learning</a> 
                    
                    <a href="wishlist.php" class="nav-link me-2 p-2"><i class="far fa-heart nav-icon"></i></a>
                    <a href="#" class="nav-link me-2 p-2"><i class="fas fa-shopping-cart nav-icon"></i></a>
                    <a href="#" class="nav-link me-2 p-2"><i class="far fa-bell nav-icon"></i></a>
                    
                    <div class="profile-wrapper">
                        <div class="profile-avatar" id="profileToggle">
                            <?= htmlspecialchars($initials) ?>
                            <span class="notification-dot"></span>
                        </div>
                        <div class="profile-dropdown" id="profileMenu">
                            <div class="profile-dropdown-header">
                                <strong><?= htmlspecialchars($fullname) ?></strong>
                                <small>Student</small>
                            </div>
                            <a href="edit_profile.php"><i class="fa-solid fa-user"></i> Edit Profile</a>
                            <a href="#"><i class="fa-solid fa-book"></i> My Learning</a>
                            <a href="wishlist.php"><i class="fa-solid fa-heart"></i> Wishlist</a>
                            <a href="#"><i class="fa-solid fa-bell"></i> Notifications</a>
                            <a href="#"><i class="fa-solid fa-gear"></i> Account Settings</a>
                            <a href="#"><i class="fa-solid fa-language"></i> Language</a>
                            <hr>
                            <a href="login.php" class="logout"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    <main>
        <header class="welcome-header">
            <div class="container">
                <h1 class="display-4 fw-bold">Welcome back, <?= htmlspecialchars($fullname) ?>!</h1>
                <p class="lead">Ready to jump back in? Let's start learning.</p>
            </div>
        </header>
       <section id="courses" class="py-5">
    <div class="container">
        <h2 class="text-center mb-4 fw-bold">Our Most Popular Courses</h2>
        <div class="row">

        <?php foreach ($popular_courses as $c): ?>

    <?php
    // compute correct image URL
    if (!empty($c['image']) && file_exists(__DIR__ . '/uploads/courses/' . $c['image'])) {
        $imgUrl = 'uploads/courses/' . htmlspecialchars($c['image']);
    } else {
        $imgUrl = 'images/course-placeholder.jpg';
    }

    // check wishlist
    $inWishlist = isset($wishlistIds[(int)$c['id']]);
    ?>

    <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
        <div class="card h-100">
            <img src="<?= $imgUrl ?>" class="card-img-top" alt="<?= htmlspecialchars($c['title']) ?>">

            <div class="card-body d-flex flex-column">

                <h5 class="fw-semibold"><?= htmlspecialchars($c['title']) ?></h5>
                <p class="text-muted small"><?= htmlspecialchars($c['instructor']) ?></p>
                <h5 class="fw-bold mt-auto">₹<?= number_format($c['price'], 2) ?></h5>

                <div class="d-flex gap-2 mt-3">

                    <a href="view_course.php?id=<?= $c['id'] ?>" 
                       class="btn btn-sm btn-outline-primary">
                        View
                    </a>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="wishlist_add.php?id=<?= $c['id'] ?>"
                           class="btn btn-sm <?= $inWishlist ? 'btn-danger' : 'btn-outline-danger' ?>">
                           <?= $inWishlist ? '♥ In Wishlist' : '♡ Wishlist' ?>
                        </a>
                    <?php else: ?>
                        <a href="login.php"
                           class="btn btn-sm btn-outline-danger">
                           ♡ Wishlist
                        </a>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>

<?php endforeach; ?>



        </div>
    </div>
</section>

        <section id="categories" class="py-5 bg-light"></section>
        <section id="testimonials" class="py-5"></section>
    </main>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const toggle = document.getElementById('profileToggle');
        const menu = document.getElementById('profileMenu');
        toggle.addEventListener('click', () => menu.classList.toggle('active'));
        window.addEventListener('click', (e) => {
            if (!toggle.contains(e.target) && !menu.contains(e.target))
                menu.classList.remove('active');
        });
    </script>
</body>
</html>