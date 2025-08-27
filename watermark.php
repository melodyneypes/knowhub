<?php
require_once 'dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

function add_watermark($file_path, $watermark_text) {
    // HTML content with watermark
    $html = '
    <html>
    <head>
        <style>
            body {
                font-family: Arial, sans-serif;
                position: relative;
            }
            .watermark {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                font-size: 50px;
                color: rgba(255, 0, 0, 0.5);
                z-index: -1;
                pointer-events: none;
            }
            .content {
                position: relative;
                z-index: 1;
            }
        </style>
    </head>
    <body>
        <div class="watermark">' . $watermark_text . '</div>
        <div class="content">
            <!-- Your content here -->
            <h1>Sample PDF Content</h1>
            <p>This is a sample PDF content with a watermark.</p>
        </div>
    </body>
    </html>';

    // Initialize Dompdf
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);

    // Load HTML content
    $dompdf->loadHtml($html);

    // Set paper size and orientation
    $dompdf->setPaper('A4', 'portrait');

    // Render the PDF
    $dompdf->render();

    // Output the generated PDF to a file
    file_put_contents($file_path, $dompdf->output());
}

// Example usage
$file_path = 'path/to/your/pdf/file.pdf';
$watermark_text = 'Confidential';
add_watermark($file_path, $watermark_text);
?>