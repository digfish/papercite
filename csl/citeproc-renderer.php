<?php
/**
 * Created by PhpStorm.
 * User: sam
 * Date: 23-03-2019
 * Time: 16:36
 * Adapter for generating  bibliographies in HTML using citeproc-php
 * @see \citeproc
 */

//require_once dirname( __DIR__ ) . "/vendor/autoload.php";
$ds= DIRECTORY_SEPARATOR;
define('PAPERCITE_DIR',str_replace("/",$ds,dirname( __DIR__ )));
$citeproc_dir = PAPERCITE_DIR . "{$ds}vendor{$ds}autoload.php";

if (file_exists($citeproc_dir))
	require_once $citeproc_dir;

use Seboettg\CiteProc\CiteProc;
use Seboettg\CiteProc\StyleSheet;

class CiteProcRenderer {

	var $jsonFilename;
	var $data;
	var $styleDefs;
	var $styleName;
	var $citeproc;
	var $language;


	private function getClientLanguage() {
		$default = "en-US";
		if ( isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) {
			$header  = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
			$matches = preg_split( '/\,/', $header );
			if ( count( $matches ) > 0 ) {
				return $matches[0];
			}
		}

		return $default;
	}


	public function setJsonSource( $jsonFilename ) {
		$this->jsonFilename = $jsonFilename;
		$this->data         = json_decode( file_get_contents( $this->jsonFilename ) );
	}

	public function setStyleDefs( $styleDefs ) {
		$this->styleDefs = $styleDefs;
		$htmlExtensions  = array( 'URL' => array( $this, 'renderURL' ) );
		$lang            = $this->getClientLanguage();

		$this->citeproc = new CiteProc( $this->styleDefs, $lang, $htmlExtensions );
	}

	public function setStyleName( $styleName ) {
		$this->styleName = $styleName;
		$this->styleDefs = StyleSheet::loadStyleSheet( $styleName );
	}


	public function init( $params = array() ) {
		if ( empty( $params['styleName'] ) && ! empty( $this->styleName ) ) {
			$styleName = $this->styleName;
		} else {
			$styleName = $params['styleName'];
		}

		if ( ! empty( $params['data'] ) ) {
			$this->data = $params['data'];
		}

		return $styleName;
	}

	public function cssStyles() {
		$css_styles = null;
		try {
			$css_styles = $this->citeproc->renderCssStyles();
		} catch (Exception $ex) {
			debug($ex);
			throw $ex;
		}
		return $css_styles;
	}

	public function renderURL( $cslItem, $renderedText ) {
		if ( ! empty( $renderedText ) ) {
			return "<A href=\"{$cslItem->URL}\">" . htmlspecialchars( $renderedText ) . "</A>";
		} else {
			return "";
		}
	}

	public function bibliography( $params = array() ) {


		if ( ! empty( $params['data'] ) ) {
			$this->data = $params['data'];
		}
		$dataObj = $this->data;

		$bibliography = $this->citeproc->render( $dataObj, "bibliography" );

		return $bibliography;
	}

	public function add_anchors(&$biblio_html) {
		$qp = html5qp($biblio_html);

		$out = '';
		$qpa = new QueryPath\DOMQuery();
		$qp->find('.csl-entry')->each(function ($i,$elem) use($qpa)  {
			$idx = intval($i)+1;
			$new_anchor = "<SPAN class='csl-entry-idx'>[$idx]</SPAN> <A name='paperkey_$idx'></A>";
			$qp = html5qp($elem)->prepend($new_anchor);
		});
		ob_start();
		$qp->writeHTML(null);
		return ob_get_clean();
	}


	public function citation( $params = array() ) {
		$styleName = $this->init( $params );
		$this->setStyleName( $styleName );
		$this->setStyleDefs( $this->styleDefs );

		if ( ! empty( $params['data'] ) ) {
			$this->data = $params['data'];
		}

		$citation = $this->citeproc->render( array( $this->data ), "citation" );

		return $citation;
	}
}