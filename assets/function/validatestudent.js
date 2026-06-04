function validateStudent(event) {
    event.preventDefault();

    const btn = document.getElementById("checkBtn");

    // para hindi magspam ng check button
    btn.disabled = true;
    btn.innerText = "Checking...";

    let student_no = document.getElementById("student_no").value.trim();
    let instiemail = document.getElementById("instiemail").value.trim();

    // Email validation - check if email ends with @plmun.edu.ph
    if (!instiemail.endsWith('@plmun.edu.ph')) {
        Swal.fire({ 
            title: "Invalid Email", 
            text: "Please use your PLMUN email address (@plmun.edu.ph)", 
            icon: "warning",
            confirmButtonText: "OK"
        });
        btn.disabled = false;
        btn.innerText = "Check Validity";
        return false;
    }

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

        if (data.success == true) {
            document.getElementById("fnresult").value = data.fullname;
            document.getElementById("courseresult").value = data.course;
            proceedBtn.classList.add("show");
            
            // Optional: Show success message
            Swal.fire({ 
                title: "Success!", 
                text: "Student validated successfully", 
                icon: "success",
                timer: 1500,
                showConfirmButton: false
            });
        } else {
            document.getElementById("fnresult").value = "";
            document.getElementById("courseresult").value = "";
            proceedBtn.classList.remove("show");
            Swal.fire({ 
                title: "Invalid Student", 
                text: data.error || "Please check your details and try again.", 
                icon: "error",
                confirmButtonText: "Retry" 
            });
        }
    })
    .catch(error => {
        console.error("Fetch error:", error);
        Swal.fire({ 
            title: "Network Error", 
            text: "Please check your connection and try again.", 
            icon: "error",
            confirmButtonText: "OK" 
        });
    })
    .finally(() => {
        // para if hindi masubmit pwede isubmit ulit
        btn.disabled = false;
        btn.innerText = "Check Validity";
    });

    return false;
}

document.addEventListener("DOMContentLoaded", () => {
    const proceedBtn = document.getElementById("proceedBtn");
    if (proceedBtn) {
        proceedBtn.addEventListener("click", () => {
            window.location.href = "student-request-form-select-items.php";
        });
    }
});