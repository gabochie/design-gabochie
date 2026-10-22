<?php
/**
 * Unit tests for BOQ_Module_BOQ_Importer::parse_csv().
 *
 * The contract under test is the real one: parse_csv() returns
 *   ['success' => bool, 'items' => array<int, array<string, string>>, 'message' => string]
 * where each item keeps the CSV header labels as keys.
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ImporterTest extends TestCase {
    public function test_parses_single_row_into_items(): void {
        $csv = "Item,Qty,Unit,Cost\nBricks,100,pcs,0.5";

        $result = BOQ_Module_BOQ_Importer::parse_csv($csv);

        $this->assertTrue($result['success']);
        $this->assertSame('Parsed successfully.', $result['message']);
        $this->assertCount(1, $result['items']);
        $this->assertSame(
            array('Item' => 'Bricks', 'Qty' => '100', 'Unit' => 'pcs', 'Cost' => '0.5'),
            $result['items'][0]
        );
    }

    public function test_empty_csv_is_rejected(): void {
        $result = BOQ_Module_BOQ_Importer::parse_csv('');

        $this->assertFalse($result['success']);
        $this->assertSame('Empty CSV content.', $result['message']);
        $this->assertSame(array(), $result['items']);
    }

    public function test_header_only_csv_has_no_items(): void {
        $result = BOQ_Module_BOQ_Importer::parse_csv('Item,Qty,Unit,Cost');

        $this->assertTrue($result['success']);
        $this->assertSame(array(), $result['items']);
    }

    public function test_blank_lines_are_skipped(): void {
        $csv = "Item,Qty,Unit,Cost\nBricks,100,pcs,0.5\n\n   \nCement,10,bags,12";

        $result = BOQ_Module_BOQ_Importer::parse_csv($csv);

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['items']);
        $this->assertSame('Cement', $result['items'][1]['Item']);
    }

    public function test_quoted_fields_with_commas_are_preserved(): void {
        $csv = "Item,Qty,Unit,Cost\n\"Bricks, Grade A\",100,pcs,0.5";

        $result = BOQ_Module_BOQ_Importer::parse_csv($csv);

        $this->assertTrue($result['success']);
        $this->assertSame('Bricks, Grade A', $result['items'][0]['Item']);
        $this->assertSame('100', $result['items'][0]['Qty']);
    }

    public function test_values_are_returned_as_strings(): void {
        $result = BOQ_Module_BOQ_Importer::parse_csv("Item,Qty\nBricks,100");

        $this->assertSame('100', $result['items'][0]['Qty']);
        $this->assertNotSame(100, $result['items'][0]['Qty']);
    }

    public function test_import_to_master_rates_rejects_empty_input(): void {
        $result = BOQ_Module_BOQ_Importer::import_to_master_rates(array());

        $this->assertFalse($result['success']);
        $this->assertSame(0, $result['added']);
        $this->assertSame('No items provided.', $result['message']);
    }
}
