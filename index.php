<?php
// index.php
session_start(); // ADD THIS
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <script>
(function(){
  try {
    var t = localStorage.getItem('eduflect_theme');
    if(t === 'dark') document.documentElement.classList.add('dark');
    else if(t === 'light') document.documentElement.classList.remove('dark');
    else if(window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
      document.documentElement.classList.add('dark');
    }
  } catch(e){}
})();
</script>

  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>eduflect | An E-learning Platform</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body>

  <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
    <div class="container-fluid">
      <a class="navbar-brand fw-bold" href="index.php">eduflect</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav">
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
              Courses
            </a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="courses.php">All Courses</a></li>
              <hr class="dropdown-divider">
              <li><a class="dropdown-item" href="courses.php?category=Development">Development</a></li>
              <li><a class="dropdown-item" href="courses.php?category=Design">Design</a></li>
              <li><a class="dropdown-item" href="courses.php?category=Marketing">Marketing</a></li>
              <li><a class="dropdown-item" href="courses.php?category=IT%20%26%20Software">IT & Software</a></li>
            </ul>
          </li>
        </ul>

        <form class="d-flex mx-auto w-50" action="courses.php" method="get">
          <div class="input-group">
            <input type="search" name="search" class="form-control" placeholder="Search for anything" aria-label="Search">
            <button class="btn btn-dark" type="submit">
              <i class="fas fa-search"></i>
            </button>
          </div>
        </form>

        <div class="navbar-nav ms-auto align-items-center">
          <button id="themeToggle" class="btn nav-icon-btn ms-2" title="Toggle theme">
          <i id="themeIcon" class="fas fa-moon"></i>
          </button>


          <div class="dropdown me-2">
            <button class="btn btn-outline-dark dropdown-toggle" type="button" id="languageDropdown" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="fas fa-globe"></i> <span id="selected-lang">English</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="languageDropdown">
              <li><a class="dropdown-item lang-option" data-lang="en" href="#">English</a></li>
              <li><a class="dropdown-item lang-option" data-lang="hi" href="#">हिन्दी</a></li>
              <li><a class="dropdown-item lang-option" data-lang="fr" href="#">Français</a></li>
            </ul>
          </div>

          <?php
// Decide dashboard link based on role
$dashboardLink = "dashboard.php"; // default = student dashboard

if (!empty($_SESSION['role'])) {
    $role = strtolower($_SESSION['role']);

    if ($role === "admin") {
        $dashboardLink = "admin/dashboard.php";
    } elseif ($role === "student") {
        $dashboardLink = "dashboard.php";
    }
}
?>

<?php if (isset($_SESSION['user_id'])): ?>

    <a href="<?= $dashboardLink ?>" class="btn btn-outline-dark me-2">
        Hi, <?= htmlspecialchars($_SESSION['fullname']) ?>
    </a>

    <a href="logout.php" class="btn btn-danger">Logout</a>

<?php else: ?>

    <a href="login.php" class="btn btn-outline-dark me-2">Log In</a>
    <a href="signup.php" class="btn btn-dark">Sign Up</a>

