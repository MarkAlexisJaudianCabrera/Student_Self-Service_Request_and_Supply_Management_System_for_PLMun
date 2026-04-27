<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    </head>
    <body>
        <!-- Hamburger (3 stacked lines) Button -->
        <div class="hamburger" onclick="toggleNav()">
            <i class="fa-solid fa-bars"></i>
        </div>

        <!-- leftSidebar -->
        <div class="left-navbar" id="sidebar">
            <h3>===</h3>

            <?php
            if ($_SESSION['user_type'] === 'registrar') {
                echo '<a href="./registrar-home-page.php">Accept Request</a>';
                echo '<a href="./registrar-claim-page.php">Claim Request</a>';
            } elseif ($_SESSION['user_type'] === 'business') {
                echo '<a href="./business-center-home-page.php">Accept Request</a>';
                echo '<a href="./business-center-claim-page.php">Claim Request</a>';
            }
            elseif ($_SESSION['user_type'] === 'admin') {
                echo '<a href="./admin-home-page.php">Home Page</a>';
                echo '<a href="./items.php">Items</a>';
                echo '<a href="./requests.php">Requests</a>';
                echo '<a href="./users-staff.php">Users (Staff)</a>';
                echo '<a href="./users-student.php">Users (Student)</a>';
            } elseif ($_SESSION['user_type'] === 'cashier') {
                echo '<a href="./request-payment-page.php">Accept Payment</a>';
            }
            ?>
            <a href="/logout.php">Logout</a>
        </div>
        <script>
        function toggleNav() {
            document.getElementById("sidebar").classList.toggle("active");
        }
        </script>
    </body>
</html>