<!-- action_buttons.php -->
<div class="action-buttons">
    <button type="button" class="btn btn-primary btn-sm"
        data-bs-toggle="modal" data-bs-target="#viewModal<?= $row['id'] ?>">
        View
    </button>
    <!--<button type="button" class="btn btn-success btn-sm action-button"-->
    <!--    data-action="approved"-->
    <!--    data-inquiry-id="<?= htmlspecialchars($row['id']) ?>"-->
    <!--    <?= $row['status'] === 'approved' || $row['status'] === 'rejected' ? 'disabled' : '' ?>>-->
    <!--    Approve-->
    <!--</button>-->
    <!--<button type="button" class="btn btn-danger btn-sm action-button"-->
    <!--    data-action="rejected"-->
    <!--    data-inquiry-id="<?= htmlspecialchars($row['id']) ?>"-->
    <!--    <?= $row['status'] === 'approved' || $row['status'] === 'rejected' ? 'disabled' : '' ?>>-->
    <!--    Reject-->
    <!--</button>-->
</div>
