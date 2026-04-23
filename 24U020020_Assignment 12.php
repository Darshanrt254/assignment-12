<?php
session_start();

// Database configuration
$db_host = getenv("DB_HOST") ?: 'localhost';
$db_user = getenv("DB_USER") ?: 'root';
$db_pass = getenv("DB_PASS") ?: '';
$db_name = getenv("DB_NAME") ?: 'itc2026_ass10';
$db_port = getenv("DB_PORT") ?: '3306';

$conn = @new mysqli($db_host, $db_user, $db_pass, $db_name, (int)$db_port);
$db_connected = true;

if ($conn->connect_error) {
    $db_connected = false;
}

$message = '';
$message_type = '';

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

// Handle Form Submissions
if ($_SERVER["REQUEST_METHOD"] == "POST" && $db_connected) {
    if (isset($_POST['action']) && $_POST['action'] == 'register') {
        $fname = $conn->real_escape_string(trim($_POST['first_name']));
        $lname = $conn->real_escape_string(trim($_POST['last_name']));
        $email = $conn->real_escape_string(trim($_POST['email']));
        $pass  = trim($_POST['password']);
        $phone = $conn->real_escape_string(trim($_POST['contact_number']));
        $gender= $conn->real_escape_string(trim($_POST['gender']));
        $edu   = $conn->real_escape_string(trim($_POST['education']));
        $role  = $conn->real_escape_string(trim($_POST['role']));
        $state = $conn->real_escape_string(trim($_POST['state']));
        $city  = $conn->real_escape_string(trim($_POST['city']));

        if(empty($fname) || empty($email) || empty($pass) || empty($phone)) {
            $message = "Please fill all required fields.";
            $message_type = "error";
        } else {
            $check = $conn->query("SELECT id FROM users WHERE email = '$email'");
            if($check && $check->num_rows > 0) {
                $message = "Email is already registered. Please login.";
                $message_type = "error";
            } else {
                $hashed_pass = password_hash($pass, PASSWORD_DEFAULT);
                $sql = "INSERT INTO users (first_name, last_name, email, password, contact_number, gender, education, role, state, city) 
                        VALUES ('$fname', '$lname', '$email', '$hashed_pass', '$phone', '$gender', '$edu', '$role', '$state', '$city')";
                if($conn->query($sql) === TRUE) {
                    $message = "Registration successful! You can now login.";
                    $message_type = "success";
                } else {
                    $message = "Error: " . $conn->error;
                    $message_type = "error";
                }
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] == 'login') {
        $email = $conn->real_escape_string(trim($_POST['email']));
        $pass  = trim($_POST['password']);

        if(empty($email) || empty($pass)) {
            $message = "Email and password are required.";
            $message_type = "error";
        } else {
            $result = $conn->query("SELECT * FROM users WHERE email = '$email'");
            if($result && $result->num_rows == 1) {
                $user = $result->fetch_assoc();
                if(password_verify($pass, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['last_name'] = $user['last_name'];
                    $_SESSION['role'] = $user['role'];
                    
                    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
                    exit;
                } else {
                    $message = "Invalid password.";
                    $message_type = "error";
                }
            } else {
                $message = "User not found. Please register.";
                $message_type = "error";
            }
        }
    }
}

// Fetch Content from Database
$expectedMenu = [
    "Home", "Committee", "Important Dates", "Speakers", "Workshop",
    "Submission", "Special Session", "Registration", "Sponsorship", "Contact"
];
$contentRows = [];

if ($db_connected) {
    $res = @$conn->query("SELECT Menu, Content FROM conference_data");
    if ($res) {
        while($r = $res->fetch_assoc()) {
            $contentRows[$r['Menu']] = $r['Content'];
        }
    }
}

function getSectionId($menuText) {
    return strtolower(trim(str_replace(' ', '-', $menuText)));
}

?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignment 10 - PHP Registration and Login</title>
    <!-- Tailwind CSS for styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root { --nav-height: 70px; }

        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            scroll-padding-top: var(--nav-height);
            background-color: #f8fafc;
        }

        nav { height: var(--nav-height); }

        /* Generated Content General Styling inside Sections */
        .content-box {
            background: white; padding: 2rem; border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            width: 100%; max-width: 1000px; margin: 0 auto;
        }
        
        .styled-list { list-style-type: square; padding-left: 2rem; margin-bottom: 1rem; }
        .styled-list li { margin-bottom: 0.5rem; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: 1rem; border: 1px solid #cbd5e1; text-align: left; }
        th { background-color: #1e3a8a; color: white; }
        
        .grid-cards {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem; width: 100%; max-width: 1000px; margin: 0 auto;
        }
        
        .card {
            background: white; padding: 1.5rem; border-radius: 8px;
            text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .card h3 { color: #1e3a8a; margin-bottom: 0.5rem; }
        
        .btn-primary {
            padding: 0.75rem 1.5rem; background-color: #1e3a8a; color: white; 
            border: none; border-radius: 5px; font-size: 1.1rem; cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .btn-primary:hover { background-color: #1e40af; }

        section {
            min-height: 100vh; padding: 4rem 1.5rem; display: flex; flex-direction: column;
            justify-content: center; align-items: center;
        }
        section h2 {
            font-size: 2.5rem; margin-bottom: 2rem; color: #1e3a8a;
            text-transform: uppercase; border-bottom: 3px solid #3b82f6;
            padding-bottom: 0.5rem; text-align: center;
        }

        /* Color classes for sections */
        .bg-section-0 { background-color: #f0f9ff; }
        .bg-section-1 { background-color: #e0f2fe; }
        .bg-section-2 { background-color: #f1f5f9; }
        .bg-section-3 { background-color: #e2e8f0; }
        .bg-section-4 { background-color: #f8fafc; }
        .bg-section-5 { background-color: #f0fdfa; }
        .bg-section-6 { background-color: #fdf2f8; }
        .bg-section-7 { background-color: #eff6ff; }
        .bg-section-8 { background-color: #fefce8; }
        .bg-section-9 { background-color: #1e293b; color: white !important; }
        .bg-section-9 h2 { color: white !important; border-bottom-color: white !important; }
        .bg-section-9 .content-box { background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.2); }
        .bg-section-9 table td, .bg-section-9 table th { border-color: rgba(255,255,255,0.2); }

        /* Modal styling */
        .modal {
            display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; 
            overflow: auto; background-color: rgba(0,0,0,0.5); backdrop-filter: blur(4px);
        }
        .modal-content {
            background-color: #fefefe; margin: 5% auto; padding: 2rem; border: 1px solid #888;
            width: 90%; max-width: 500px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            position: relative;
            color: #333;
        }
        .close-btn {
            color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;
        }
        .close-btn:hover, .close-btn:focus { color: black; text-decoration: none; }
        
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: #1e3a8a; }
        .form-group input, .form-group select {
            width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 4px;
        }
        
        .tab-container { display: flex; border-bottom: 2px solid #e2e8f0; margin-bottom: 1.5rem; }
        .tab { padding: 0.75rem 1.5rem; cursor: pointer; font-weight: 600; color: #64748b; }
        .tab.active { color: #1e3a8a; border-bottom: 2px solid #1e3a8a; margin-bottom: -2px; }
    </style>
</head>
<body>

    <!-- Fixed Navigation Menu -->
    <nav class="fixed top-0 left-0 w-full bg-blue-900 shadow-md z-[1000] flex justify-between items-center px-6">
        <div class="text-white text-2xl font-bold tracking-wider">ITC 2026</div>
        
        <!-- Desktop Menu -->
        <ul class="hidden xl:flex gap-1 items-center h-full m-0 p-0 text-white font-medium text-[15px]" id="desktop-menu">
            <?php foreach($expectedMenu as $item): ?>
                <li><a href="#<?php echo getSectionId($item); ?>" class="nav-link block px-3 py-2 hover:text-yellow-400 transition"><?php echo htmlspecialchars($item); ?></a></li>
            <?php endforeach; ?>
            <li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="?logout=1" class="block px-3 py-2 hover:text-yellow-400 transition bg-red-600 rounded ml-2" title="Logged in as <?php echo htmlspecialchars($_SESSION['first_name']); ?>">Logout</a>
                <?php else: ?>
                    <a href="javascript:void(0)" onclick="openModal()" class="block px-3 py-2 hover:text-yellow-400 transition bg-green-600 rounded ml-2">Login / Register</a>
                <?php endif; ?>
            </li>
        </ul>

        <!-- Mobile Menu Button -->
        <button id="mobile-toggle" class="xl:hidden text-white focus:outline-none">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
        </button>
    </nav>

    <!-- Mobile Dropdown Menu -->
    <div id="mobile-menu" class="hidden flex-col bg-blue-900 w-full fixed top-[70px] left-0 z-50 shadow-lg text-white font-medium">
        <?php foreach($expectedMenu as $item): ?>
            <a href="#<?php echo getSectionId($item); ?>" class="mobile-nav-link block px-6 py-3 border-b border-blue-800 hover:bg-blue-800 transition"><?php echo htmlspecialchars($item); ?></a>
        <?php endforeach; ?>
        <?php if(isset($_SESSION['user_id'])): ?>
            <a href="?logout=1" class="block px-6 py-3 bg-red-700 hover:bg-red-600 transition">Logout (<?php echo htmlspecialchars($_SESSION['first_name']); ?>)</a>
        <?php else: ?>
            <a href="javascript:void(0)" onclick="openModal(); document.getElementById('mobile-menu').classList.add('hidden');" class="block px-6 py-3 bg-green-700 hover:bg-green-600 transition">Login / Register</a>
        <?php endif; ?>
    </div>

    <!-- Alert Messages -->
    <?php if($message): ?>
    <div id="alert-toast" class="fixed top-[80px] left-1/2 transform -translate-x-1/2 z-[1500] px-6 py-3 rounded shadow-lg text-white font-bold <?php echo $message_type == 'success' ? 'bg-green-500' : 'bg-red-500'; ?>">
        <?php echo htmlspecialchars($message); ?>
        <button onclick="document.getElementById('alert-toast').style.display='none'" class="ml-4">&times;</button>
    </div>
    <?php endif; ?>

    <?php if(!$db_connected): ?>
    <div class="pt-[100px] px-6 text-center text-red-600 font-bold bg-white p-4 shadow mb-4">
        Warning: Database connection failed. Please ensure MySQL is running and the database '<?php echo $db_name; ?>' is created from the provided SQL file.
    </div>
    <?php endif; ?>

    <!-- Dynamic Container for Generated Sections -->
    <main id="content-container" class="pt-[30px]">
        <?php foreach($expectedMenu as $index => $item): 
            $sectionId = getSectionId($item);
            $bgClass = 'bg-section-' . ($index % 10);
            $content = isset($contentRows[$item]) ? $contentRows[$item] : '';
        ?>
            <section id="<?php echo $sectionId; ?>" class="<?php echo $bgClass; ?> w-full transition-all">
                <?php if($content): ?>
                    <?php echo $content; ?>
                <?php else: ?>
                    <h2><?php echo htmlspecialchars($item); ?></h2>
                    <div class="content-box text-black">
                        <p class="text-red-500 font-semibold text-center">Data for this section is managed via the MySQL database.</p>
                    </div>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    </main>

    <!-- Modal for Login / Register -->
    <div id="auth-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <div class="tab-container">
                <div class="tab active" id="tab-login" onclick="switchTab('login')">Login</div>
                <div class="tab" id="tab-register" onclick="switchTab('register')">Register</div>
            </div>

            <!-- Login Form -->
            <form id="form-login" method="POST" action="">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label>Email ID</label>
                    <input type="email" name="email" required placeholder="Enter your email">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="Enter password">
                </div>
                <button type="submit" class="btn-primary w-full mt-4">Login</button>
            </form>

            <!-- Register Form -->
            <form id="form-register" method="POST" action="" style="display:none; max-height: 60vh; overflow-y: auto; padding-right: 10px;">
                <input type="hidden" name="action" value="register">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" name="last_name" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Email (Login ID)</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label>Contact Number</label>
                        <input type="tel" name="contact_number" required>
                    </div>
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="gender" required>
                            <option value="">Select</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Education Qualification</label>
                    <select name="education" required>
                        <option value="">Select</option>
                        <option value="Undergraduate">Undergraduate</option>
                        <option value="Postgraduate">Postgraduate</option>
                        <option value="PhD">PhD</option>
                        <option value="Diploma">Diploma</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" required>
                        <option value="">Select</option>
                        <option value="Student">Student</option>
                        <option value="Faculty">Faculty</option>
                        <option value="Industry">Industry Professional</option>
                    </select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label>State</label>
                        <input type="text" name="state" required>
                    </div>
                    <div class="form-group">
                        <label>City/Place</label>
                        <input type="text" name="city" required>
                    </div>
                </div>
                <button type="submit" class="btn-primary w-full mt-4">Register</button>
            </form>
        </div>
    </div>

    <!-- Script for UI behavior -->
    <script>
        // Modal functions
        const modal = document.getElementById('auth-modal');
        function openModal() { modal.style.display = 'block'; document.body.style.overflow = 'hidden'; }
        function closeModal() { modal.style.display = 'none'; document.body.style.overflow = 'auto'; }
        
        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }

        function switchTab(tab) {
            if(tab === 'login') {
                document.getElementById('tab-login').classList.add('active');
                document.getElementById('tab-register').classList.remove('active');
                document.getElementById('form-login').style.display = 'block';
                document.getElementById('form-register').style.display = 'none';
            } else {
                document.getElementById('tab-register').classList.add('active');
                document.getElementById('tab-login').classList.remove('active');
                document.getElementById('form-register').style.display = 'block';
                document.getElementById('form-login').style.display = 'none';
            }
        }

        <?php if($message && $message_type == 'error'): ?>
            // If there's an error message, slightly helpful to keep modal open on the relevant tab
            // For now, modal is closed by default and user sees the toast, which is clean.
        <?php endif; ?>

        document.addEventListener('DOMContentLoaded', () => {
            // Setup Mobile Menu Toggle
            const mobileToggle = document.getElementById('mobile-toggle');
            const mobileMenu = document.getElementById('mobile-menu');
            const mobileNavLinks = document.querySelectorAll('.mobile-nav-link');

            mobileToggle.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
                mobileMenu.classList.toggle('flex');
            });

            mobileNavLinks.forEach(link => {
                link.addEventListener('click', () => {
                    mobileMenu.classList.add('hidden');
                    mobileMenu.classList.remove('flex');
                });
            });

            // ScrollSpy Setup for dynamic highlighting of menu elements
            const sections = document.querySelectorAll("section");
            const desktopNavLinks = document.querySelectorAll("#desktop-menu .nav-link");
            const mobileLinks = document.querySelectorAll("#mobile-menu .mobile-nav-link");

            function onScroll() {
                let currentId = "";
                const scrollPos = window.scrollY + 150; 

                sections.forEach(section => {
                    const top = section.offsetTop;
                    const height = section.offsetHeight;
                    if (scrollPos >= top && scrollPos < top + height) {
                        currentId = section.getAttribute("id");
                    }
                });

                desktopNavLinks.forEach(link => {
                    link.classList.remove("text-yellow-400", "font-bold");
                    if (link.getAttribute("href") === `#${currentId}`) {
                        link.classList.add("text-yellow-400", "font-bold");
                    }
                });
                
                mobileLinks.forEach(link => {
                    link.classList.remove("text-yellow-400", "bg-blue-800");
                    if (link.getAttribute("href") === `#${currentId}`) {
                        link.classList.add("text-yellow-400", "bg-blue-800");
                    }
                });
            }

            window.addEventListener("scroll", onScroll);
            onScroll();
            
            // To auto dismiss alerts after a few seconds
            const alertToast = document.getElementById('alert-toast');
            if(alertToast) {
                setTimeout(() => {
                    alertToast.style.transition = "opacity 0.5s ease";
                    alertToast.style.opacity = "0";
                    setTimeout(() => alertToast.remove(), 500);
                }, 4000);
            }
        });
    </script>
</body>
</html>
