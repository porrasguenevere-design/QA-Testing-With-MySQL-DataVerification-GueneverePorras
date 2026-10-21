<?php
// ---------------------------------------------------------------
// Database connection (local MariaDB via mysqli)
// ---------------------------------------------------------------
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "birthday_wishnote";

mysqli_report(MYSQLI_REPORT_OFF); // handle errors manually
$conn = new mysqli($servername, $username, $password, $dbname);

$message = "";
$messageType = "";

if ($conn->connect_error) {
    $message = "Database connection failed. Please check that MariaDB is running.";
    $messageType = "error";
}

// ---------------------------------------------------------------
// Handle POST request
// ---------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && $messageType !== "error") {
    // Capture all four variables from the form
    $FirstName   = trim($_POST["FirstName"]   ?? "");
    $LastName    = trim($_POST["LastName"]    ?? "");
    $GovPosition = trim($_POST["GovPosition"] ?? "");
    $Location    = trim($_POST["Location"]    ?? "");

    // =============================================================
    // INTENTIONAL FLAW (for demonstration/debugging exercises):
    // The form collects four fields, and $Location is captured above,
    // but it is deliberately LEFT OUT of the INSERT statement below.
    //   - The Location column is missing from the SQL string.
    //   - There is no matching placeholder for it.
    //   - $Location is not passed to bind_param().
    // Result: only FirstName, LastName and GovPosition are saved;
    // whatever the user typed for Location is silently discarded.
    // =============================================================
    $sql = "INSERT INTO main_corrupt_politicians (FirstName, LastName, GovPosition) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        // Fallback error message if the query fails to prepare
        $message = "Error: the query could not be prepared. " . htmlspecialchars($conn->error);
        $messageType = "error";
    } else {
        // Flaw continues here: only three "s" types and three variables (no $Location)
        $stmt->bind_param("sss", $FirstName, $LastName, $GovPosition);

        if ($stmt->execute()) {
            $message = "Success! The wish note has been sent.";
            $messageType = "success";
        } else {
            $message = "Error: the wish note could not be saved. " . htmlspecialchars($stmt->error);
            $messageType = "error";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Kara David's Wish Note</title>
<style>
    :root {
        --blue: #1e6fd9;
        --blue-dark: #1859b0;
        --blue-light: #e8f1fc;
        --text: #1f2a37;
        --muted: #6b7684;
        --border: #cfd8e3;
        --bg: #eef0f3;
    }
    * { box-sizing: border-box; }
    body {
        margin: 0;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px 16px;
        background: var(--bg);
        font-family: "Segoe UI", system-ui, -apple-system, Roboto, Arial, sans-serif;
        color: var(--text);
    }
    .card {
        width: 100%;
        max-width: 480px;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 18px rgba(30, 60, 100, 0.12);
        overflow: hidden;
    }
    .card-header {
        background: var(--blue);
        color: #fff;
        padding: 22px 28px;
    }
    .card-header h2 { margin: 0; font-size: 1.4rem; font-weight: 600; }
    .card-body { padding: 26px 28px 30px; }
    .field { margin-bottom: 18px; }
    label { display: block; margin-bottom: 6px; font-size: 0.92rem; font-weight: 600; }
    label .opt { font-weight: 400; color: var(--muted); }
    input[type="text"] {
        width: 100%;
        padding: 11px 12px;
        font-size: 1rem;
        color: var(--text);
        border: 1px solid var(--border);
        border-radius: 4px;
        background: #fff;
        transition: border-color .15s, box-shadow .15s;
    }
    input[type="text"]:focus {
        outline: none;
        border-color: var(--blue);
        box-shadow: 0 0 0 3px rgba(30, 111, 217, 0.2);
    }
    .btn {
        font: inherit;
        font-weight: 600;
        padding: 11px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        transition: background .15s;
    }
    .btn:focus-visible { outline: 3px solid rgba(30, 111, 217, 0.4); outline-offset: 2px; }
    .btn-primary { background: var(--blue); color: #fff; width: 100%; font-size: 1rem; }
    .btn-primary:hover { background: var(--blue-dark); }
    .btn-secondary { background: #e3e8ee; color: var(--text); }
    .btn-secondary:hover { background: #d2d9e1; }
    .alert { padding: 13px 16px; border-radius: 4px; margin-bottom: 20px; font-size: 0.95rem; }
    .alert.success { background: #e6f6ec; color: #17653a; border-left: 4px solid #2e9f5c; }
    .alert.error { background: #fdeaea; color: #8f1d1d; border-left: 4px solid #d64545; }

    /* Modal */
    .overlay {
        position: fixed; inset: 0;
        display: none;
        align-items: center; justify-content: center;
        padding: 16px;
        background: rgba(20, 35, 55, 0.55);
        z-index: 100;
    }
    .overlay.open { display: flex; }
    .modal {
        width: 100%;
        max-width: 420px;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        overflow: hidden;
    }
    .modal h3 { margin: 0; padding: 18px 24px; background: var(--blue-light); color: var(--blue-dark); font-size: 1.15rem; }
    .modal dl { margin: 0; padding: 18px 24px 6px; }
    .modal dt { font-size: 0.8rem; color: var(--muted); margin-top: 12px; }
    .modal dd { margin: 2px 0 0; font-size: 1rem; font-weight: 600; word-break: break-word; }
    .modal-actions { display: flex; gap: 10px; justify-content: flex-end; padding: 18px 24px 22px; }
    @media (max-width: 420px) {
        .card-body { padding: 22px 18px 26px; }
        .modal-actions { flex-direction: column-reverse; }
        .modal-actions .btn { width: 100%; }
    }
</style>
</head>
<body>

<div class="card">
    <div class="card-header">
        <h2>Kara David's Wish Note</h2>
    </div>
    <div class="card-body">

        <?php if ($message !== ""): ?>
            <div class="alert <?php echo $messageType; ?>" role="alert"><?php echo $message; ?></div>
        <?php endif; ?>

        <form id="wishForm" method="POST" action="">
            <div class="field">
                <label for="FirstName">Target's First Name</label>
                <input type="text" id="FirstName" name="FirstName" maxlength="30" required>
            </div>
            <div class="field">
                <label for="LastName">Target's Last Name</label>
                <input type="text" id="LastName" name="LastName" maxlength="30" required>
            </div>
            <div class="field">
                <label for="GovPosition">Target's Government Position</label>
                <input type="text" id="GovPosition" name="GovPosition" maxlength="20" required>
            </div>
            <div class="field">
                <label for="Location">Target's Location <span class="opt">(optional)</span></label>
                <input type="text" id="Location" name="Location" maxlength="40">
            </div>
            <button type="submit" class="btn btn-primary">Send Wish Note</button>
        </form>
    </div>
</div>

<!-- Verification modal -->
<div class="overlay" id="verifyOverlay" role="dialog" aria-modal="true" aria-labelledby="verifyTitle">
    <div class="modal">
        <h3 id="verifyTitle">Verify Target Data</h3>
        <dl>
            <dt>First Name</dt><dd id="vFirstName"></dd>
            <dt>Last Name</dt><dd id="vLastName"></dd>
            <dt>Government Position</dt><dd id="vGovPosition"></dd>
            <dt>Location</dt><dd id="vLocation"></dd>
        </dl>
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" id="cancelBtn">Cancel</button>
            <button type="button" class="btn btn-primary" id="confirmBtn" style="width:auto">Confirm &amp; Send</button>
        </div>
    </div>
</div>

<script>
(function () {
    var form = document.getElementById("wishForm");
    var overlay = document.getElementById("verifyOverlay");

    function show(id, value, fallback) {
        // textContent keeps user input from being interpreted as HTML
        document.getElementById(id).textContent = value.trim() !== "" ? value : fallback;
    }

    // Intercept submission and show the verification modal
    form.addEventListener("submit", function (e) {
        e.preventDefault();
        show("vFirstName", form.FirstName.value, "—");
        show("vLastName", form.LastName.value, "—");
        show("vGovPosition", form.GovPosition.value, "—");
        show("vLocation", form.Location.value, "Not provided");
        overlay.classList.add("open");
        document.getElementById("confirmBtn").focus();
    });

    document.getElementById("cancelBtn").addEventListener("click", function () {
        overlay.classList.remove("open");
    });

    overlay.addEventListener("click", function (e) {
        if (e.target === overlay) overlay.classList.remove("open");
    });

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") overlay.classList.remove("open");
    });

    // form.submit() bypasses the submit event listener, so no loop occurs
    document.getElementById("confirmBtn").addEventListener("click", function () {
        form.submit();
    });
})();
</script>

</body>
</html>