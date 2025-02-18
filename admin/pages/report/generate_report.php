<?php
require_once 'config.php';
require_once '../../../vendor/autoload.php'; // For TCPDF or other PDF library
require_once 'report.php';
class ReportGenerator {
    private $pdf;
    private $data;
    
    public function __construct($data) {
        $this->data = $data;
        $this->pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $this->setupPDF();
    }
    
    private function setupPDF() {
        // Set document information
        $this->pdf->SetCreator('Analytics System');
        $this->pdf->SetAuthor('Your Company Name');
        $this->pdf->SetTitle('Analytics Report - ' . date('F d, Y'));
        
        // Set default header data
        $this->pdf->SetHeaderData('', 0, 'Analytics Report', 'Generated on ' . date('F d, Y'), array(0,0,0), array(0,0,0));
        $this->pdf->setHeaderFont(array('helvetica', '', 10));
        
        // Set margins
        $this->pdf->SetMargins(15, 20, 15);
        $this->pdf->SetHeaderMargin(10);
        $this->pdf->SetFooterMargin(10);
        
        // Set auto page breaks
        $this->pdf->SetAutoPageBreak(TRUE, 15);
    }
    
    public function generateReport() {
        // Add a page
        $this->pdf->AddPage();
        
        // Key Metrics Section
        $this->addKeyMetrics();
        
        // Attendance Analysis Section
        $this->addAttendanceAnalysis();
        
        // Monthly Earnings Section
        $this->addMonthlyEarnings();
        
        // Service Utilization Section
        $this->addServiceUtilization();
        
        // Programs and Rentals Section
        $this->addProgramsAndRentals();
        
        return $this->pdf;
    }
    
    private function addKeyMetrics() {
        $this->pdf->SetFont('helvetica', 'B', 14);
        $this->pdf->Cell(0, 10, 'Key Metrics', 0, 1, 'L');
        $this->pdf->SetFont('helvetica', '', 11);
        
        $metrics = array(
            array('Total Members', number_format($this->data['total_members'])),
            array('Total Revenue', '₱' . number_format($this->data['total_revenue'], 2)),
            array('Average Check-ins', number_format($this->data['avg_checkins'], 1))
        );
        
        foreach($metrics as $metric) {
            $this->pdf->Cell(100, 8, $metric[0] . ':', 0, 0);
            $this->pdf->Cell(0, 8, $metric[1], 0, 1);
        }
        
        $this->pdf->Ln(10);
    }
    
    private function addAttendanceAnalysis() {
        $this->pdf->SetFont('helvetica', 'B', 14);
        $this->pdf->Cell(0, 10, 'Member Attendance Analysis', 0, 1, 'L');
        
        // Table header
        $this->pdf->SetFont('helvetica', 'B', 10);
        $header = array('Username', 'Name', 'Check-ins', 'Missed', 'Rate');
        $w = array(40, 60, 30, 30, 25);
        
        foreach($header as $i => $col) {
            $this->pdf->Cell($w[$i], 7, $col, 1, 0, 'C');
        }
        $this->pdf->Ln();
        
        // Table data
        $this->pdf->SetFont('helvetica', '', 9);
        foreach($this->data['attendance'] as $row) {
            $this->pdf->Cell($w[0], 6, $row['username'], 1);
            $this->pdf->Cell($w[1], 6, $row['first_name'] . ' ' . $row['last_name'], 1);
            $this->pdf->Cell($w[2], 6, number_format($row['total_check_ins']), 1, 0, 'R');
            $this->pdf->Cell($w[3], 6, number_format($row['total_missed']), 1, 0, 'R');
            $rate = ($row['total_check_ins'] / ($row['total_check_ins'] + $row['total_missed'])) * 100;
            $this->pdf->Cell($w[4], 6, number_format($rate, 1) . '%', 1, 0, 'R');
            $this->pdf->Ln();
        }
        
        $this->pdf->Ln(10);
    }
    
