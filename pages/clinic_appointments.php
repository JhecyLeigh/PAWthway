<?php
// pages/clinic_appointments.php
session_start();
require_once __DIR__ . '/../config/db.php'; // expects $conn (mysqli)

if (!isset($_SESSION['clinic_id'])) {
    header('Location: clinic_login.php');
    exit;
}
$clinic_id = (int)$_SESSION['clinic_id'];
$clinic_name_display = htmlspecialchars($_SESSION['clinic_name'] ?? '');

// ---- POST: handle status update with strict server-side enforcement ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $appointment_id = isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : 0;
    $new_status = isset($_POST['status']) ? trim($_POST['status']) : '';

    // Allowed target statuses further checked below
    $allowed_targets = ['Confirmed', 'Completed', 'Cancelled'];

    if ($appointment_id > 0 && in_array($new_status, $allowed_targets, true)) {
        // fetch current status and clinic ownership to enforce transitions
        $check = $conn->prepare("SELECT status FROM appointments WHERE id = ? AND clinic_id = ?");
        if ($check) {
            $check->bind_param('ii', $appointment_id, $clinic_id);
            $check->execute();
            $result = $check->get_result();
            $row = $result ? $result->fetch_assoc() : null;
            $check->close();

            if ($row) {
                $current_status = $row['status'];
                $allow = false;

                // Allowed transitions:
                // Pending -> Confirmed
                // Confirmed -> Completed
                // Pending -> Cancelled
                if ($current_status === 'Pending' && $new_status === 'Confirmed') $allow = true;
                if ($current_status === 'Confirmed' && $new_status === 'Completed') $allow = true;
                if ($current_status === 'Pending' && $new_status === 'Cancelled') $allow = true;

                if ($allow) {
                    $upd = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ? AND clinic_id = ?");
                    if ($upd) {
                        $upd->bind_param('sii', $new_status, $appointment_id, $clinic_id);
                        $upd->execute();
                        if ($upd->affected_rows > 0) {
                            $_SESSION['flash'] = "Appointment status updated.";
                        } else {
                            $_SESSION['flash'] = "No change made or update failed.";
                        }
                        $upd->close();
                    } else {
                        error_log("DB prepare failed (update): " . $conn->error);
                        $_SESSION['flash'] = "Database error (update).";
                    }
                } else {
                    error_log("Blocked invalid transition for appointment {$appointment_id}: {$current_status} -> {$new_status}");
                    $_SESSION['flash'] = "Invalid status transition attempted.";
                }
            } else {
                error_log("Appointment not found or not owned by clinic: id={$appointment_id}, clinic={$clinic_id}");
                $_SESSION['flash'] = "Appointment not found or not owned by this clinic.";
            }
        } else {
            error_log("DB prepare failed (select check): " . $conn->error);
            $_SESSION['flash'] = "Database error (check).";
        }
    } else {
        $_SESSION['flash'] = "Invalid input.";
    }

    // Redirect back (PRG) so flash shows and prevents double submit
    header('Location: clinic_appointments.php');
    exit;
}

// Fetch appointments for this clinic
$sql = "
  SELECT a.id, a.pet_name, a.pet_type, a.service, a.appointment_date, a.status,
         u.username AS owner_name, u.email AS owner_email
  FROM appointments a
  LEFT JOIN users u ON a.user_id = u.id
  WHERE a.clinic_id = ?
  ORDER BY a.appointment_date DESC
