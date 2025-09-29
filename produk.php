<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Produk</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }
        .action-buttons {
            white-space: nowrap;
        }
        .table-responsive {
            min-height: 400px;
        }
        #productTable {
            font-size: 0.9rem;
        }
        @media (max-width: 768px) {
            #productTable {
                font-size: 0.8rem;
            }
            .btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 0.7rem;
            }
        }
        .navbar-brand {
            font-weight: bold;
        }
        .placeholder-image {
            width: 60px;
            height: 60px;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
        }
        .modal-footer .btn-delete {
            margin-right: auto;
        }
        .image-preview-container {
            margin-top: 10px;
            text-align: center;
        }
        .image-preview {
            max-width: 100%;
            max-height: 150px;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 4px;
            /* display: none; */
        }
        .current-image-container {
            margin-top: 10px;
            text-align: center;
        }
        .current-image-label {
            font-size: 0.9rem;
            margin-bottom: 5px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <!-- Navigation Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.html">Sistem Manajemen</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="produk.php">Produk</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="gudang.php">Gudang</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Daftar Produk</h2>
            <button class="btn btn-success" onclick="addNewProduct()">
                <i class="fas fa-plus"></i> Tambah Produk
            </button>
        </div>

        <!-- Product Table -->
        <div class="table-responsive">
            <table class="table table-striped table-bordered table-hover" id="productTable">
                <thead class="table-dark">
                    <tr>
                        <th>Aksi</th>
                        <th>Gambar</th>
                        <th>Kode</th>
                        <th>Nama</th>
                        <th>Satuan</th>
                        <th>Harga</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Database connection
                    $servername = "localhost";
                    $username = "root";
                    $password = "";
                    $dbname = "sales_db";
                    
                    // Create connection
                    $conn = new mysqli($servername, $username, $password, $dbname);
                    
                    // Check connection
                    if ($conn->connect_error) {
                        die("Connection failed: " . $conn->connect_error);
                    }

                    // Create img_produk directory if it doesn't exist
                    if (!file_exists('img_produk')) {
                        mkdir('img_produk', 0777, true);
                    }
                    
                    // Handle delete action via POST to avoid URL parameters
                    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_id'])) {
                        $delete_id = $_POST['delete_id'];
                        // Get image path before deleting
                        $sql = "SELECT image FROM produk WHERE kode='$delete_id'";
                        $result = $conn->query($sql);
                        if ($result->num_rows > 0) {
                            $row = $result->fetch_assoc();
                            $image_path = $row['image'];
                            
                            // Delete image file if exists
                            if (!empty($image_path) && file_exists($image_path)) {
                                unlink($image_path);
                            }
                        }
  
                        $sql = "DELETE FROM produk WHERE kode='$delete_id'";
                        if ($conn->query($sql) === TRUE) {
                            echo "<div class='alert alert-success'>Produk berhasil dihapus</div>";
                        } else {
                            echo "<div class='alert alert-danger'>Error: " . $sql . "<br>" . $conn->error . "</div>";
                        }
                    }
                    
                    // Handle form submission
                    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['kode'])) {
                        $kode = $_POST['kode'];
                        $nama = $_POST['nama'];
                        $satuan = $_POST['satuan'];
                        $harga = $_POST['harga'];
                        $edit_id = $_POST['edit_id'];
						$current_image = $_POST['current_image'] ?? '';

                        // Handle image upload
                        $image_path = $current_image;
                        if (isset($_FILES['productImage']) && $_FILES['productImage']['error'] == UPLOAD_ERR_OK) {
                            $file = $_FILES['productImage'];
                            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                            $file_name = uniqid() . '_' . time() . '.' . $file_extension;
                            $upload_path = 'img_produk/' . $file_name;
                            
                            // Check if file is an image
                            $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                            if (in_array(strtolower($file_extension), $allowed_types)) {
                                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                                    $image_path = $upload_path;
                                    
                                    // Delete old image if exists and we're updating
                                    if (!empty($current_image) && file_exists($current_image)) {
                                        unlink($current_image);
                                    }
                                } else {
                                    echo "<div class='alert alert-danger'>Gagal mengupload gambar.</div>";
                                }
                            } else {
                                echo "<div class='alert alert-danger'>Hanya file gambar yang diizinkan (JPG, JPEG, PNG, GIF, WEBP).</div>";
                            }
                        }

                        if (!empty($edit_id)) {
                            // Update existing product
                            $sql = "UPDATE produk SET kode='$kode', nama='$nama', satuan='$satuan', harga='$harga', image='$image_path' WHERE kode='$edit_id'";
                            
                            if ($conn->query($sql) === TRUE) {
                                echo "<div class='alert alert-success'>Produk berhasil diperbarui</div>";
                            } else {
                                echo "<div class='alert alert-danger'>Error: " . $sql . "<br>" . $conn->error . "</div>";
                            }
                        } else {
                            // Insert new product
                            $sql = "INSERT INTO produk (kode, nama, satuan, harga, image) VALUES ('$kode', '$nama', '$satuan', '$harga', '$image_path')";
                            
                            if ($conn->query($sql) === TRUE) {
                                echo "<div class='alert alert-success'>Produk berhasil ditambahkan</div>";
                            } else {
                                echo "<div class='alert alert-danger'>Error: " . $sql . "<br>" . $conn->error . "</div>";
                            }
                        }
                    }
                    
                    // Fetch products from database
                    $sql = "SELECT * FROM produk";
                    $result = $conn->query($sql);
                    
                    if ($result->num_rows > 0) {
                        // Output data of each row
                        while($row = $result->fetch_assoc()) {
                            $image_display = '';
                            if (!empty($row['image']) && file_exists($row['image'])) {
                                $image_display = '<img src="' . $row['image'] . '" class="product-image" alt="' . $row['nama'] . '">';
                            } else {
                                $image_display = '<div class="placeholder-image"><i class="fas fa-image text-secondary"></i></div>';
                            }

                            echo "<tr>
                                <td class='action-buttons'>
                                    <button class='btn btn-sm btn-warning me-1' onclick='editProduct(\"" . $row['kode'] . "\", \"" . $row['nama'] . "\", \"" . $row['satuan'] . "\", " . $row['harga'] . ", \"" . $row['image'] . "\")'>
                                        <i class='fas fa-edit'></i> Edit
                                    </button>
                                    <!-- <button class='btn btn-sm btn-danger' onclick='showDeleteModal(\"" . $row['kode'] . "\", \"" . $row['nama'] . "\")'>
                                        <i class='fas fa-trash'></i> Hapus
                                    </button> -->
                                </td>
                                <td>
                                     $image_display
                                </td>
                                <td>" . $row['kode'] . "</td>
                                <td>" . $row['nama'] . "</td>
                                <td>" . $row['satuan'] . "</td>
                                <td>Rp " . number_format($row['harga'], 0, ',', '.') . "</td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' class='text-center'>Tidak ada data produk</td></tr>";
                    }
                    
                    $conn->close();
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Tambah Produk Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="productForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" id="editId" name="edit_id" value="">
						<input type="hidden" id="currentImage" name="current_image" value="">
                        <div class="mb-3">
                            <label for="productImage" class="form-label">Gambar Produk</label>
                            <input class="form-control" type="file" id="productImage" name="productImage" accept="image/*" onchange="previewImage(this)">
                            <div class="form-text">Format yang didukung: JPG, JPEG, PNG, GIF, WEBP (Maks. 2MB)</div>

                            <div class="image-preview-container">
                                <img id="imagePreview" class="image-preview" src="" alt="Preview Gambar">
                            </div>
                            
                            <div id="currentImageContainer" class="current-image-container" style="display: none;">
                                <div class="current-image-label">Gambar saat ini:</div>
                                <img id="currentImagePreview" class="image-preview" src="" alt="Gambar Saat Ini">
                            </div>

                        </div>
                        <div class="mb-3">
                            <label for="productCode" class="form-label">Kode Produk <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="productCode" name="kode" required>
                        </div>
                        <div class="mb-3">
                            <label for="productName" class="form-label">Nama Produk <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="productName" name="nama" required>
                        </div>
                        <div class="mb-3">
                            <label for="productUnit" class="form-label">Satuan <span class="text-danger">*</span></label>
                            <select class="form-select" id="productUnit" name="satuan" required>
                                <option value="">Pilih Satuan</option>
                                <option value="Pes">Pes</option>
                                <option value="Unit">Unit</option>
                                <option value="Box">Box</option>
                                <option value="Pack">Pack</option>
                                <option value="Set">Set</option>
                                <option value="Lusin">Lusin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="productPrice" class="form-label">Harga <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" id="productPrice" name="harga" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Simpan</button>

                        <!-- Delete button (only visible when editing) -->
                        <button type="button" class="btn btn-danger btn-delete me-auto" id="deleteProductBtn" style="display: none;" 
                                onclick="confirmDeleteInModal()">
                            <i class="fas fa-trash"></i> Hapus Produk
                        </button>
                    
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="deleteForm">
                    <div class="modal-body">
                        <input type="hidden" id="deleteId" name="delete_id" value="">
                        <p>Apakah Anda yakin ingin menghapus produk <strong id="deleteProductName"></strong>?</p>
                        <p class="text-danger">Tindakan ini tidak dapat dibatalkan.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Hapus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Application Script -->
    <script>
        // Function to add new product
        function addNewProduct() {
            // Reset the form
            document.getElementById('editId').value = '';
            document.getElementById('productCode').value = '';
            document.getElementById('productName').value = '';
            document.getElementById('productUnit').value = '';
            document.getElementById('productPrice').value = '';
            document.getElementById('modalTitle').textContent = 'Tambah Produk Baru';
            
            // Reset image previews
            document.getElementById('imagePreview').style.display = 'none';
            document.getElementById('currentImageContainer').style.display = 'none';
            document.getElementById('productImage').value = '';
            
            // Hide delete button for new products
            document.getElementById('deleteProductBtn').style.display = 'none';

             // Hide delete button for new products
            document.getElementById('deleteProductBtn').style.display = 'none';

            // Show the modal
            var productModal = new bootstrap.Modal(document.getElementById('productModal'));
            productModal.show();
        }

		// Function to edit product
        function editProduct(kode, nama, satuan, harga, image) {
			console.log("Editing product:", kode, nama, satuan, harga, image);
			
            document.getElementById('modalTitle').textContent = 'Edit Produk';
            document.getElementById('editId').value = kode;
            document.getElementById('productCode').value = kode;
            document.getElementById('productName').value = nama;
            document.getElementById('productUnit').value = satuan;
            document.getElementById('productPrice').value = harga;
			document.getElementById('currentImage').value = image;
            
            // Show current image if exists
            if (image && image !== 'null' && image !== '') {
                console.log("Setting current image to:", image);
                document.getElementById('currentImagePreview').src = image;
                document.getElementById('currentImageContainer').style.display = 'block';
            } else {
                console.log("No image available for this product");
                document.getElementById('currentImageContainer').style.display = 'none';
            }
            
            // Hide new image preview
            document.getElementById('imagePreview').style.display = 'none';

            // Show delete button for existing products
            document.getElementById('deleteProductBtn').style.display = 'block';
            document.getElementById('deleteProductBtn').setAttribute('data-kode', kode);
            document.getElementById('deleteProductBtn').setAttribute('data-name', nama);
 
            // Show the modal
            var productModal = new bootstrap.Modal(document.getElementById('productModal'));
            productModal.show();
        }

        // Function to preview image before upload
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            const currentImageContainer = document.getElementById('currentImageContainer');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    currentImageContainer.style.display = 'none';
                }
                
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.style.display = 'none';
                if (document.getElementById('currentImage').value) {
                    currentImageContainer.style.display = 'block';
                }
            }
        }

        // Function to show delete confirmation modal from product modal
        function confirmDeleteInModal() {
            const kode = document.getElementById('deleteProductBtn').getAttribute('data-kode');
            const name = document.getElementById('deleteProductBtn').getAttribute('data-name');
            
            // Close the product modal
            var productModal = bootstrap.Modal.getInstance(document.getElementById('productModal'));
            productModal.hide();
            
            // Show the delete confirmation modal
            showDeleteModal(kode, name);
        }
        
        // Function to show delete confirmation modal
        function showDeleteModal(kode, nama) {
            document.getElementById('deleteProductName').textContent = nama;
			document.getElementById('deleteId').value = kode;
            
            // Show the modal
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }
        
        // Reset form when modal is closed
        document.getElementById('productModal').addEventListener('hidden.bs.modal', function () {
            // Clear form fields when modal is closed
            document.getElementById('editId').value = '';
            document.getElementById('productCode').value = '';
            document.getElementById('productName').value = '';
            document.getElementById('productUnit').value = '';
            document.getElementById('productPrice').value = '';
            document.getElementById('modalTitle').textContent = 'Tambah Produk Baru';

            // Reset image previews
            document.getElementById('imagePreview').style.display = 'none';
            document.getElementById('currentImageContainer').style.display = 'none';
            document.getElementById('productImage').value = '';
   
			// Hide delete button
            document.getElementById('deleteProductBtn').style.display = 'none';
        });

        // Remove any URL parameters when page loads to clean the address bar
        if (window.history.replaceState && window.location.search) {
            // Remove query parameters without refreshing page
            window.history.replaceState({}, document.title, window.location.pathname);
        }
	</script>
</body>
</html>