<?php
/**
 * Class Module BOQ Importer
 *
 * Handles import of BOQ data from CSV or Excel files into the plugin's internal structures.
 * Includes security (MIME/type validation, size limits) and i18n-ready messages.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Include Composer autoloader if present (self‑contained plugin)
if ( file_exists( __DIR__ . '/../../vendor/autoload.php' ) ) {
    require_once __DIR__ . '/../../vendor/autoload.php';
}

class BOQ_Module_BOQ_Importer {
    const MAX_UPLOAD_SIZE = 5242880; // 5 MB
    const ALLOWED_EXTENSIONS = array( 'csv', 'xls', 'xlsx' );

    /**
     * Handles file upload via WordPress media handling.
     *
     * @param array $file Uploaded file array from $_FILES.
     * @return array Result with success flag and localized message.
     */
    public static function handle_upload( $file ) {
        if ( empty( $file ) || $file['error'] !== UPLOAD_ERR_OK ) {
            return array( 'success' => false, 'message' => __( 'File upload error.', 'boq-construction-suite' ) );
        }

        // Size check
        if ( $file['size'] > self::MAX_UPLOAD_SIZE ) {
            return array( 'success' => false, 'message' => __( 'Uploaded file exceeds the 5 MB size limit.', 'boq-construction-suite' ) );
        }

        $ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
        if ( ! in_array( $ext, self::ALLOWED_EXTENSIONS, true ) ) {
            return array( 'success' => false, 'message' => __( 'Unsupported file type.', 'boq-construction-suite' ) );
        }

        // MIME verification (additional safety)
        $mime = mime_content_type( $file['tmp_name'] );
        $allowed_mimes = array(
            'csv'  => 'text/plain',
            'xls'  => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        );
        if ( ! empty( $allowed_mimes[ $ext ] ) && strpos( $mime, $allowed_mimes[ $ext ] ) === false ) {
            return array( 'success' => false, 'message' => __( 'File MIME type does not match allowed types.', 'boq-construction-suite' ) );
        }

        // Parse based on extension.
        if ( $ext === 'csv' ) {
            $data = self::parse_csv_file( $file['tmp_name'] );
        } else {
            $data = self::parse_excel_file( $file['tmp_name'] );
        }

        if ( is_wp_error( $data ) ) {
            return array( 'success' => false, 'message' => $data->get_error_message() );
        }

        $inserted = self::import_items( $data );
        return array( 'success' => $inserted, 'message' => $inserted ? __( 'Import successful.', 'boq-construction-suite' ) : __( 'Import failed.', 'boq-construction-suite' ) );
    }

    /**
     * Parse CSV text (used by REST endpoint).
     *
     * @param string $csv_text Raw CSV content.
     * @return array Parsed result with i18n messages.
     */
    public static function parse_csv( $csv_text ) {
        if ( empty( $csv_text ) ) {
            return array( 'success' => false, 'message' => __( 'Empty CSV content.', 'boq-construction-suite' ), 'items' => array() );
        }
        $lines = explode( "\n", trim( $csv_text ) );
        $header = str_getcsv( array_shift( $lines ) );
        if ( empty( $header ) ) {
            return array( 'success' => false, 'message' => __( 'CSV header missing.', 'boq-construction-suite' ), 'items' => array() );
        }
        $items = array();
        foreach ( $lines as $line ) {
            if ( trim( $line ) === '' ) {
                continue;
            }
            $row = str_getcsv( $line );
            $items[] = array_combine( $header, $row );
        }
        return array( 'success' => true, 'items' => $items, 'message' => __( 'Parsed successfully.', 'boq-construction-suite' ) );
    }

    /**
     * Import parsed items into the master rates table.
     *
     * @param array $items Parsed BOQ items.
     * @return array Result with i18n messages.
     */
    public static function import_to_master_rates( $items ) {
        if ( empty( $items ) ) {
            return array( 'added' => 0, 'success' => false, 'message' => __( 'No items provided.', 'boq-construction-suite' ) );
        }
        global $wpdb;
        $table = $wpdb->prefix . 'boq_rates';
        $added = 0;
        // Begin transaction for atomicity.
        $wpdb->query( 'START TRANSACTION' );
        foreach ( $items as $item ) {
            if ( empty( $item['description'] ) || empty( $item['rate'] ) ) {
                continue;
            }
            $wpdb->insert( $table, array(
                'description' => sanitize_text_field( $item['description'] ),
                'unit'        => sanitize_text_field( $item['unit'] ?? '' ),
                'rate'        => floatval( $item['rate'] ),
                'currency'    => sanitize_text_field( $item['currency'] ?? 'GHS' ),
                'category'    => sanitize_text_field( $item['category'] ?? 'General' ),
                'created_at'  => current_time( 'mysql' ),
            ) );
            $added++;
        }
        $wpdb->query( 'COMMIT' );
        return array( 'added' => $added, 'success' => true, 'message' => sprintf( __( '%d rates added.', 'boq-construction-suite' ), $added ) );
    }

    /**
     * Parse CSV from a file path (used by file upload).
     */
    private static function parse_csv_file( $path ) {
        if ( ! file_exists( $path ) ) {
            return new WP_Error( 'file_missing', __( 'CSV file not found.', 'boq-construction-suite' ) );
        }
        $rows = array();
        if ( ( $handle = fopen( $path, 'r' ) ) !== false ) {
            $header = fgetcsv( $handle );
            if ( ! $header ) {
                return new WP_Error( 'csv_invalid', __( 'CSV header missing.', 'boq-construction-suite' ) );
            }
            while ( ( $data = fgetcsv( $handle ) ) !== false ) {
                $rows[] = array_combine( $header, $data );
            }
            fclose( $handle );
        }
        return $rows;
    }

    /**
     * Parse Excel file using PhpSpreadsheet if available.
     */
    private static function parse_excel_file( $path ) {
        if ( ! class_exists( '\\PhpOffice\\PhpSpreadsheet\\IOFactory' ) ) {
            return new WP_Error( 'phpspreadsheet_missing', __( 'PhpSpreadsheet library not installed.', 'boq-construction-suite' ) );
        }
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load( $path );
        $sheet = $spreadsheet->getActiveSheet();
        $rows = array();
        $header = array();
        foreach ( $sheet->getRowIterator() as $rowIndex => $row ) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells( false );
            $cells = array();
            foreach ( $cellIterator as $cell ) {
                $cells[] = $cell->getValue();
            }
            if ( $rowIndex === 1 ) {
                $header = $cells;
                continue;
            }
            $rows[] = array_combine( $header, $cells );
        }
        return $rows;
    }

    /**
     * Insert parsed items into the plugin's rate database (used by both upload & REST).
     */
    private static function import_items( $items ) {
        if ( empty( $items ) ) {
            return false;
        }
        global $wpdb;
        $table = $wpdb->prefix . 'boq_rates';
        foreach ( $items as $item ) {
            if ( empty( $item['description'] ) || empty( $item['rate'] ) ) {
                continue;
            }
            $wpdb->insert( $table, array(
                'description' => sanitize_text_field( $item['description'] ),
                'unit'        => sanitize_text_field( $item['unit'] ?? '' ),
                'rate'        => floatval( $item['rate'] ),
                'currency'    => sanitize_text_field( $item['currency'] ?? 'GHS' ),
                'category'    => sanitize_text_field( $item['category'] ?? 'General' ),
                'created_at'  => current_time( 'mysql' ),
            ) );
        }
        return true;
    }
}
?>
