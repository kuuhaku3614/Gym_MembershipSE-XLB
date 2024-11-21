<?php
    session_start();
    include('includes/header.php');
    require_once 'services/services.class.php';

    $Obj = new Services_Class();
    $standard_plans = $Obj->displayStandardPlans();
    $special_plans = $Obj->displaySpecialPlans();
    $programs = $Obj->displayPrograms();
    $rental_services = $Obj->displayRentalServices();
?>
<link rel="stylesheet" href="../css/browse_services.css">

<div class="services-page">
    <div class="container-fluid p-0">
        <div class="content-wrapper">
            <!-- Standard Plans Section -->
            <h2 class="section-heading">GYM RATES</h2>
            <div class="row g-4 mb-4">
                <?php foreach ($standard_plans as $arr){ ?>
                    <div class="col-sm-6 col-md-6 col-lg-3">
                        <a href="services/avail_membership.php?id=<?= $arr['plan_id'] ?>" class="program-link">
                            <div class="card shadow">
                                <div class="card-header text-white text-center">
                                    <h2 class="fw-bold mb-0"><?= $arr['plan_name'] ?></h2>
                                </div>
                                <div class="card-body">
                                    <p class="card-text mb-1">Price: Php <?= number_format($arr['price'], 2) ?></p>
                                    <p class="card-text mb-1">Validity: <?= $arr['validity'] ?></p>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php } ?>
            </div>

            <br>

            <!-- Special Plans Section -->
            <h2 class="section-heading">SPECIAL RATES</h2>
            <div class="row g-4 mb-4">
                <?php foreach ($special_plans as $arr){ ?>
                    <div class="col-sm-6 col-md-6 col-lg-3">
                        <a href="services/avail_membership.php?id=<?= $arr['plan_id'] ?>" class="program-link">
                            <div class="card shadow">
                                <div class="card-header text-white text-center">
                                    <h2 class="fw-bold mb-0"><?= $arr['plan_name'] ?></h2>
                                </div>
                                <div class="card-body">
                                    <p class="card-text mb-1">Price: Php <?= number_format($arr['price'], 2) ?></p>
                                    <p class="card-text mb-1">Validity: <?= $arr['validity'] ?></p>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php } ?>
            </div>

            <!-- Programs Section -->
            <h2 class="section-heading">PROGRAMS</h2>
            <div class="row g-4 mb-4">
                <?php foreach ($programs as $program){ ?>
                    <div class="col-sm-6 col-md-6 col-lg-3">
                        <a href="services/avail_program.php?id=<?= $program['program_id'] ?>" class="program-link">
                            <div class="card shadow">
                                <div class="card-header text-white text-center">
                                    <h2 class="fw-bold mb-0"><?= $program['program_name'] ?></h2>
                                </div>
                                <div class="card-body">
                                    <p class="card-text mb-1">Price: Php <?= number_format($program['price'], 2) ?></p>
                                    <p class="card-text mb-1">Validity: <?= $program['validity'] ?></p>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php } ?>
            </div>

            <br>

            <!-- Rental Services Section -->
            <h2 class="section-heading">OPTIONAL SERVICES</h2>
            <div class="row g-4 mb-4">
                <?php foreach ($rental_services as $rental){ ?>
                    <div class="col-sm-6 col-md-6 col-lg-3">
                        <a href="services/avail_rental.php?id=<?= $rental['rental_id'] ?>" class="program-link">
                            <div class="card shadow">
                                <div class="card-header text-white text-center">
                                    <h2 class="fw-bold mb-0"><?= $rental['service_name'] ?></h2>
                                </div>
                                <div class="card-body">
                                    <p class="card-text mb-1">Price: Php <?= number_format($rental['price'], 2) ?></p>
                                    <p class="card-text mb-1">Validity: <?= $rental['validity'] ?></p>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>