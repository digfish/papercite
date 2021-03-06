<?php

namespace Papercite\Tests;

use CiteProcRenderer;
use CslConverter;
use Papercite;
use PHPUnit\Framework\TestCase;


require_once( __DIR__ . '/../tests/papercite-root.php' );

require_once __DIR__ . "/../papercite.classes.php";
//require_once __DIR__ . "/../utils/my-converter.php";
require_once __DIR__ . "/../csl/citeproc-renderer.php";
require_once __DIR__ . "/../csl/csl-converter.php";

if ( ! defined( 'PHPUNIT_PAPERCITE_TESTSUITE' ) ) {
	define( 'PHPUNIT_PAPERCITE_TESTSUITE', 1 );
}

class ConvertersTest extends TestCase {

	var $papercite;


	public function setUp() {
		//parent::setUp(); // TODO: Change the autogenerated stub

		$this->papercite = new Papercite();
		$this->papercite->init();
		$this->papercite->options['file'] = 'http://digfish.org/shared/converted.bib';
	}

	function testCslConverterToDate() {
		$cslConverter = new \CslConverter();
		$cslDate      = $cslConverter->toCslDate( "2019-06-07" );
		var_dump( $cslDate );
		$this->assertTrue( property_exists( $cslDate, 'date-parts' ) );
	}

	function testToRawJson() {
		$this->papercite->options["bibtex_parser"] = "pear";
		$entries                                   = $this->papercite->getEntries( $this->papercite->options );
		$sample                                    = array_slice( $entries, 0, 5 );
		//var_dump($sample);
		$converter = new CslConverter( $sample );
		$converter->setEntries();
		$converter->toJson( "converted.raw.json", 'raw' );

		$this->assertFileExists( "converted.raw.json" );
	}

	function testToCslJson() {
		$this->papercite->options["bibtex_parser"] = "pear";
		$entries                                   = $this->papercite->getEntries( $this->papercite->options );
		$converter                                 = new CslConverter( $entries );
		$converter->setEntries();
		$converter->toJson( "converted.csl.json", 'csl' );

		$this->assertFileExists( "converted.csl.json" );
	}


	function testCiteProcRendererBibliography() {
		$jsonFilename = "converted.csl.json";
		$styleName    = "apa";
		$renderer     = new CiteProcRenderer();
		$renderer->setJsonSource( $jsonFilename );
		$renderer->setStyleDefs(file_get_contents("csl-styles/apa.csl"));
		$basename                   = pathinfo( $jsonFilename, PATHINFO_FILENAME );
		$bibliography_filename_html = "$basename.bibliography.$styleName.html";
		echo "Saving bibliography to $bibliography_filename_html \n";
		file_put_contents( $bibliography_filename_html, $renderer->bibliography( array( 'styleName' => $styleName ) ) );
		echo file_get_contents( $bibliography_filename_html );
		$this->assertFileExists( $bibliography_filename_html );
	}
}
