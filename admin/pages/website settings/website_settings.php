<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Website Settings</title>
    <link rel="stylesheet" href="C:\xampp\htdocs\Gym_MembershipSE-XLB\admin\css\admin.css">
</head>
<body>
<h2>Website Settings</h2>

<form action="upload.php" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Header Section</h4>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="companyHeader" class="form-label">Upload Header Image:</label>
                    <input type="file" class="form-control" id="companyHeader" name="companyHeader" accept="image/*" required>
                </div>
                <div class="mb-3">
                    <label for="companyName" class="form-label">Company Name:</label>
                    <input type="text" class="form-control" id="companyName" name="companyName" required>
                </div>
                <div class="mb-3">
                    <label for="companyDescription" class="form-label">Company Description:</label>
                    <input type="text" class="form-control" id="companyDescription" name="companyDescription" required>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Offers Section</h4>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="companyHeader" class="form-label">Upload offers image:</label>
                    <input type="file" class="form-control" id="companyHeader" name="companyHeader" accept="image/*" required>
                </div>
                <div class="mb-3">
                    <label for="title" class="form-label">Title:</label>
                    <input type="text" class="form-control" id="title" name="title" required>
                </div>
                <div class="mb-3">
                    <label for="Description" class="form-label">Description:</label>
                    <input type="text" class="form-control" id="Description" name="Description" required>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Products Section</h4>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="products-img" class="form-label">Upload products image(MAX:8):</label>
                    <input type="file" class="form-control" id="products-img" name="products-img" accept="image/*" required>
                </div>

            <div class="mb-3">
                <label for="title" class="form-label">Title:</label>
                    <input type="text" class="form-control" id="title" name="title" required>
                </div>
                <div class="mb-3">
                    <label for="Description" class="form-label">Description:</label>
                    <input type="text" class="form-control" id="Description" name="Description" required>
                </div>  
            </div>
        </div>
    </div>

    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">About Us Section</h4>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="aboutUs-img" class="form-label">Upload about image(MAX:4):</label>
                    <input type="file" class="form-control" id="aboutUs-img" name="aboutUs-img" accept="image/*" required>
                </div>

                <div class="mb-3">
                    <label for="Description" class="form-label">About Us-Description:</label>
                    <input type="text" class="form-control" id="Description" name="Description" required>
                </div>  
            </div>
        </div>
    </div>

    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Tagline Section</h4>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="tagline-img" class="form-label">Upload tagline image:</label>
                    <input type="file" class="form-control" id="tagline-img" name="tagline-img" accept="image/*" required>
                </div>

                <div class="mb-3">
                    <label for="Tagline" class="form-label">Tagline:</label>
                    <input type="text" class="form-control" id="Tagline" name="Tagline" required>
                </div>  
            </div>
        </div>
    </div>

    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Staff Section</h4>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="tagline-img" class="form-label">Upload staff image (MAX:5):</label>
                    <input type="file" class="form-control" id="tagline-img" name="tagline-img" accept="image/*" required>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Contact Us Section</h4>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="location-img" class="form-label">Upload location image:</label>
                    <input type="file" class="form-control" id="location-img" name="location-img" accept="image/*" required>
                </div>

                <div class="mb-3">
                    <label for="Location" class="form-label">Location:</label>
                    <input type="text" class="form-control" id="Location" name="Location" required>
                </div>  
                <div class="mb-3">
                    <label for="Contact" class="form-label">Contact No:</label>
                    <input type="text" class="form-control" id="Contact" name="Contact" required>
                </div> 
                <div class="mb-3">
                    <label for="Email" class="form-label">Email:</label>
                    <input type="text" class="form-control" id="Email" name="Email" required>
                </div> 
            </div>
        </div>
    </div>
    
        
</form>
    <div class="mt-3">
            <button class="btn btn-primary update-btn " type="submit">Update</button>
        </div>


</body>
</html>