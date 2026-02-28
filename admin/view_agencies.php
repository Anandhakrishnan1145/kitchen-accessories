<?php
session_start();
require_once '../config.php';

// Delete agency
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Get image path before deleting
    $stmt = $conn->prepare("SELECT profile_image FROM agencies WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $image_path = $row['profile_image'];
        
        // Delete the image file if it's not the default image
        if ($image_path != "../uploads/agency_profiles/default-agency.png" && file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    // Delete the record
    $stmt = $conn->prepare("DELETE FROM agencies WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $delete_success = "Agency deleted successfully";
    } else {
        $delete_error = "Error deleting agency: " . $conn->error;
    }
}

// Fetch all agencies
$sql = "SELECT * FROM agencies ORDER BY id DESC";
$result = $conn->query($sql);

// Initialize counters for display purposes
$total_agencies = $result->num_rows;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Agencies</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 40px auto;
        }
        .table-container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 20px;
            overflow-x: auto;
        }
        .page-title {
            margin-bottom: 30px;
            color: #333;
            font-weight: 600;
        }
        .profile-img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        .agency-actions {
            display: flex;
            gap: 10px;
        }
        .btn-view {
            background-color: #17a2b8;
            color: white;
        }
        .btn-edit {
            background-color: #ffc107;
            color: white;
        }
        .btn-delete {
            background-color: #dc3545;
            color: white;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        .btn-action {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: all 0.3s;
        }
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .stats-container {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            flex: 1;
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #0d6efd;
            margin-bottom: 5px;
        }
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .add-new-btn {
            margin-bottom: 20px;
        }
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .pagination {
            margin-top: 20px;
            justify-content: center;
        }
        .modal-header, .modal-footer {
            border: none;
        }
        .modal-body {
            padding: 30px;
        }
        .empty-state {
            text-align: center;
            padding: 50px;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #dee2e6;
        }
        .page-subtitle {
            color: #6c757d;
            margin-bottom: 30px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="page-title">Agency Management</h2>
                <p class="page-subtitle">View, edit and manage all registered agencies</p>
            </div>
            <a href="agency_registration.php" class="btn btn-primary add-new-btn">
                <i class="fas fa-plus me-2"></i> Add New Agency
            </a>
        </div>
        
        <?php if(isset($delete_success)): ?>
            <div class="alert alert-success"><?php echo $delete_success; ?></div>
        <?php endif; ?>
        
        <?php if(isset($delete_error)): ?>
            <div class="alert alert-danger"><?php echo $delete_error; ?></div>
        <?php endif; ?>
        
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_agencies; ?></div>
                <div class="stat-label">Total Agencies</div>
            </div>
        </div>
        
        <div class="table-container">
            <?php if ($result->num_rows > 0): ?>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Profile</th>
                            <th>Agency Name</th>
                            <th>Agent Name</th>
                            <th>Agency Code</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td>
                                    <img src="<?php echo htmlspecialchars($row['profile_image']); ?>" alt="Agency Profile" class="profile-img">
                                </td>
                                <td><?php echo htmlspecialchars($row['agency_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['agent_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['agency_code']); ?></td>
                                <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td>
                                    <div class="agency-actions">
                                        <a href="view_agency.php?id=<?php echo $row['id']; ?>" class="btn-action btn-view" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit_agency.php?id=<?php echo $row['id']; ?>" class="btn-action btn-edit" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="#" class="btn-action btn-delete" title="Delete" onclick="confirmDelete(<?php echo $row['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-building"></i>
                    <h4>No Agencies Found</h4>
                    <p>There are no agencies registered in the system yet.</p>
                    <a href="agency_registration.php" class="btn btn-primary mt-3">Register New Agency</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this agency? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Delete</a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(id) {
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            document.getElementById('confirmDeleteBtn').href = '?delete=' + id;
            deleteModal.show();
        }
    </script>
</body>
</html>