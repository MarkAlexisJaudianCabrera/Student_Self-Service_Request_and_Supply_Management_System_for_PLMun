<?php
    session_start();

    if (!isset($_SESSION['validated']) || $_SESSION['validated'] !== true) {
        header("Location: /404.php");
        exit();
    }

    include('../config/db.php');

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $result = $conn->query("SELECT * FROM itemtb");
?>
<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Request Items - Student Self-Service Request and Supply Management System for PLMUN</title>
        <link rel="stylesheet" href="/assets/styles/allstyles.css">
        <link rel="stylesheet" href="/assets/styles/selectitems.css">
        <link rel="stylesheet" href="/assets/styles/navbar.css">
        <link rel="icon" href="/assets/ico/logo16ico.ico" >
        <link rel="icon" href="/assets/ico/logo32ico.ico" >
        <link rel="icon" href="/assets/ico/logo96ico.ico" >
        <link rel="icon" href="/assets/ico/logo192ico.ico">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    </head>
    <body>
        <nav class="navbar">
            <a href="/landingpage.html"><img src="/assets/img/schl_logo-1.png" alt="Logo"></a>
        </nav>
        <br>
        <div class="selectitems-container">
            <div class="title">
                <h3>Self-Service Request | Request Items</h3>
            </div>
            <div class="subtitle">
                <p>Select the items you would like to request.</p>
                <br>
            </div>
            <br>
            <div id="selected-items"></div>
            <br>
            <form class="item-grid">
                <?php while ($row = $result->fetch_assoc()) : ?>
                    <button class="item-btn"
                        data-id="<?= $row['itemtbID']; ?>"
                        data-name="<?= htmlspecialchars($row['name']); ?>"
                        data-price="<?= $row['price']; ?>"
                        data-itemrole="<?= htmlspecialchars($row['category']); ?>"
                    >
                        <div class="item-header">
                            <div class="text">
                                <h4><?= htmlspecialchars($row['name']); ?></h4>
                                <p><?= htmlspecialchars($row['description']); ?></p>
                            </div>
                            <p><?= htmlspecialchars("P" . number_format($row['price'], 2)); ?></p>
                            <i class="fa-solid fa-square-plus"></i>
                        </div>
                    </button>
                <?php endwhile; ?>
            </form>
            <label for="" id="temp-items-list-label">Requested Items:</label>
            <div id="temp-items-list"></div>
            <button id="request-all-btn">Request All Item(s)</button>
        </div>
        <script>
        document.getElementById("request-all-btn").onclick = function () {
            const container = document.getElementById("temp-items-list");

            if (container.children.length === 0) {
                alert("No items selected.");
                return;
            }

            window.location.href = "student-request-form-summary.php";
        };

        function loadTempItems() {
            fetch("../load_temp_items.php")
            .then(res => res.text())
            .then(data => {
                document.getElementById("temp-items-list").innerHTML = data;
                attachDeleteEvents(); // 👈 IMPORTANT
            });
        }

        function attachDeleteEvents() {
            document.querySelectorAll(".delete-btn").forEach(btn => {
                btn.onclick = function () {
                    const id = this.dataset.id;

                    fetch("../delete_temp_item.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                        body: `id=${id}`
                    })
                    .then(res => res.text())
                    .then(data => {
                        if (data.trim() === "success") {
                            loadTempItems(); // refresh list
                        }
                    });
                };
            });
        }

        document.addEventListener("DOMContentLoaded", function() {
            loadTempItems(); // load on page start

            document.querySelectorAll(".item-btn").forEach(button => {
                button.addEventListener("click", function(e) {
                    e.preventDefault();

                    const id = this.dataset.id;
                    const name = this.dataset.name;

                    const container = document.getElementById("selected-items");
                    container.innerHTML = "";

                    const item = document.createElement("div");
                    item.classList.add("selected-item");

                    item.innerHTML = `
                        <span class="item-name">${name}</span>
                        <div class="qty-control">
                            <p></p>
                            <i class="fa-solid fa-minus minus"></i>
                            <span class="qty">1</span>
                            <i class="fa-solid fa-plus plus"></i>
                        </div>
                        <button class="ok-btn">OK</button>
                    `;

                    container.appendChild(item);

                    const qtySpan = item.querySelector(".qty");

                    item.querySelector(".plus").onclick = () => {
                        qtySpan.textContent = parseInt(qtySpan.textContent) + 1;
                    };

                    item.querySelector(".minus").onclick = () => {
                        let val = parseInt(qtySpan.textContent);
                        if (val > 1) qtySpan.textContent = val - 1;
                    };

                    item.querySelector(".ok-btn").onclick = () => {
                        const qty = parseInt(qtySpan.textContent);

                        fetch("../add_temp_item.php", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/x-www-form-urlencoded"
                            },
                            body: `itemtbID=${id}&qty=${qty}`
                        })
                        .then(res => res.text())
                        .then(data => {
                            if (data.trim() === "success") {
                                item.style.background = "#2e7d32";
                                loadTempItems();
                            } else {
                                console.log(data); 
                                alert("Error saving item");
                            }
                        });
                    };
                });
            });
        });
        // auto load on page open
        document.addEventListener("DOMContentLoaded", loadTempItems);
        </script>
    </body>
</html>