<?php
/**
 * Basic PHPUnit tests for BOQ Construction Suite plugin.
 */
class ImporterTest extends WP_UnitTestCase {
    /** @var BOQ_Module_BOQ_Importer */
    protected $importer;

    public function setUp(): void {
        parent::setUp();
        $this->importer = new BOQ_Module_BOQ_Importer();
    }

    public function test_csv_parsing_simple() {
        $csv = "Item,Qty,Unit,Cost\nBricks,100,pcs,0.5";
        $result = $this->importer->parse_csv( $csv );
        $this->assertIsArray( $result );
        $this->assertCount( 1, $result );
        $this->assertEquals( 'Bricks', $result[0]['item'] );
        $this->assertEquals( 100, $result[0]['qty'] );
        $this->assertEquals( 'pcs', $result[0]['unit'] );
        $this->assertEquals( 0.5, $result[0]['cost'] );
    }
}
?>