";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("DB prepare error: " . htmlspecialchars($conn->error));
}
$stmt->bind_param('i', $clinic_id);
$stmt->execute();
$res = $stmt->get_result();
$appointments = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// fetch and clear flash (if any)
$flash = $_SESSION['flash'] ?? null;
if ($flash) unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Appointments — <?= $clinic_name_display ?></title>
  <link rel="stylesheet" href="/pawthway/assets/css/style.css">
  <link rel="stylesheet" href="/pawthway/assets/css/styles.css">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    .appointments-card{max-width:1100px;margin:30px auto;padding:22px;border-radius:12px;background:#fff;box-shadow:0 8px 24px rgba(0,0,0,.04)}
    table.app-table{width:100%;border-collapse:collapse}
    table.app-table th{background:#cfead2;color:#1b6c2b;padding:12px;text-align:left;border-bottom:1px solid #e9f2ea}
    table.app-table td{padding:12px;border-bottom:1px solid #f1f5f1;vertical-align:middle}
    .status-chip{padding:6px 10px;border-radius:20px;font-weight:600;color:#fff;display:inline-block;min-width:80px;text-align:center}
    .st-pending{background:#f39c12}
    .st-confirmed{background:#2ecc71}
    .st-completed{background:#3498db}
    .st-cancelled{background:#e74c3c}
    .status-select{padding:6px 10px;border-radius:6px;border:1px solid #c8e6c9;background:#fff;font-weight:600;min-width:140px}
    .status-select:disabled{background:#f4f4f4;color:#777;cursor:not-allowed}
    .status-form{display:inline-block;margin:0;padding:0}
    /* flash */
    .flash { max-width:1100px;margin:10px auto;padding:12px;border-radius:8px;background:#e6ffed;color:#114c27;border:1px solid #c6f1d1 }
    /* confirmation modal */
    .modal-backdrop { position:fixed; inset:0; background:rgba(0,0,0,0.45); display:none; align-items:center; justify-content:center; z-index:9999; }
    .modal { background:#fff; padding:18px; border-radius:10px; width:90%; max-width:420px; box-shadow:0 8px 30px rgba(0,0,0,0.2); text-align:left; }
    .modal h3 { margin:0 0 8px; color:#2e7d32; }
    .modal p { margin:8px 0 14px; color:#333; }
    .modal .controls { text-align:right; gap:8px; display:flex; justify-content:flex-end; }
    .btn { padding:8px 12px; border-radius:8px; border:none; cursor:pointer; font-weight:600; }
    .btn-confirm { background:#4CAF50; color:#fff; }
    .btn-danger { background:#e64b47; color:#fff; }
    .btn-secondary { background:#f4f4f4; color:#333; border:1px solid #ddd; }
    @media screen and (max-width:700px){ .appointments-card{padding:14px} .status-select{min-width:100px} }
  </style>
</head>
<body>
  <!-- header -->
  <header>
    <nav>
      <div class="logo">
        <a href="clinic_dashboard.php" style="display:flex;align-items:center;color:white;text-decoration:none">
          <img src="/pawthway/assets/img/logo.png" alt="PAWthway Logo" style="width:46px;margin-right:10px">
          <span style="font-weight:600;font-size:20px;color:white">PAWthway</span>
        </a>
      </div>
      <ul>
        <li><a href="clinic_dashboard.php">Home</a></li>
        <li><a href="clinic_logout.php">Logout</a></li>
      </ul>
    </nav>
  </header>

  <?php if ($flash): ?>
    <div class="flash"><?= htmlspecialchars($flash) ?></div>
  <?php endif; ?>

  <main>
    <div class="appointments-card content">
      <h2>Appointments for <?= $clinic_name_display ?></h2>
      <table class="app-table">
        <thead>
          <tr><th>Pet</th><th>Type</th><th>Service</th><th>Date & Time</th><th>Status</th><th>Action</th></tr>
        </thead>
        <tbody>
          <?php if (empty($appointments)): ?>
            <tr><td colspan="6" style="text-align:center;padding:30px;color:#6b8a6b">No appointments found.</td></tr>
          <?php else: foreach ($appointments as $a):
            $cls = 'st-pending';
            if ($a['status'] === 'Confirmed') $cls = 'st-confirmed';
            if ($a['status'] === 'Completed') $cls = 'st-completed';
            if ($a['status'] === 'Cancelled') $cls = 'st-cancelled';

            // UI options per current status:
            $current = $a['status'];
            $disabled = false;
            $options = [];
            if ($current === 'Pending') {
                $options[] = ['value'=>'Pending','label'=>'Pending','selected'=>true];
                $options[] = ['value'=>'Confirmed','label'=>'Confirm','selected'=>false];
                $options[] = ['value'=>'Cancelled','label'=>'Cancel','selected'=>false];
            } elseif ($current === 'Confirmed') {
                $options[] = ['value'=>'Confirmed','label'=>'Confirmed','selected'=>true];
                $options[] = ['value'=>'Completed','label'=>'Complete','selected'=>false];
            } else {
                $options[] = ['value'=>$current,'label'=>$current,'selected'=>true];
                $disabled = true;
            }
          ?>
            <tr id="row-<?= (int)$a['id'] ?>">
              <td style="min-width:180px;"><?= htmlspecialchars($a['pet_name']) ?><br/><small style="color:#6b8a6b"><?= htmlspecialchars($a['owner_name'] ?? '') ?></small></td>
              <td style="width:90px;"><?= htmlspecialchars($a['pet_type']) ?></td>
              <td><?= nl2br(htmlspecialchars($a['service'])) ?></td>
              <td style="white-space:nowrap;"><?= htmlspecialchars($a['appointment_date']) ?></td>
              <td style="width:130px;"><span class="status-chip <?= $cls ?>"><?= htmlspecialchars($a['status']) ?></span></td>
              <td style="width:180px;">
                <form method="POST" class="status-form">
                  <input type="hidden" name="update_status" value="1">
                  <input type="hidden" name="appointment_id" value="<?= (int)$a['id'] ?>">
                  <select name="status"
                          class="status-select"
                          data-current="<?= htmlspecialchars($current) ?>"
                          <?= $disabled ? 'disabled' : '' ?>
                          onchange="return handleStatusSelectChange(event)">
                    <?php foreach ($options as $opt): ?>
                      <option value="<?= htmlspecialchars($opt['value']) ?>" <?= $opt['selected'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($opt['label']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </form>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </main>

  <footer>
    <div style="max-width:1100px;margin:0 auto;padding:12px 20px;">
      &copy; <?= date('Y') ?> PAWthway. All Rights Reserved.
    </div>
  </footer>

  <!-- Modal markup (hidden by default) -->
  <div id="modal-backdrop" class="modal-backdrop" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="modal" role="document">
      <h3 id="modal-title">Confirm action</h3>
      <p id="modal-message">Are you sure?</p>
      <div class="controls">
        <button id="modal-cancel" class="btn btn-secondary">Cancel</button>
        <button id="modal-confirm" class="btn btn-confirm">Yes, proceed</button>
      </div>
    </div>
  </div>

  <script>
  // simple modal + handler
  (function(){
    var backdrop = document.getElementById('modal-backdrop');
    var titleEl = document.getElementById('modal-title');
    var msgEl = document.getElementById('modal-message');
    var btnConfirm = document.getElementById('modal-confirm');
    var btnCancel = document.getElementById('modal-cancel');

    var pendingForm = null;
    var pendingSelect = null;
    var pendingNewValue = null;
    var pendingOldValue = null;

    function showModal(message, form, select, newVal, oldVal){
      pendingForm = form;
      pendingSelect = select;
      pendingNewValue = newVal;
      pendingOldValue = oldVal;

      titleEl.textContent = 'Please confirm';
      msgEl.textContent = message;
      backdrop.style.display = 'flex';
      backdrop.setAttribute('aria-hidden','false');
      btnConfirm.focus();
    }

    function hideModal(){
      backdrop.style.display = 'none';
      backdrop.setAttribute('aria-hidden','true');
      pendingForm = null;
      pendingSelect = null;
      pendingNewValue = null;
      pendingOldValue = null;
    }

    btnCancel.addEventListener('click', function(e){
      e.preventDefault();
      if (pendingSelect) pendingSelect.value = pendingOldValue;
      hideModal();
    });

    btnConfirm.addEventListener('click', function(e){
      e.preventDefault();
      if (!pendingForm) { hideModal(); return; }
      pendingForm.submit();
      hideModal();
    });

    window.confirmActionModal = function(form, selectEl){
      var newVal = selectEl.value;
      var oldVal = selectEl.getAttribute('data-current') || (selectEl.querySelector('option[selected]') && selectEl.querySelector('option[selected]').value) || null;
      if (newVal === oldVal) return;

      var message = '';
      if (newVal === 'Confirmed') {
        message = 'Mark this appointment as CONFIRMED?';
      } else if (newVal === 'Completed') {
        message = 'Mark this appointment as COMPLETED?';
      } else if (newVal === 'Cancelled') {
        message = 'Cancel this appointment?';
      } else {
        form.submit();
        return;
      }

      showModal(message, form, selectEl, newVal, oldVal);
    };
  })();

  function handleStatusSelectChange(ev){
    var select = ev.target;
    var form = select.closest('form');
    window.confirmActionModal(form, select);
    return false;
  }
  </script>

</body>
</html>