<?php endif; ?>


        </div>
      </div>
    </div>
  </nav>

  <main>
    <header class="hero-section">
      <div class="container">
        <div class="hero-content p-4 shadow-lg">
          <h1 class="display-5 fw-bold">New skills, new future</h1>
          <p class="lead">Start learning from the world's best instructors. Find the right course for you.</p>
          <form class="input-group input-group-lg" action="courses.php" method="get">
            <input type="text" name="search" class="form-control" placeholder="What do you want to learn?">
            <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
          </form>
        </div>
      </div>
    </header>

    <section id="courses" class="py-5">
      <div class="container">
        <h2 class="text-center mb-4 fw-bold">Our Most Popular Courses</h2>
        <div class="row">
          <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100 course-card">
              <img src="images/course-web-dev.jpg" class="card-img-top" alt="Course Image">
              <div class="card-body d-flex flex-column">
                <h5 class="card-title fw-bold">The Complete Web Development Bootcamp</h5>
                <p class="card-text text-muted small">Angela Yu</p>
                <h5 class="mt-auto fw-bold">₹499 <small class="text-muted text-decoration-line-through">₹3,199</small></h5>
              </div>
            </div>
          </div>

          <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100 course-card">
              <img src="images/course-python.jpg" class="card-img-top" alt="Course Image">
              <div class="card-body d-flex flex-column">
                <h5 class="card-title fw-bold">Python for Data Science</h5>
                <p class="card-text text-muted small">Jose Portilla</p>
                <h5 class="mt-auto fw-bold">₹499 <small class="text-muted text-decoration-line-through">₹3,199</small></h5>
              </div>
            </div>
          </div>

          <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100 course-card">
              <img src="images/course-Digital.jpg" class="card-img-top" alt="Course Image">
              <div class="card-body d-flex flex-column">
                <h5 class="card-title fw-bold">Digital Marketing Masterclass</h5>
                <p class="card-text text-muted small">Phil Ebiner</p>
                <h5 class="mt-auto fw-bold">₹499 <small class="text-muted text-decoration-line-through">₹3,199</small></h5>
              </div>
            </div>
          </div>

          <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100 course-card">
              <img src="images/course-graphicDesign.jpg" class="card-img-top" alt="Course Image">
              <div class="card-body d-flex flex-column">
                <h5 class="card-title fw-bold">Graphic Design Masterclass</h5>
                <p class="card-text text-muted small">Lindsay Marsh</p>
                <h5 class="mt-auto fw-bold">₹499 <small class="text-muted text-decoration-line-through">₹3,199</small></h5>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section id="categories" class="py-5 bg-light">
      <div class="container">
        <h2 class="text-center mb-4 fw-bold">Popular Categories</h2>
        <div class="row g-4 text-center">
          <div class="col-lg-3 col-md-6">
            <a href="courses.php?category=Design" class="category-box p-4 fw-bold d-block text-dark bg-white shadow-sm rounded">Design</a>
          </div>
          <div class="col-lg-3 col-md-6">
            <a href="courses.php?category=Development" class="category-box p-4 fw-bold d-block text-dark bg-white shadow-sm rounded">Development</a>
          </div>
          <div class="col-lg-3 col-md-6">
            <a href="courses.php?category=Marketing" class="category-box p-4 fw-bold d-block text-dark bg-white shadow-sm rounded">Marketing</a>
          </div>
          <div class="col-lg-3 col-md-6">
            <a href="courses.php?category=IT%20%26%20Software" class="category-box p-4 fw-bold d-block text-dark bg-white shadow-sm rounded">IT & Software</a>
          </div>
        </div>
      </div>
    </section>

    <section id="testimonials" class="py-5">
      <div class="container">
        <h2 class="text-center mb-4 fw-bold">What Our Students Say</h2>
        <div class="row">
          <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
              <div class="card-body">
                <blockquote class="blockquote mb-0">
                  <p>"This platform helped me switch my career. The courses are excellent and up-to-date."</p>
                  <footer class="blockquote-footer d-flex align-items-center">
                    <img src="https://i.pravatar.cc/50?img=1" class="rounded-circle me-2" alt="Student">
                    Priya Sharma, Web Developer
                  </footer>
                </blockquote>
              </div>
            </div>
          </div>
          <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
              <div class="card-body">
                <blockquote class="blockquote mb-0">
                  <p>"I learned Python from scratch here. The instructor was amazing and explained everything clearly."</p>
                  <footer class="blockquote-footer d-flex align-items-center">
                    <img src="https://i.pravatar.cc/50?img=2" class="rounded-circle me-2" alt="Student">
                    Rohan Verma, Data Analyst
                  </footer>
                </blockquote>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
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
          <p><a href="#" class="text-white">About Us</a></p>
          <p><a href="#" class="text-white">Careers</a></p>
          <p><a href="#" class="text-white">Help and Support</a></p>
        </div>
        <div class="col-md-3 mx-auto mt-3">
          <h5 class="text-uppercase mb-4 fw-bold">Contact</h5>
          <p><i class="fas fa-home me-3"></i> New Delhi, India</p>
          <p><i class="fas fa-envelope me-3"></i> info@eduflect.com</p>
          <p><i class="fas fa-phone me-3"></i> +91 7091987466</p>
        </div>
      </div>
      <hr class="mb-4">
      <div class="text-center">
        <p>© 2025 eduflect, Inc. All rights reserved.</p>
        <div class="social-links mt-2">
          <a href="#" class="text-white me-3"><i class="fab fa-facebook fa-lg"></i></a>
          <a href="#" class="text-white me-3"><i class="fab fa-twitter fa-lg"></i></a>
          <a href="#" class="text-white"><i class="fab fa-linkedin-in fa-lg"></i></a>
        </div>
      </div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
// DARK MODE TOGGLE BUTTON JS
document.getElementById("themeToggle").addEventListener("click", function () {
  document.documentElement.classList.toggle("dark");

  if (document.documentElement.classList.contains("dark")) {
      localStorage.setItem("eduflect_theme", "dark");
      document.getElementById("themeIcon").className = "fas fa-sun";
  } else {
      localStorage.setItem("eduflect_theme", "light");
      document.getElementById("themeIcon").className = "fas fa-moon";
  }
});

// On page load - set correct icon
if (document.documentElement.classList.contains("dark")) {
  document.getElementById("themeIcon").className = "fas fa-sun";
} else {
  document.getElementById("themeIcon").className = "fas fa-moon";
}


// LANGUAGE SELECTOR JS
document.querySelectorAll('.lang-option').forEach(item => {
    item.addEventListener('click', function(e) {
        e.preventDefault();
        const selectedText = this.textContent;
        // Update the display text
        document.getElementById('selected-lang').textContent = selectedText;
        
        // In a real application, you would add logic here 
        // to change the actual content language using PHP/server-side code.
    });
});
</script>
<script src="script.js"></script>


</body>
</html>