function validateStudent(event) {
    event.preventDefault();

    const btn = document.getElementById("checkBtn");

    // 🔄 LOADING STATE
    btn.disabled = true;
    btn.innerText = "Checking...";

    let student_no = document.getElementById("student_no").value.trim();
    let instiemail = document.getElementById("instiemail").value.trim();

    fetch("../validate_student.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: `student_no=${encodeURIComponent(student_no)}&instiemail=${encodeURIComponent(instiemail)}`
    })
    .then(response => response.json())
    .then(data => {
        const proceedBtn = document.getElementById("proceedBtn");

        if (data.success==true) {
            document.getElementById("fnresult").value = data.fullname;
            document.getElementById("courseresult").value = data.course;
            proceedBtn.classList.add("show");

        } else {
            document.getElementById("fnresult").value = "";
            document.getElementById("courseresult").value = "";
            proceedBtn.classList.remove("show");
            Swal.fire({ title: "Invalid Student", text: "Please check your details and try again.", confirmButtonText: "Retry" });
        }
    })
    .catch(error => {
        console.error("Fetch error:", error);
    })
    .finally(() => {
        // 🔙 RESTORE BUTTON
        btn.disabled = false;
        btn.innerText = "Check Validity";
    });

    return false;
}

document.addEventListener("DOMContentLoaded", () => {
    document.getElementById("proceedBtn").addEventListener("click", () => {
        window.location.href = "student-request-form-select-items.php";
    });
});