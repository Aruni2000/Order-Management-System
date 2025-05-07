<!-- View Modal -->
<div class="modal fade" id="viewModal<?= $row['id'] ?>" tabindex="-1"
                                            aria-hidden="true">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Inquiry Details</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                            aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row mb-3">
                                                            <div class="col-md-6">
                                                                <p><strong>Name:</strong>
                                                                    <?= htmlspecialchars($row['first_name']) . ' ' . htmlspecialchars($row['last_name']) ?>
                                                                </p>
                                                                <p><strong>Email:</strong>
                                                                    <?= htmlspecialchars($row['email']) ?></p>
                                                                <p><strong>Company:</strong>
                                                                    <?= htmlspecialchars($row['company']) ?></p>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <p><strong>Created At:</strong>
                                                                    <?= htmlspecialchars($row['created_at']) ?></p>
                                                                <p><strong>Status:</strong>
                                                                    <span class="badge <?= $row['status'] === 'approved' ? 'bg-success' :
                                                                        ($row['status'] === 'rejected' ? 'bg-danger' : 'bg-warning') ?>">
                                                                        <?= htmlspecialchars($row['status']) ?: 'Pending' ?>
                                                                    </span>
                                                                </p>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-12">
                                                                <p><strong>Message:</strong></p>
                                                                <div class="p-3 bg-light rounded">
                                                                    <?= nl2br(htmlspecialchars($row['mesage'])) ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary"
                                                            data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>