    private function addMonthlyEarnings() {
        $this->pdf->AddPage();
        $this->pdf->SetFont('helvetica', 'B', 14);
        $this->pdf->Cell(0, 10, 'Monthly Earnings', 0, 1, 'L');
        
        // Table header
        $this->pdf->SetFont('helvetica', 'B', 10);
        $header = array('Month', 'Year', 'Memberships', 'Amount');
        $w = array(40, 30, 40, 40);
        
        foreach($header as $i => $col) {
            $this->pdf->Cell($w[$i], 7, $col, 1, 0, 'C');
        }
        $this->pdf->Ln();
        
        // Table data
        $this->pdf->SetFont('helvetica', '', 9);
        foreach($this->data['earnings'] as $row) {
            $month = date('F', mktime(0, 0, 0, $row['month'], 10));
            $this->pdf->Cell($w[0], 6, $month, 1);
            $this->pdf->Cell($w[1], 6, $row['year'], 1);
            $this->pdf->Cell($w[2], 6, number_format($row['total_memberships']), 1, 0, 'R');
            $this->pdf->Cell($w[3], 6, '₱' . number_format($row['total_amount'], 2), 1, 0, 'R');
            $this->pdf->Ln();
        }
        
        $this->pdf->Ln(10);
    }
    
    private function addServiceUtilization() {
        $this->pdf->AddPage();
        $this->pdf->SetFont('helvetica', 'B', 14);
        $this->pdf->Cell(0, 10, 'Service Utilization', 0, 1, 'L');
        
        // Table header
        $this->pdf->SetFont('helvetica', 'B', 10);
        $header = array('Service', 'Total Sessions', 'Utilized', 'Not Utilized', 'Utilization Rate');
        $w = array(50, 40, 40, 40, 40);
        
        foreach($header as $i => $col) {
            $this->pdf->Cell($w[$i], 7, $col, 1, 0, 'C');
        }
        $this->pdf->Ln();
        
        // Table data
        $this->pdf->SetFont('helvetica', '', 9);
        foreach($this->data['service_utilization'] as $row) {
            $this->pdf->Cell($w[0], 6, $row['service_name'], 1);
            $this->pdf->Cell($w[1], 6, number_format($row['total_sessions']), 1, 0, 'R');
            $this->pdf->Cell($w[2], 6, number_format($row['utilized_sessions']), 1, 0, 'R');
            $this->pdf->Cell($w[3], 6, number_format($row['not_utilized_sessions']), 1, 0, 'R');
            $rate = ($row['utilized_sessions'] / $row['total_sessions']) * 100;
            $this->pdf->Cell($w[4], 6, number_format($rate, 1) . '%', 1, 0, 'R');
            $this->pdf->Ln();
        }
        
        $this->pdf->Ln(10);
    }
    
    private function addProgramsAndRentals() {
        $this->pdf->AddPage();
        $this->pdf->SetFont('helvetica', 'B', 14);
        $this->pdf->Cell(0, 10, 'Programs and Rentals', 0, 1, 'L');
        
        // Table header
        $this->pdf->SetFont('helvetica', 'B', 10);
        $header = array('Program/Rental', 'Month', 'Year', 'Total Bookings', 'Total Revenue');
        $w = array(50, 30, 30, 40, 40);
        
        foreach($header as $i => $col) {
            $this->pdf->Cell($w[$i], 7, $col, 1, 0, 'C');
        }
        $this->pdf->Ln();
        
        // Table data
        $this->pdf->SetFont('helvetica', '', 9);
        foreach($this->data['programs_rentals'] as $row) {
            $month = date('F', mktime(0, 0, 0, $row['month'], 10));
            $this->pdf->Cell($w[0], 6, $row['program_or_rental_name'], 1);
            $this->pdf->Cell($w[1], 6, $month, 1);
            $this->pdf->Cell($w[2], 6, $row['year'], 1);
            $this->pdf->Cell($w[3], 6, number_format($row['total_bookings']), 1, 0, 'R');
            $this->pdf->Cell($w[4], 6, '₱' . number_format($row['total_revenue'], 2), 1, 0, 'R');
            $this->pdf->Ln();
        }
        
        $this->pdf->Ln(10);
    }
    
}

// Prepare data array
$reportData = array(
    'total_members' => $total_members,
    'total_revenue' => $total_revenue,
    'avg_checkins' => $avg_checkins,
    'attendance' => $attendance,
    'earnings' => $earnings,
    'utilization' => $utilization,
    'programs' => $programs,
    'rentals' => $rentals
);
// Generate PDF
$generator = new ReportGenerator($reportData);
$pdf = $generator->generateReport();

// Output the PDF
$pdf->Output('analytics_report_' . date('Y-m-d') . '.pdf', 'D');
?>