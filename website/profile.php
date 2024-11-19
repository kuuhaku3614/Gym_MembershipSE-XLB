<?php
    session_start();
    require_once 'user_account/profile.class.php';

    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login/login.php');
        exit();
    }

    $profile = new Profile_class();
    $userDetails = $profile->getUserDetails($_SESSION['user_id']);

    $_SESSION['personal_details'] = $userDetails;

    include('includes/header.php');
?>


    <header class="container-fluid" id="title">
        <div class="container-xl" id="name">
            <h1><?= $_SESSION['personal_details']['name'] ?></h1>
            <h5>Member</h5>
        </div>
    </header>

    <section>
        <div id="availed">
            <h4>Current availed services</h4>
            <table id="availed_table">
                <tr>
                    <td>Type: <span>1 month</span></td>
                    <td>Duration: <span>1 month</span></td>
                    <td>Expiry date: <span>1 month</span></td>
                </tr>
                <tr>
                    <td>Type:</td>
                    <td>Duration:</td>
                    <td>Expiry date:</td>
                </tr>
            </table>
        </div>

        <div id="log">
            <table id="log_table">
                <tr>
                    <td style="font-style:italic; border-top-left-radius: 10px; border-bottom-left-radius: 10px;">Logged in</td>
                    <td>1:00 pm</td>
                    <td style="font-style:italic">Logged out</td>
                    <td>3:00 pm</td>
                    <td style="font-size: 0.875rem; font-weight: bold; border-top-right-radius: 10px; border-bottom-right-radius: 10px;">3 days ago</td>
                </tr>
                <tr>
                    <td style="font-style:italic; border-top-left-radius: 10px; border-bottom-left-radius: 10px;">Logged in</td>
                    <td>1:00 pm</td>
                    <td style="font-style:italic">Logged out</td>
                    <td>3:00 pm</td>
                    <td style="font-size: 0.875rem; font-weight: bold; border-top-right-radius: 10px; border-bottom-right-radius: 10px;">3 days ago</td>
                </tr>
            </table>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        :root {
            --red: #ff0000 !important;
        }

        body {
            background-color: whitesmoke !important;
        }

        header {
            margin-bottom: 2rem !important;
        }

        #title {
            background-color: var(--red) !important;
            color: white !important;
            padding: 2rem 0 !important;
            box-shadow: 0px 5px 10px gray !important;
            display: flex !important;
            justify-content: center !important;
        }
        #name h1 {
            font-size: 2.5rem !important;
            font-weight: 900 !important;
            font-style: italic !important;
            font-family: arial !important;
            margin: 0 !important;
        }
        #name h5 {
            font-size: 1.25rem!important;
            font-family: arial!important;
        }
        #name {
            display: flex!important;
            flex-direction: column!important;
            justify-content: center!important;
        }
        section {
            width: 100%!important;
            padding: 0 5%!important;
            display: flex!important;
            flex-wrap: wrap!important;
        }

        section #availed {
            width: 30%!important;
            margin-right: 3%!important;
            box-shadow: 0px 1px 3px gray!important;
            border-radius: 10px!important;
            background-color: white!important;
            display: flex!important;
            flex-direction: column!important;
            padding: 1rem!important;
            height: auto!important;
            max-height: 70vh!important;
            overflow: auto!important;
            scrollbar-width: none!important;
            -ms-overflow-style: none!important;
        }

        section #availed::-webkit-scrollbar {
            display: none!important;
        }

        section #log {
            width: 67%!important;
            background-color: whitesmoke!important;
            height: 70vh!important;
            overflow: auto!important;
            scrollbar-width: none!important;
            -ms-overflow-style: none!important;
        }

        section #log::-webkit-scrollbar {
            display: none!important;
        }

        #log_table {
            width: 100%!important;
            height: auto!important;
            border-collapse: separate!important;
            border-spacing: 0px 1rem!important;
            padding: 0.1rem!important;
        }

        #log_table tr {
            box-shadow: 0px 1px 3px gray!important;
            border-radius: 10px!important;
            background-color: white!important;
        }

        #log_table td {
            text-align: center!important;
            padding: 1rem!important;
            font-weight: 500!important;
            font-size: 1rem!important;
        }

        #availed_table {
            width: 100%!important;
            background-color: white!important;
            color: white!important;
            border-collapse: separate!important;
            border-spacing: 0!important;
        }

        #availed_table tr {
            padding: 1rem!important;
            border-radius: 10px!important;
            background-color: var(--red)!important;
            display: flex!important;
            flex-direction: column!important;
            margin-bottom: 1rem!important;
        }

        #availed_table td span {
            font-weight: 900!important;
        }

        @media (max-width: 768px) {
            #title {
                padding: 1.5rem 0!important;
            }
            #name h1 {
                font-size: 1.75rem!important;
            }
            #name h5 {
                font-size: 1rem!important;
            }
            section {
                flex-direction: column!important;
            }
            section #availed, section #log {
                width: 100%!important;
                margin-right: 0!important;
                margin-bottom: 2rem!important;
            }
            section #availed {
                max-height: none!important;
                height: auto!important;
            }
            #log_table td {
                font-size: 0.875rem!important;
                padding: 0.!important;
            }
        }
    </style>
    
</body>
</html>