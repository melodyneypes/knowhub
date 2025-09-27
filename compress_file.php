<?php
// compress_file.php - File compression functionality

/**
 * Compress a file using ZIP compression
 * 
 * @param string $source_file Path to the source file
 * @param string $destination Path to the destination compressed file
 * @return bool True on success, false on failure
 */
function compress_file($source_file, $destination) {
    // Check if the source file exists
    if (!file_exists($source_file)) {
        return false;
    }
    
    // Create a new ZIP archive
    $zip = new ZipArchive();
    
    if ($zip->open($destination, ZipArchive::CREATE) === TRUE) {
        // Add the file to the archive
        $filename = basename($source_file);
        $zip->addFile($source_file, $filename);
        $zip->close();
        return true;
    }
    
    return false;
}

/**
 * Decompress a ZIP file
 * 
 * @param string $source_zip Path to the ZIP file
 * @param string $destination Directory to extract to
 * @return bool True on success, false on failure
 */
function decompress_file($source_zip, $destination) {
    // Check if the ZIP file exists
    if (!file_exists($source_zip)) {
        return false;
    }
    
    // Create destination directory if it doesn't exist
    if (!is_dir($destination)) {
        mkdir($destination, 0777, true);
    }
    
    // Extract the ZIP file
    $zip = new ZipArchive();
    
    if ($zip->open($source_zip) === TRUE) {
        $zip->extractTo($destination);
        $zip->close();
        return true;
    }
    
    return false;
}

/**
 * Get the compression ratio of a file
 * 
 * @param string $original_file Path to the original file
 * @param string $compressed_file Path to the compressed file
 * @return float Compression ratio as a percentage
 */
function get_compression_ratio($original_file, $compressed_file) {
    if (!file_exists($original_file) || !file_exists($compressed_file)) {
        return 0;
    }
    
    $original_size = filesize($original_file);
    $compressed_size = filesize($compressed_file);
    
    if ($original_size == 0) {
        return 0;
    }
    
    return (1 - ($compressed_size / $original_size)) * 100;
}
?>