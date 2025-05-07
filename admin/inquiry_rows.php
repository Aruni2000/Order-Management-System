<tr data-inquiry-id="<?= htmlspecialchars($row['id']) ?>">
    <td>
        <?= htmlspecialchars($row['first_name']) . ' ' . htmlspecialchars($row['last_name']) ?>
        <span class="status-badge badge <?= $row['status'] === 'approved' ? 'bg-success' :
            ($row['status'] === 'rejected' ? 'bg-danger' : 'bg-warning') ?>">
            <?= htmlspecialchars($row['status'] ?: 'Pending') ?>
        </span>
    </td>
    <td><?= htmlspecialchars($row['email']) ?></td>
    <td class="message-cell">
        <div class="message-content" data-bs-toggle="tooltip"
            data-bs-placement="top" title="<?= htmlspecialchars($row['mesage']) ?>">
            <?= htmlspecialchars($row['mesage']) ?>
        </div>
    </td>
    <td><?= htmlspecialchars($row['company']) ?></td>
    <td><?= htmlspecialchars($row['created_at']) ?></td>
    <td>
        <?php
        $inquiry_id = htmlspecialchars($row['id']);
        $status = htmlspecialchars($row['status']);
        include 'action_buttons.php';
        ?>
    </td>
</tr>
