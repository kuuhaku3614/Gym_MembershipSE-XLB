<?php
    session_start();
    include('includes/header.php');
    require_once 'services/services.class.php';

    $Obj = new Services_Class();
    $array = $Obj->displayGymRates();
?>
<link rel="stylesheet" href="../css/browse_services.css">


<div class="services-page">
    <div class="container-fluid p-0">
        <div class="content-wrapper">
            <h2 class="fs-4 fw-bold mb-4">GYM RATES</h2>
            
            <div class="row g-4 mb-4">
                <?php foreach ($array as $arr){ ?>
                    <div class="col-sm-6 col-md-6 col-lg-3">
                        <a href="avail_product.php?id=<?= $arr['program_id'] ?>" class="program-link">
                            <div class="card shadow">
                                <div class="card-header text-white text-center">
                                    <h2 class="fw-bold mb-0"><?= $arr['program_name'] ?></h2>
                                </div>
                                <div class="card-body">
                                    <p class="card-text mb-1">Price : Php <?= $arr['price'] ?></p>
                                    <p class="card-text">Validity: <?= $arr['validity'] ?></p>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php } ?>
            </div>

            <div class="row g-4 mb-4">
                


            <h2 class="fs-4 fw-bold mt-5">OPTIONAL OFFERS</h2>

            <div class="row g-4 mb-4">
                
                <div class="col-sm-6 col-md-6 col-lg-3">
                    <div class="card shadow">
                        <div class="card-header text-white text-center">
                            <h2 class="fw-bold mb-0">Regular</h2>
                        </div>
                        <div class="card-body">
                            <p class="card-text mb-1">Price : Php.500</p>
                            <p class="card-text">Validity: 1 Month</p>
                        </div>
                    </div>
                </div>

            <div class="row g-4 mb-4"></div>


        </div>
    </div>
</div>
</body>
</html>