<?php
session_start();

// Define a constant for the session key
define('TIME_TRAVEL_SESSION_KEY', 'time_travel_date');

// Database configuration (from your provided code)
class Database {
    private $host = 'localhost';
    private $username = 'root';
    private $password = '';
    private $dbname = 'gym_managementdb';
    public $connection;

    function connect() {
        try {
            if($this->connection === null) {
                $this->connection = new PDO(
                    "mysql:host=$this->host;dbname=$this->dbname",
                    $this->username,
                    $this->password,
                    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
                );

                // Set timezone to Asia/Manila (+08:00)
                $this->connection->exec("SET time_zone = '+08:00'");
            }
            return $this->connection;
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
}

// Initialize database connection
$database = new Database();
try {
    $pdo = $database->connect();
    $pdo->exec("SET time_zone = '+08:00'");
} catch (Exception $e) {
    die("Connection failed: " . $e->getMessage());
}


/**
 * Gets the "system" date, adjusted for time travel, in a format suitable for database queries.
 *
 * This function returns either the user-selected "traveled" date (if time travel is active)
 * or the actual current date, formatted as a string for use in SQL queries.
 *
 * @return string The current system date.
 */
function getSystemDate(): string {
    if (isset($_SESSION[TIME_TRAVEL_SESSION_KEY]) && $_SESSION[TIME_TRAVEL_SESSION_KEY] instanceof DateTime) {
        return $_SESSION[TIME_TRAVEL_SESSION_KEY]->format('Y-m-d H:i:s');
    } else {
        return (new DateTime())->format('Y-m-d H:i:s'); // Returns the actual current date
    }
}

/**
 * Sets the "time travel" date, now taking into account the database server's time and timezone.
 *
 * This function gets the current time from the database and adds the user-provided offset.
 * It explicitly sets the timezone to Asia/Manila.
 *
 * @param string|null $dateString The date string to travel to, or null to reset to the current date.
 */
function setTimeTravelDate(?string $dateString): void {
    global $pdo; // Access the database connection

    if ($dateString === null) {
        unset($_SESSION[TIME_TRAVEL_SESSION_KEY]); // Clear the session variable
    } else {
        try {
            // Get the current database time.
            $stmt = $pdo->query("SELECT NOW()");
            $dbTimeResult = $stmt->fetchColumn();
            $dbTime = new DateTime($dbTimeResult, new DateTimeZone('Asia/Manila')); // Use the correct timezone

            $travelDate = new DateTime($dateString, new DateTimeZone('Asia/Manila'));
            // Calculate the difference between the provided time and the current db time.
            $interval = $dbTime->diff($travelDate);

            // Apply the interval to the database time.
            $dbTime->add($interval);
            $_SESSION[TIME_TRAVEL_SESSION_KEY] = $dbTime;

        } catch (Exception $e) {
            // Handle the error (e.g., log it, display a message)
            error_log("Error setting time travel date: " . $e->getMessage());
            // Consider showing a user-friendly message, but avoid leaking sensitive details in production
            echo "Invalid date format or database error. Please check the date and try again.";
        }
    }
}



// Handle form submission for setting the time travel date
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['time_travel_date'])) {
    $date_str = $_POST['time_travel_date'];
    if (!empty($date_str)) {
        setTimeTravelDate($date_str);
    } else {
        setTimeTravelDate(null); // Reset time travel
    }
    // Redirect to prevent form resubmission on refresh
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Get the current system date (for display)
$currentSystemDate = getSystemDate();

// Calculate the 5 days before expiration.
$expirationDate = new DateTime("2025-04-30", new DateTimeZone('Asia/Manila')); // Example expiration date.
$expiringSoon = clone $expirationDate;
$expiringSoon->modify("-5 days");


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gym Management - Time Travel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@tailwindcss/browser@latest"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>
        /* Optional: Custom styles for the datepicker container */
        .flatpickr-calendar {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1);
            font-family: 'Inter', sans-serif;
            z-index: 10; /* Ensure it's above other elements */
        }

        .flatpickr-day {
            border-radius: 0.25rem;
            color: #4b5563;
            font-weight: 500;
            height: 2.5rem;
            line-height: 2.5rem;
            text-align: center;
            width: 2.5rem;
        }

        .flatpickr-day.today {
            border-color: #6b7280;
        }

        .flatpickr-day.selected,
        .flatpickr-day.startRange,
        .flatpickr-day.endRange,
        .flatpickr-day.inRange {
            background: #3b82f6;
            color: #fff;
        }

        .flatpickr-day.selected:hover,
        .flatpickr-day.startRange:hover,
        .flatpickr-day.endRange:hover,
        .flatpickr-day.inRange:hover,
        .flatpickr-day:hover {
            background: #2563eb;
            color: #fff;
        }

        .flatpickr-disabled,
        .flatpickr-disabled:hover,
        .flatpickr-day.prevMonth,
        .flatpickr-day.nextMonth,
        .flatpickr-day.notAllowed,
        .flatpickr-day.notAllowed.prevMonth,
        .flatpickr-day.notAllowed.nextMonth {
            color: #d1d5db;
            cursor: not-allowed;
        }

        .flatpickr-input {
            border-radius: 0.375rem;
            border: 1px solid #d1d5db;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            line-height: 1.25rem;
            width: 100%;
            transition: border-color 0.15s ease-in-out, shadow-sm 0.15s ease-in-out;
            font-family: 'Inter', sans-serif;
        }

        .flatpickr-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
            outline: none;
        }

        .flatpickr-calendar.has-time .flatpickr-time-container {
            border-top: 1px solid #e5e7eb;
        }

        .flatpickr-time-container {
            align-items: center;
            display: flex;
            gap: 0.5rem;
            padding: 0.5rem;
        }

        .flatpickr-time {
            display: flex;
            flex-wrap: wrap;
            gap: 0.25rem;
        }

        .flatpickr-time-separator {
            margin: 0 0.25rem;
            color: #4b5563;
            font-weight: 600;
        }

        .flatpickr-number-input {
            border-radius: 0.25rem;
            border: 1px solid #d1d5db;
            padding: 0.25rem;
            width: 2.5rem;
            text-align: center;
            font-size: 0.875rem;
            line-height: 1.25rem;
            font-family: 'Inter', sans-serif;
            -moz-appearance: textfield; /* Firefox */
        }

        .flatpickr-number-input::-webkit-outer-spin-button,
        .flatpickr-number-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .flatpickr-number-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
            outline: none;
        }
        .flatpickr-am-pm {
            padding: 0.25rem;
            text-align: center;
            font-size: 0.875rem;
            line-height: 1.25rem;
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100 font-inter antialiased">
    <div class="min-h-screen flex items-center justify-center py-10">
        <div class="bg-white rounded-lg shadow-md p-8 w-full max-w-md">
            <h1 class="text-2xl font-semibold text-gray-800 mb-6 text-center">Time Travel</h1>

            <div class="mb-6">
                <p class="text-sm text-gray-500">
                    Current System Date: <span class="font-medium text-gray-700"><?php echo date('F j, Y H:i:s', strtotime(getSystemDate())); ?></span>
                    <?php if (isset($_SESSION[TIME_TRAVEL_SESSION_KEY])): ?>
                        <span class="text-xs text-red-500 ml-2">(Time Travel Active)</span>
                    <?php endif; ?>
                </p>
            </div>

            <form method="post" class="space-y-4">
                <div>
                    <label for="time_travel_date" class="block text-sm font-medium text-gray-700">
                        Set Time Travel Date
                    </label>
                    <div class="mt-1">
                        <input
                            type="text"
                            id="time_travel_date"
                            name="time_travel_date"
                            class="flatpickr-input"
                            placeholder="Select a date..."
                            value="<?php echo isset($_SESSION[TIME_TRAVEL_SESSION_KEY]) ? $_SESSION[TIME_TRAVEL_SESSION_KEY]->format('Y-m-d H:i:s') : ''; ?>"
                        >
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="submit" class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                            onclick="document.getElementById('time_travel_date').value = '';" >
                        Reset
                    </button>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Set Date
                    </button>
                </div>
            </form>

            <div class="mt-8">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Testing Example</h2>
                <p class="text-sm text-gray-600">
                    Expiration Date: <?php echo $expirationDate->format('F j, Y'); ?>
                </p>
                <p class="text-sm text-gray-600">
                    Expiring Soon (5 days before): <?php echo $expiringSoon->format('F j, Y'); ?>
                </p>

                <?php
                //  use the string version of the date for comparison
                //  ***IMPORTANT CHANGE HERE: USE getSystemDate()***
                if (getSystemDate() > $expirationDate->format('Y-m-d H:i:s')) {
                    echo "<p class='text-red-500 font-medium mt-2'>Service is EXPIRED.</p>";
                } elseif (getSystemDate() >= $expiringSoon->format('Y-m-d H:i:s') && getSystemDate() <= $expirationDate->format('Y-m-d H:i:s')) {
                    echo "<p class='text-yellow-500 font-medium mt-2'>Service is EXPIRING SOON.</p>";
                } else {
                    echo "<p class='text-green-500 font-medium mt-2'>Service is Active.</p>";
                }
                ?>
            </div>  
        </div>
    </div>

    <script>
        flatpickr("#time_travel_date", {
            dateFormat: "Y-m-d H:i:s",
            enableTime: true,
            dateFormat: "Y-m-d H:i:s",
        });
    </script>
</body>
</html>