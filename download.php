        <?php
        session_start();
        require 'db.php';
        require 'notify.php';
        require 'vendor/autoload.php'; // For PhpWord
        
        use PhpOffice\PhpWord\IOFactory;
        
        // Validate resource_id
        $resource_id = isset($_GET['id']) ? intval($_GET['id']) : null;
        if (!$resource_id) {
            die("Invalid resource ID.");
        }
        
        // Fetch resource
        $sql = "SELECT * FROM resources WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $resource_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $resource = $result->fetch_assoc();
        $stmt->close();
        
        if (!$resource) {
            die("Resource not found.");
        }
        
        // Get uploader information
        $uploader_id = isset($resource['uploader_id']) ? $resource['uploader_id'] : null;
        $downloader_name = isset($_SESSION['user']['name']) ? $_SESSION['user']['name'] : 'Unknown';
        
        // Send notification to uploader (only if uploader_id is valid and not the downloader)
        if ($uploader_id && isset($_SESSION['user']['id']) && $uploader_id != $_SESSION['user']['id']) {
            notify_file_download($uploader_id, $downloader_name, $resource['title']);
        }
        
        $file_path = $resource['file_path'];
        $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        
        if ($ext == 'pdf') {
            if (file_exists($file_path)) {
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
                readfile($file_path);
                exit;
            } else {
                die("File not found on server.");
            }
        } elseif ($ext == 'docx' || $ext == 'doc') {
            // Convert DOCX/DOC to PDF using PHPWord
            if (file_exists($file_path)) {
                $phpWord = IOFactory::load($file_path);
                $pdfWriter = IOFactory::createWriter($phpWord, 'PDF');
                $pdfFile = sys_get_temp_dir() . '/' . uniqid('converted_', true) . '.pdf';
                $pdfWriter->save($pdfFile);
        
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="' . basename($file_path, '.' . $ext) . '.pdf"');
                readfile($pdfFile);
                unlink($pdfFile); // Clean up temp file
                exit;
            } else {
                die("File not found on server.");
            }
        } elseif ($ext == 'txt') {
            // Convert TXT to PDF (simple)
            if (file_exists($file_path)) {
                $content = file_get_contents($file_path);
                $pdfFile = sys_get_temp_dir() . '/' . uniqid('converted_', true) . '.pdf';
        
                // Use TCPDF for TXT to PDF (more modern and available via Composer)
                require_once('vendor/tecnickcom/tcpdf/tcpdf.php');
        
                $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                $pdf->SetCreator(PDF_CREATOR);
                $pdf->SetAuthor('Archive System');
                $pdf->SetTitle(basename($file_path, '.' . $ext));
                $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
                $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
                $pdf->AddPage();
                $pdf->SetFont('helvetica', '', 12);
                $pdf->writeHTML(nl2br(htmlspecialchars($content)), true, false, true, false, '');
                $pdf->Output($pdfFile, 'F');
        
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="' . basename($file_path, '.' . $ext) . '.pdf"');
                readfile($pdfFile);
                unlink($pdfFile);
                exit;
            } else {
                die("File not found on server.");
            }
        } else {
            echo "Unsupported file type for conversion.";
        }
        ?>