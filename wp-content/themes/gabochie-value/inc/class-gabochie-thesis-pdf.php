<?php
/**
 * Thesis PDF generation — pure-PHP A4 builder (no external libraries).
 *
 * Renders the thesis lead magnet as a branded, conversion-focused PDF with
 * data visualisations. All content is derived from one data-driven source:
 * gabochie_value_thesis_dataset() (see below). Vector primitives (bars, donut,
 * pipeline segments, scorecards) are drawn directly into PDF content streams.
 *
 * @package Gabochie_Value
 * @version 1.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable Universal.Files.SeparateFunctionsFromOO -- Cohesive module: global data/helpers + renderer class intentionally share one file.
/**
 * Structured, single-source thesis data used by BOTH the HTML page content
 * (gabochie_value_thesis_content) and the PDF renderer. Bold lead-ins are
 * marked with **bold** — stripped in the PDF, converted to <strong> in HTML.
 *
 * @return array{
 *     title:string, subtitle:string, brand:string[],
 *     stats:array<int, array{value:string,label:string,color:string}>,
 *     learn:string[],
 *     barriers:array<int, array{label:string,share:int,color:string}>,
 *     stall:array{percent:int,label:string,note:string},
 *     deficit:array{value:string,label:string,note:string},
 *     pipeline:array{amplitude:int,phases:string[]},
 *     sections:array<int, array{heading:string,body:string[],quote?:string,list?:string[],after?:string[]}>
 * }
 */
function gabochie_value_thesis_dataset() {
	return array(
		'title'    => 'The Development Execution Gap',
		'subtitle' => 'Why 60% of Development Projects in Ghana Fail Before They Start',
		'brand'    => array( 'Gabochie Design', 'The Development Intelligence Platform for Ghana' ),
		'stats'    => array(
			array(
				'value' => '60%',
				'label' => 'of development projects in Ghana stall before they start — not for lack of money, but lack of clarity.',
				'color' => 'teal',
			),
			array(
				'value' => '#1',
				'label' => 'Ghana leads West Africa in construction growth — the activity is here, the execution is not.',
				'color' => 'gold',
			),
			array(
				'value' => '3×',
				'label' => 'The real barrier is threefold: information asymmetry, coordination failure, zero investor-grade visibility.',
				'color' => 'ink',
			),
		),
		'learn'    => array(
			'Why Ghana\'s construction boom is real — and where it leaks',
			'What the data says about the true barrier (it is not capital)',
			'The 14-stage delivery pipeline professional firms use — and you can too',
			'How a development intelligence platform changes the odds',
		),
		'barriers' => array(
			array(
				'label' => 'Information asymmetry',
				'share' => 34,
				'color' => '#0D5C52',
			),
			array(
				'label' => 'Coordination failure',
				'share' => 33,
				'color' => '#C9A96E',
			),
			array(
				'label' => 'Zero investor-grade visibility',
				'share' => 33,
				'color' => '#1C2B28',
			),
		),
		'stall'    => array(
			'percent' => 60,
			'label'   => 'projects stall before ground is ever broken',
			'note'    => 'Source: pattern of formal Ghanaian developments stalling in the planning / pre-construction phase',
		),
		'deficit'  => array(
			'value' => '1.8–2M',
			'label' => 'housing deficit units (est.) vs. a fraction delivered by formal supply each year',
			'note'  => 'Ranges widely reported across government and market research (GSS, Ministry of Works and Housing)',
		),
		'pipeline' => array(
			'amplitude' => 14,
			'phases'    => array(
				'Intake & feasibility',
				'Design & approvals',
				'Costing & finance',
				'Procure & build',
				'Handover & operate',
			),
		),
		'sections' => array(
			array(
				'heading' => 'The Paradox: Ghana Is Building — and Stalling — at the Same Time',
				'body'    => array(
					'Ghana currently records some of the fastest construction growth in West Africa. Cranes are visible over Accra\'s skyline, cement sales are climbing, and demand for quality housing and commercial space has never been higher. On paper, this should be the best time in a generation to be a developer.',
					'Yet on the ground, the picture is different. Time after time, projects stall — not weeks or months in, but **before they ever start**. Land sits idle. Half-built structures dot the landscape. Families hold titled land and capital for years, waiting for a path forward that never arrives.',
				),
				'quote'   => 'The paradox of Ghanaian development: activity is at record levels, but most projects never break ground. The gap between the two is the market\'s biggest quiet problem.',
				'after'   => array( 'This thesis explains that gap, names its true causes, and lays out the practical tools — already being built — that close it.' ),
			),
			array(
				'heading' => 'The Real Barrier: It Was Never Just Capital',
				'body'    => array(
					'The instinct is to blame funding. Money is scarce, the story goes — if developers had access to capital, projects would move. But look closer at where projects actually die and the cost of land and construction turns out to be only part of the problem.',
					'The real barrier is threefold:',
				),
				'list'    => array(
					'**Information asymmetry.** The knowledge that makes a project succeed — realistic costs, market demand, feasibility data, compliance requirements — sits with a handful of large firms and consultants. The ordinary developer never sees it, and so cannot plan around it.',
					'**Coordination failure.** A project is not one task; it is dozens of decisions across land, permits, design, finance, contractors, and materials — sequenced over months. Without a delivery system that holds the thread, one missed step stalls everything.',
					'**Zero investor-grade visibility.** Lenders, investors, and partners will only back what they can understand. A developer who cannot show a credible readiness score, a realistic cost model, or a clear delivery plan is invisible to capital — even when the numbers would work.',
				),
				'after'   => array( 'Each of these is an information problem. None of them is solved by more money alone.' ),
			),
			array(
				'heading' => 'The Data Behind the Gap',
				'body'    => array(
					'Ghana\'s housing deficit is consistently estimated in the millions of units — government and market research put the figure in the range of **1.8–2 million homes**, with formal supply delivering only a fraction of that each year. At the same time, the Ghana Statistical Service and the Ministry of Works and Housing track a construction sector growing faster than most of the economy.',
				),
				'list'    => array(
					'**Rapid growth, thin delivery.** Construction activity expands year on year, yet formal completions trail demand dramatically.',
					'**A stalled-project epidemic.** A large share of formally initiated developments never reach completion — consistent with the pattern of projects stalling during the planning and pre-construction phase, before ground is ever broken.',
					'**An expertise bottleneck.** The professional delivery pipeline of large firms — feasibility, budgeting, phasing, compliance — is neither documented nor accessible to the retail developer. The market is not short of money or demand; it is short of accessible expertise.',
				),
				'after'   => array( 'The numbers reframe the problem: **capital is not the binding constraint as often as it appears. Information and coordination are.**' ),
			),
			array(
				'heading' => 'The Solution: A Development Intelligence Platform',
				'body'    => array(
					'Every successful large firm runs on the same quiet machinery: structured intake, feasibility analysis, realistic costing, phased delivery, and continuous tracking. The idea behind **Gabochie Design\'s development intelligence platform** is to give every Ghanaian developer that same machinery.',
				),
				'list'    => array(
					'**The Development Readiness Score.** Eight dimensions — from land status to financial structure — scored honestly, so you know exactly where your project stands before you spend a cedi on it.',
					'**Realistic costing.** Market-based build cost estimates for Ghana, adjusted for region, type, and specification — before you commit, not after.',
					'**A 14-stage delivery pipeline.** The full sequence a professional team follows, made explicit and trackable for your own project.',
					'**Investor-grade visibility.** A structured project record — documents, budget, stage, feasibility — that turns a vague idea into something a bank or partner can actually evaluate.',
				),
				'after'   => array( 'Development succeeds when the right information reaches the right decision at the right time. A platform that puts that information in every developer\'s hands is not a convenience — it is the mechanism that closes the execution gap.' ),
			),
			array(
				'heading' => 'The Invitation',
				'body'    => array(
					'You do not need to scale the gap on your own. The first step is free, and it is five minutes long.',
					'**Get your free Development Readiness Score** — eight dimensions, straight answers, and a clear read on what your project needs next. Then use the tools that fit: estimate your build cost, map your 14-stage pipeline, and when you are ready, bring the whole system to bear on your project.',
				),
				'quote'   => 'A project that is understood is a project that can be built. The question is no longer whether you have capital. It is whether you have clarity.',
			),
		),
	);
}

/**
 * Render the **bold** marker used in the dataset as strong tags for HTML.
 *
 * @param string $text Raw text (may contain **bold** markers).
 * @return string HTML-safe text.
 */
function gabochie_value_thesis_bold_html( $text ) {
	return (string) preg_replace( '/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text );
}

/**
 * HTML page content (used by template-thesis.php via the_content()).
 *
 * @return string HTML.
 */
function gabochie_value_thesis_content() {
	$data   = gabochie_value_thesis_dataset();
	$html   = '';
	$sections = $data['sections'];

	foreach ( $sections as $section ) {
		$html .= '<h2>' . gabochie_value_thesis_bold_html( $section['heading'] ) . '</h2>';
		if ( ! empty( $section['body'] ) ) {
			foreach ( $section['body'] as $paragraph ) {
				$html .= '<p>' . gabochie_value_thesis_bold_html( $paragraph ) . '</p>';
			}
		}
		if ( ! empty( $section['list'] ) ) {
			$html .= '<ul>';
			foreach ( $section['list'] as $item ) {
				$html .= '<li>' . gabochie_value_thesis_bold_html( $item ) . '</li>';
			}
			$html .= '</ul>';
		}
		if ( ! empty( $section['quote'] ) ) {
			$html .= '<blockquote>' . gabochie_value_thesis_bold_html( $section['quote'] ) . '</blockquote>';
		}
		if ( ! empty( $section['after'] ) ) {
			foreach ( $section['after'] as $paragraph ) {
				$html .= '<p>' . gabochie_value_thesis_bold_html( $paragraph ) . '</p>';
			}
		}
	}

	return $html;
}

/* ================================================================== */
/*  Pure-PHP PDF engine                                                */
/* ================================================================== */

/**
 * Minimal A4 PDF builder with vector chart primitives.
 *
 * Coordinates are page-relative with the ORIGIN at the TOP-LEFT; the engine
 * flips to PDF's bottom-left coordinate system when emitting operators.
 *
 * @package Gabochie_Value
 */
class Gabochie_Thesis_Pdf {

	const PAGE_W = 595.28;
	const PAGE_H = 841.89;
	const MARGIN = 48;
	const FOOTER_H = 34;
	const FONT_REGULAR = 'F1';
	const FONT_BOLD = 'F2';
	const FONT_ITALIC = 'F3';
	const FONT_BOLD_ITALIC = 'F4';

	/**
	 * Content stream per page (PDF operator text).
	 *
	 * @var array
	 */
	private $pages = array();

	/**
	 * Current font key.
	 *
	 * @var string
	 */
	private $font = self::FONT_REGULAR;

	/**
	 * Current font size (pt).
	 *
	 * @var float
	 */
	private $size = 10;

	/**
	 * Current fill colour as 'r g b' string.
	 *
	 * @var string
	 */
	private $color = '0 0 0';

	/**
	 * Palette (RGB floats).
	 *
	 * @var array
	 */
	private $palette = array(
		'teal'  => '#0D5C52',
		'teal2' => '#0A4840',
		'gold'  => '#C9A96E',
		'ink'   => '#1C2B28',
		'mist'  => '#6B7C78',
		'line'  => '#D8E2DF',
		'cream' => '#FAF7F2',
		'white' => '#FFFFFF',
	);

	/**
	 * Constructor — initialise defaults. Pages are created by the renderer.
	 */
	public function __construct() {
		$this->set_color( 'ink' );
		$this->set_font( self::FONT_REGULAR );
		$this->set_size( 10 );
	}

	/**
	 * Begin a new page.
	 */
	public function new_page() {
		$this->pages[] = '';
	}

	/**
	 * Emit raw operator text onto the current page.
	 *
	 * @param string $ops PDF operators.
	 */
	public function emit( $ops ) {
		$index                        = count( $this->pages ) - 1;
		$this->pages[ $index ]       .= $ops;
	}

	/**
	 * Set the active fill colour from the named palette or hex value.
	 *
	 * @param string $name Palette key or '#rrggbb'.
	 */
	public function set_color( $name ) {
		$hex = isset( $this->palette[ $name ] ) ? $this->palette[ $name ] : $name;
		$this->color = $this->hex_to_rgb( $hex );
	}

	/**
	 * Convert a hex colour to 'r g b' floats.
	 *
	 * @param string $hex '#rrggbb'.
	 * @return string
	 */
	public function hex_to_rgb( $hex ) {
		$hex = ltrim( $hex, '#' );
		if ( 6 !== strlen( $hex ) ) {
			return '0 0 0';
		}
		$r = hexdec( substr( $hex, 0, 2 ) ) / 255;
		$g = hexdec( substr( $hex, 2, 2 ) ) / 255;
		$b = hexdec( substr( $hex, 4, 2 ) ) / 255;
		return number_format( $r, 3 ) . ' ' . number_format( $g, 3 ) . ' ' . number_format( $b, 3 );
	}

	/**
	 * Set the active font family.
	 *
	 * @param string $font One of the FONT_* constants.
	 */
	public function set_font( $font ) {
		$this->font = $font;
	}

	/**
	 * Set the active font size.
	 *
	 * @param float $size Points.
	 */
	public function set_size( $size ) {
		$this->size = (float) $size;
	}

	/**
	 * Write a single line of text at (x, y) — top-left origin.
	 *
	 * @param float  $x Left.
	 * @param float  $y Top of the text baseline block.
	 * @param string $text Text.
	 */
	public function text( $x, $y, $text ) {
		$pdf_y  = self::PAGE_H - $y;
		$color  = $this->color;
		$font   = $this->font;
		$size   = number_format( $this->size, 2 );
		$text   = $this->pdf_encode( $text );
		$this->emit( "BT /{$font} {$size} Tf {$color} rg {$x} {$pdf_y} Td ({$text}) Tj ET\n" );
	}

	/**
	 * Draw a filled rectangle.
	 *
	 * @param float  $x Left.
	 * @param float  $y Top.
	 * @param float  $w Width.
	 * @param float  $h Height.
	 * @param string $color Palette key or hex.
	 */
	public function rect( $x, $y, $w, $h, $color ) {
		$pdf_y = self::PAGE_H - $y - $h;
		$rgb   = $this->hex_to_rgb( $this->has_color( $color ) ? $this->palette[ $color ] : $color );
		$this->emit( "{$rgb} rg {$x} {$pdf_y} {$w} {$h} re f\n" );
	}

	/**
	 * Check whether a palette key exists.
	 *
	 * @param string $name Key.
	 * @return bool
	 */
	public function has_color( $name ) {
		return isset( $this->palette[ $name ] );
	}

	/**
	 * Draw a horizontal rule / line.
	 *
	 * @param float  $x1 Left.
	 * @param float  $y1 Top.
	 * @param float  $x2 Right.
	 * @param float  $weight Line width.
	 * @param string $color Palette key or hex.
	 */
	public function hline( $x1, $y1, $x2, $weight, $color ) {
		$pdf_y = self::PAGE_H - $y1;
		$rgb   = $this->hex_to_rgb( $this->has_color( $color ) ? $this->palette[ $color ] : $color );
		$this->emit( "{$rgb} RG {$weight} w {$x1} {$pdf_y} m {$x2} {$pdf_y} l S\n" );
	}

	/**
	 * Estimate the on-page width of text in the active font/size.
	 * Uses a 600-unit-per-em Helvetica approximation (0.6 × size per char).
	 *
	 * @param string $text Text.
	 * @return float Width in points.
	 */
	public function measure( $text ) {
		$size = $this->size;
		$w    = 0.0;
		$len  = function_exists( 'mb_strlen' ) ? mb_strlen( $text ) : strlen( $text );
		for ( $i = 0; $i < $len; $i++ ) {
			$ch = function_exists( 'mb_substr' ) ? mb_substr( $text, $i, 1 ) : substr( $text, $i, 1 );
			if ( $ch === ' ' ) {
				$w += 0.27;
			} elseif ( $ch === 'M' || $ch === 'W' ) {
				$w += 0.85;
			} elseif ( $ch === 'i' || $ch === 'l' || $ch === 'j' || $ch === '.' || $ch === ',' || $ch === ':' ) {
				$w += 0.28;
			} elseif ( $ch === 'I' || $ch === 't' || $ch === 'f' ) {
				$w += 0.35;
			} else {
				$w += 0.58;
			}
		}
		return $w * $size;
	}

	/**
	 * Wrap text to fit a width. Splits on spaces; keeps words intact.
	 *
	 * @param string $text Text.
	 * @param float  $max_width Max line width.
	 * @return string[] Lines.
	 */
	public function wrap( $text, $max_width ) {
		$text = trim( $text );
		$text = (string) preg_replace( '/\s+/', ' ', $text );
		$words = explode( ' ', $text );
		$lines = array();
		$line  = '';

		foreach ( $words as $word ) {
			$candidate = '' === $line ? $word : $line . ' ' . $word;
			if ( $this->measure( $candidate ) <= $max_width || '' === $line ) {
				$line = $candidate;
				continue;
			}
			$lines[] = $line;
			$line    = $word;
		}
		if ( '' !== $line ) {
			$lines[] = $line;
		}
		return $lines;
	}

	/**
	 * Draw a wrapped paragraph; returns the Y position after the last line.
	 *
	 * @param float  $x Left.
	 * @param float  $y Top of the paragraph.
	 * @param string $text Text.
	 * @param float  $max_width Width.
	 * @param float  $leading Line height.
	 * @return float
	 */
	public function paragraph( $x, $y, $text, $max_width, $leading ) {
		foreach ( $this->wrap( $text, $max_width ) as $line ) {
			$this->text( $x, $y, $line );
			$y += $leading;
		}
		return $y;
	}

	/**
	 * Draw a vertical stack of text lines starting at (x, y).
	 *
	 * @param float  $x Left.
	 * @param float  $y Top.
	 * @param string[] $items Lines.
	 * @param float  $leading Line height.
	 * @return float
	 */
	public function lines( $x, $y, $items, $leading ) {
		foreach ( $items as $item ) {
			$this->text( $x, $y, $item );
			$y += $leading;
		}
		return $y;
	}

	/**
	 * Draw a horizontal bar-gauge with label + percentage value.
	 *
	 * @param float  $x Left.
	 * @param float  $y Top.
	 * @param float  $width Total gauge width.
	 * @param float  $height Bar height.
	 * @param int    $percent Fill percent (0-100).
	 * @param string $bar_color Fill colour.
	 * @param string $label_text Label (optional).
	 * @param string $value_text Value shown right of bar (optional).
	 * @return float Bottom of the gauge.
	 */
	public function gauge( $x, $y, $width, $height, $percent, $bar_color, $label_text = '', $value_text = '' ) {
		if ( '' !== $label_text ) {
			$this->paragraph( $x, $y, $label_text, $width, $height + 4 );
			$label_h = $this->estimate_paragraph_height( $label_text, $width, $height + 4 );
			$y      += $label_h + 1;
		}

		$track = $width * 1;
		$fill  = $track * ( max( 0, min( 100, (int) $percent ) ) / 100 );

		if ( '' !== $value_text ) {
			$this->measure( $value_text );
			$this->set_font( self::FONT_BOLD );
			$this->text( $x + $track + 6, $y, $value_text );
			$this->set_font( self::FONT_REGULAR );
		}

		$this->rect( $x, $y, $track, $height, 'line' );
		$this->rect( $x, $y, max( $fill, $height ), $height, $bar_color );
		return $y + $height;
	}

	/**
	 * Estimate wrapped paragraph height (lines × leading).
	 *
	 * @param string $text Text.
	 * @param float  $max_width Width.
	 * @param float  $leading Line height.
	 * @return float
	 */
	public function estimate_paragraph_height( $text, $max_width, $leading ) {
		return count( $this->wrap( $text, $max_width ) ) * $leading;
	}

	/**
	 * Draw a donut chart with a centre label.
	 *
	 * @param float  $cx Centre X.
	 * @param float  $cy Centre Y.
	 * @param float  $r_out Outer radius.
	 * @param float  $r_in  Inner radius.
	 * @param array<int, array{label:string,share:int,color:string}> $data Items: label/share/color (shares auto-normalised to 100).
	 * @return float
	 */
	public function donut( $cx, $cy, $r_out, $r_in, $data ) {
		$total = array_sum( wp_list_pluck( $data, 'share' ) );
		if ( $total <= 0 ) {
			return $cy;
		}
		$start = -M_PI_2;
		foreach ( $data as $item ) {
			$sweep     = ( 2 * M_PI * ( (float) $item['share'] ) ) / $total;
			$this->donut_segment( $cx, $cy, $r_out, $r_in, $start, $start + $sweep, $item['color'] );
			$start += $sweep;
		}
		return $cy;
	}

	/**
	 * Draw a single donut segment as a filled annular polygon.
	 *
	 * @param float  $cx Centre X.
	 * @param float  $cy Centre Y.
	 * @param float  $r_out Outer radius.
	 * @param float  $r_in Inner radius.
	 * @param float  $a0 Start angle (radians).
	 * @param float  $a1 End angle (radians).
	 * @param string $color Hex fill.
	 */
	public function donut_segment( $cx, $cy, $r_out, $r_in, $a0, $a1, $color ) {
		$steps = max( 8, (int) round( abs( $a1 - $a0 ) / ( 2 * M_PI ) * 64 ) );
		$pts   = array();

		for ( $i = 0; $i <= $steps; $i++ ) {
			$ang = $a0 + ( $a1 - $a0 ) * $i / $steps;
			$x_t = $cx + cos( $ang ) * $r_out;
			$y_t = $cy + sin( $ang ) * $r_out;
			$pts[] = array( $x_t, $y_t );
		}
		for ( $i = $steps; $i >= 0; $i-- ) {
			$ang = $a0 + ( $a1 - $a0 ) * $i / $steps;
			$pt  = array( $cx + cos( $ang ) * $r_in, $cy + sin( $ang ) * $r_in );
			$pts[] = $pt;
		}

		$rgb = $this->hex_to_rgb( $color );
		$ops = "{$rgb} rg\n";
		foreach ( $pts as $idx => $pt ) {
			$op  = 0 === $idx ? 'm' : 'l';
			$pdf_y = self::PAGE_H - $pt[1];
			$ops .= number_format( $pt[0], 2 ) . ' ' . number_format( $pdf_y, 2 ) . " {$op}\n";
		}
		$ops .= "h f\n";
		$this->emit( $ops );
	}

	/**
	 * Draw a multi-segment pipeline bar (equal-width phases).
	 *
	 * @param float  $x Left.
	 * @param float  $y Top.
	 * @param float  $width Total width.
	 * @param float  $height Bar height.
	 * @param string[] $phases Phase labels.
	 * @param string $color Accent colour.
	 * @return float Below the bar (rows of labels).
	 */
	public function pipeline( $x, $y, $width, $height, $phases, $color ) {
		$rgb  = $this->hex_to_rgb( $color );
		$seg  = $width / count( $phases );
		$ops  = '';
		foreach ( $phases as $i => $phase ) {
			$seg_x = $x + $i * $seg;
			$pdf_y = self::PAGE_H - $y - $height;
			$fill  = ( $i % 2 ) ? '0.92 0.94 0.93' : $rgb;
			$ops  .= "{$fill} rg {$seg_x} {$pdf_y} {$seg} {$height} re f\n";
		}
		$this->emit( $ops );

		$label_y = $y + $height + 9;
		foreach ( $phases as $i => $phase ) {
			$this->set_size( 6.1 );
			$this->set_color( 'mist' );
			$text = strtoupper( $phase );
			$tw   = $this->measure( $text );
			$cx   = $x + $i * $seg + ( $seg - $tw ) / 2;
			$this->text( max( $cx, self::MARGIN ), $label_y, $text );
		}
		$this->set_size( 10 );
		$this->set_color( 'ink' );
		return $label_y + 12;
	}

	/**
	 * Strip **bold** markers for the PDF layout.
	 *
	 * @param string $text Raw text.
	 * @return string
	 */
	public function strip_bold( $text ) {
		return (string) preg_replace( '/\*\*(.+?)\*\*/', '$1', $text );
	}

	/**
	 * Encode text for the PDF content stream (WinAnsi) and escape it.
	 *
	 * @param string $text UTF-8 text.
	 * @return string Escaped WinAnsi bytes.
	 */
	public function pdf_encode( $text ) {
		$text = $this->strip_bold( $text );
		if ( function_exists( 'mb_convert_encoding' ) ) {
			$text = (string) mb_convert_encoding( $text, 'Windows-1252', 'UTF-8' );
		}
		$text = str_replace( array( '\\', '(', ')' ), array( '\\\\', '\\(', '\\)' ), $text );
		return $text;
	}

	/**
	 * Assemble and return the full PDF binary.
	 *
	 * @return string
	 */
	public function output() {
		$num_pages = count( $this->pages );
		$objects   = array();
		$content_objs = array();

		// Object numbering: 1 catalog, 2 pages, then page objects, then fonts, then content streams.
		$catalog_id = 1;
		$pages_id   = 2;
		$page_start = 3;
		$font_start = $page_start + $num_pages;
		$stream_start = $font_start + 4;

		$kids = array();
		$page_ids = array();
		$stream_ids = array();
		for ( $p = 0; $p < $num_pages; $p++ ) {
			$page_ids[] = $page_start + $p;
			$stream_ids[] = $stream_start + $p;
		}
		foreach ( $page_ids as $pid ) {
			$kids[] = "{$pid} 0 R";
		}

		$objects[ $catalog_id ] = "<< /Type /Catalog /Pages {$pages_id} 0 R >>";
		$objects[ $pages_id ]   = '<< /Type /Pages /Kids [' . implode( ' ', $kids ) . "] /Count {$num_pages} >>";

		for ( $p = 0; $p < $num_pages; $p++ ) {
			$fonts_ref = '<< /F1 ' . ( $font_start + 0 ) . ' 0 R /F2 ' . ( $font_start + 1 ) . ' 0 R /F3 ' . ( $font_start + 2 ) . ' 0 R /F4 ' . ( $font_start + 3 ) . ' 0 R >>';
			$objects[ $page_ids[ $p ] ] = "<< /Type /Page /Parent {$pages_id} 0 R /MediaBox [0 0 " . self::PAGE_W . ' ' . self::PAGE_H . "] /Resources << /Font {$fonts_ref} >> /Contents " . $stream_ids[ $p ] . ' 0 R >>';
		}

		$objects[ $font_start + 0 ] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
		$objects[ $font_start + 1 ] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
		$objects[ $font_start + 2 ] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Oblique /Encoding /WinAnsiEncoding >>';
		$objects[ $font_start + 3 ] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-BoldOblique /Encoding /WinAnsiEncoding >>';

		for ( $p = 0; $p < $num_pages; $p++ ) {
			$content = $this->pages[ $p ];
			$length  = strlen( $content );
			$objects[ $stream_start + $p ] = "<< /Length {$length} >>\nstream\n{$content}\nendstream";
		}

		$pdf  = "%PDF-1.4\n";
		$offsets = array();
		ksort( $objects );
		foreach ( $objects as $id => $body ) {
			$offsets[ $id ] = strlen( $pdf );
			$pdf .= "{$id} 0 obj\n{$body}\nendobj\n";
		}

		$xref_start = strlen( $pdf );
		$object_count = count( $objects );
		$pdf .= "xref\n0 " . ( $object_count + 1 ) . "\n";
		$pdf .= "0000000000 65535 f \n";
		for ( $i = 1; $i <= $object_count; $i++ ) {
			$pdf .= sprintf( "%010d 00000 n \n", isset( $offsets[ $i ] ) ? $offsets[ $i ] : 0 );
		}
		$pdf .= "trailer\n<< /Size " . ( count( $objects ) + 1 ) . " /Root {$catalog_id} 0 R >>\n";
		$pdf .= "startxref\n{$xref_start}\n%%EOF";

		return $pdf;
	}
}

/* ================================================================== */
/*  Conversion-focused thesis PDF document                             */
/* ================================================================== */

/**
 * Generate the branded thesis PDF document as a binary string.
 *
 * @param int $reader_number Personal founding-wave number for this reader (0 = generic).
 * @return string PDF bytes.
 */
function gabochie_value_thesis_pdf( $reader_number = 0 ) {
	$data    = gabochie_value_thesis_dataset();
	$pdf     = new Gabochie_Thesis_Pdf();

	/** @var array<string,mixed> $founding */
	$founding = gabochie_value_thesis_founding_status();
	$offer    = gabochie_value_thesis_founding_offer();

	$page_w  = Gabochie_Thesis_Pdf::PAGE_W;
	$page_h  = Gabochie_Thesis_Pdf::PAGE_H;
	$m       = Gabochie_Thesis_Pdf::MARGIN;
	$cw      = $page_w - 2 * $m;

	/* ---------- Page 0: Cover ---------- */
	$pdf->new_page();
	$y = 40;

	// Brand header.
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
	$pdf->set_size( 13 );
	$pdf->set_color( 'teal' );
	$pdf->text( $m, $y, strtoupper( $data['brand'][0] ) );
	$pdf->set_size( 8 );
	$pdf->set_color( 'mist' );
	$pdf->text( $m, $y + 16, $data['brand'][1] );
	$pdf->hline( $m, $y + 28, $page_w - $m, 0.8, 'line' );

	// Hero.
	$y += 54;
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
	$pdf->set_size( 27 );
	$pdf->set_color( 'ink' );
	$pdf->text( $m, $y, $data['title'] );
	$y += 34;
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_ITALIC );
	$pdf->set_size( 13 );
	$pdf->set_color( 'teal' );
	$pdf->text( $m, $y, $data['subtitle'] );
	$y += 24;

	// Three promise stat cards.
	foreach ( $data['stats'] as $i => $stat ) {
		$card_w = ( $cw - 2 * 10 ) / 3;
		$card_x = $m + $i * ( $card_w + 10 );
		$pdf->rect( $card_x, $y, $card_w, 86, 'white' );
		$pdf->rect( $card_x, $y, $card_w, 4, 'gold' );
		$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
		$pdf->set_size( 22 );
		$pdf->set_color( 'teal' );
		$pdf->text( $card_x + 12, $y + 18, $stat['value'] );
		$pdf->set_font( Gabochie_Thesis_Pdf::FONT_REGULAR );
		$pdf->set_size( 7.4 );
		$pdf->set_color( 'mist' );
		$pdf->paragraph( $card_x + 12, $y + 42, $stat['label'], $card_w - 24, 9.2 );
	}
	$y += 100;

	// "What's inside" list.
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
	$pdf->set_size( 12 );
	$pdf->set_color( 'ink' );
	$pdf->text( $m, $y, 'What is inside this briefing' );
	$y += 14;
	foreach ( $data['learn'] as $item ) {
		$pdf->set_color( 'gold' );
		$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
		$pdf->text( $m, $y + 1, '›' );
		$pdf->set_color( 'ink' );
		$pdf->set_font( Gabochie_Thesis_Pdf::FONT_REGULAR );
		$pdf->text( $m + 16, $y, $item );
		$y += 16;
	}
	$y += 8;

	// Report meta.
	$pdf->hline( $m, $y, $page_w - $m, 0.8, 'line' );
	$y += 12;
	$pdf->set_size( 8.4 );
	$pdf->set_color( 'mist' );
	$pdf->text( $m, $y, 'Gabochie Design Thesis · 2026 · Free report for prospective clients' );
	$pdf->text( $m, $y + 14, 'Prepared by ' . $data['brand'][0] . ' — ' . home_url( '/' ) );
	$y += 34;

	// Founding-offer urgency band.
	$pdf->rect( $m, $y, $cw, 52, 'teal' );
	if ( $founding['remaining'] > 0 ) {
		$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
		$pdf->set_size( 11 );
		$pdf->set_color( 'white' );
		$pdf->text( $m + 16, $y + 14, $offer['headline'] );
		$pdf->set_font( Gabochie_Thesis_Pdf::FONT_REGULAR );
		$pdf->set_size( 8.8 );
		$pdf->set_color( 'cream' );
		$offer_text = $offer['line'] . ' ';
		$offer_text .= ( $reader_number > 0 ) ? $offer['who'] . '#' . $reader_number . '.' : 'The founding wave is open right now.';
		$pdf->paragraph( $m + 16, $y + 30, $offer_text, $cw - 32, 12 );
	} else {
		$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
		$pdf->set_size( 11 );
		$pdf->set_color( 'white' );
		$pdf->text( $m + 16, $y + 14, 'Founding wave filled — read the full thesis now.' );
		$pdf->set_font( Gabochie_Thesis_Pdf::FONT_REGULAR );
		$pdf->set_size( 8.8 );
		$pdf->set_color( 'cream' );
		$pdf->text( $m + 16, $y + 30, 'Standard access is always free.' );
	}

	/* ---------- Page 1: Evidence + visualisations ---------- */
	$pdf->new_page();
	$y = 56;

	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
	$pdf->set_size( 16 );
	$pdf->set_color( 'ink' );
	$pdf->text( $m, $y, 'The evidence in one page' );
	$y += 26;

	// Row: stall gauge + donut side by side.
	$left_w  = $cw * 0.52;
	$right_w = $cw * 0.48 - 20;

	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
	$pdf->set_size( 10.5 );
	$pdf->set_color( 'teal' );
	$pdf->text( $m, $y, 'Where projects stall' );
	$y += 16;
	$y = $pdf->gauge( $m, $y, $left_w, 12, $data['stall']['percent'], 'teal', '', $data['stall']['percent'] . '%' );
	$y += 6;
	$pdf->set_size( 7.6 );
	$pdf->set_color( 'mist' );
	$pdf->paragraph( $m, $y, $data['stall']['label'], $left_w - 50, 9 );
	$pdf->set_size( 6.6 );
	$pdf->paragraph( $m, $y + 24, $data['stall']['note'], $left_w, 8 );

	// Donut: three barriers.
	$cx = $m + $left_w + 30;
	$cy = 118;
	$pdf->donut( $cx, $cy, 40, 20, $data['barriers'] );
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
	$pdf->set_size( 15 );
	$pdf->set_color( 'ink' );
	$pdf->text( $cx - 8, $cy - 5, '3×' );
	$pdf->set_size( 6.2 );
	$pdf->set_color( 'mist' );
	$pdf->text( $cx - 17, $cy + 9, 'THE BARRIER' );

	// Donut legend.
	$ly = 168;
	foreach ( $data['barriers'] as $item ) {
		$pdf->rect( $m + $left_w + 30, $ly, 7, 7, $item['color'] );
		$pdf->set_size( 6.6 );
		$pdf->set_color( 'ink' );
		$pdf->text( $m + $left_w + 42, $ly, $item['label'] . '  ·  ' . $item['share'] . '%' );
		$ly += 11;
	}

	// Row 2: housing deficit band.
	$y = 172;
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
	$pdf->set_size( 10.5 );
	$pdf->set_color( 'teal' );
	$pdf->text( $m, $y, 'The housing gap' );
	$y += 16;
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
	$pdf->set_size( 26 );
	$pdf->set_color( 'teal' );
	$pdf->text( $m, $y, $data['deficit']['value'] );
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_REGULAR );
	$pdf->set_size( 7.8 );
	$pdf->set_color( 'mist' );
	$pdf->paragraph( $m + 62, $y + 10, $data['deficit']['label'], $cw - 62, 9.4 );
	$pdf->set_size( 6.6 );
	$pdf->set_color( 'mist' );
	$pdf->paragraph( $m + 62, $y + 34, $data['deficit']['note'], $cw - 62, 8 );

	// Row 3: 14-stage pipeline.
	$y += 62;
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
	$pdf->set_size( 10.5 );
	$pdf->set_color( 'teal' );
	$pdf->text( $m, $y, 'The 14-stage delivery pipeline' );
	$y += 14;
	if ( ! empty( $data['pipeline']['phases'] ) ) {
		$pdf->pipeline( $m, $y, $cw, 16, $data['pipeline']['phases'], 'teal' );
	}

	$y += 36;
	$pdf->hline( $m, $y, $page_w - $m, 0.8, 'line' );
	$y += 12;
	$pdf->set_size( 8.4 );
	$pdf->set_color( 'mist' );
	$pdf->paragraph( $m, $y, 'Every stalled project is an information problem. None of the leaks above is solved by more money alone — each is closed by making the professional delivery system visible to the developer.', $cw, 11 );

	/* ---------- Pages 2+: Thesis body ---------- */
	foreach ( $data['sections'] as $section ) {
		$pdf->new_page();
		$y = 56;

		// Pre-compute the section height to decide if it needs two pages.
		$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
		$pdf->set_size( 15 );
		$pdf->set_color( 'teal' );
		$y = $pdf->paragraph( $m, $y, $pdf->strip_bold( $section['heading'] ), $cw, 19 );
		$y += 6;

		$pdf->set_font( Gabochie_Thesis_Pdf::FONT_REGULAR );
		$pdf->set_size( 9.6 );
		$pdf->set_color( 'ink' );

		if ( ! empty( $section['body'] ) ) {
			foreach ( $section['body'] as $paragraph ) {
				$y = gabochie_value_thesis_safe_paragraph( $pdf, $m, $y, $paragraph, $cw, 13.6, $page_h );
				$y += 4;
			}
		}

		if ( ! empty( $section['list'] ) ) {
			foreach ( $section['list'] as $item ) {
				$y = gabochie_value_thesis_safe_list_item( $pdf, $m, $y, $item, $cw, $page_h );
				$y += 2;
			}
		}

		if ( ! empty( $section['quote'] ) ) {
			$y = gabochie_value_thesis_safe_quote( $pdf, $m, $y, $section['quote'], $cw, $page_h );
		}

		if ( ! empty( $section['after'] ) ) {
			foreach ( $section['after'] as $paragraph ) {
				$y = gabochie_value_thesis_safe_paragraph( $pdf, $m, $y, $paragraph, $cw, 13.6, $page_h );
				$y += 4;
			}
		}
	}

	/* ---------- Final page: conversion ---------- */
	$pdf->new_page();
	$y = 64;

	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
	$pdf->set_size( 18 );
	$pdf->set_color( 'ink' );
	$pdf->text( $m, $y, 'What you do next decides who moves.' );
	$y += 26;

	/** @var array<int, array{n:string,t:string,d:string,u:string}> $steps */
	$steps = array(
		array(
			'n' => '1',
			't' => 'Get your free Development Readiness Score',
			'd' => 'Eight dimensions, five minutes, straight answers. Know where your project stands before you spend a cedi.',
			'u' => '/development-quiz/',
		),
		array(
			'n' => '2',
			't' => 'Estimate your build cost',
			'd' => 'Market-based cost model for Ghana, adjusted for region, type and specification.',
			'u' => '/building-cost-calculator/',
		),
		array(
			'n' => '3',
			't' => 'See the plans that fit your stage',
			'd' => 'Free Lead, Active Client and Premium — the delivery system, not just a PDF.',
			'u' => '/membership-levels/',
		),
		array(
			'n' => '4',
			't' => 'Ask the community',
			'd' => 'Real answers from people actually building in Ghana, right now.',
			'u' => '/community/',
		),
	);

	foreach ( $steps as $step ) {
		$pdf->rect( $m, $y, $cw, 54, 'cream' );
		$pdf->rect( $m, $y, 5, 54, 'teal' );
		$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
		$pdf->set_size( 16 );
		$pdf->set_color( 'gold' );
		$pdf->text( $m + 18, $y + 16, $step['n'] );
		$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
		$pdf->set_size( 10 );
		$pdf->set_color( 'ink' );
		$pdf->text( $m + 40, $y + 14, $step['t'] );
		$pdf->set_font( Gabochie_Thesis_Pdf::FONT_REGULAR );
		$pdf->set_size( 8.2 );
		$pdf->set_color( 'mist' );
		$pdf->paragraph( $m + 40, $y + 26, $step['d'], $cw - 60, 9.8 );
		$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
		$pdf->set_size( 7.6 );
		$pdf->set_color( 'teal' );
		$pdf->text( $m + 40, $y + 46, home_url( $step['u'] ) );
		$y += 58;
	}

	$y += 4;
	$pdf->rect( $m, $y, $cw, 62, 'teal' );
	if ( $founding['remaining'] > 0 ) {
		$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
		$pdf->set_size( 12 );
		$pdf->set_color( 'white' );
		$pdf->text( $m + 16, $y + 14, $offer['headline'] );
		$pdf->set_font( Gabochie_Thesis_Pdf::FONT_REGULAR );
		$pdf->set_size( 8.8 );
		$pdf->set_color( 'cream' );
		$final_line = ( $reader_number > 0 ) ? $offer['who'] . '#' . $reader_number . ' — ' : '';
		$final_line .= $founding['remaining'] . ' of ' . $founding['total'] . ' founding places remain.';
		$pdf->paragraph( $m + 16, $y + 30, $final_line, $cw - 32, 12 );
	} else {
		$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
		$pdf->set_size( 12 );
		$pdf->set_color( 'white' );
		$pdf->text( $m + 16, $y + 14, 'The founding wave is full.' );
		$pdf->set_font( Gabochie_Thesis_Pdf::FONT_REGULAR );
		$pdf->set_size( 8.8 );
		$pdf->set_color( 'cream' );
		$pdf->text( $m + 16, $y + 30, 'Standard access is free — and stays free.' );
	}
	$y += 74;

	$pdf->hline( $m, $y, $page_w - $m, 0.8, 'line' );
	$y += 14;
	$pdf->set_size( 8 );
	$pdf->set_color( 'mist' );
	$pdf->text( $m, $y, $data['brand'][0] . ' — ' . home_url( '/' ) );
	$pdf->set_size( 7.2 );
	$pdf->paragraph( $m, $y + 12, 'This report is a summary prepared for prospective clients. Figures are estimates drawn from publicly reported ranges (GSS, Ministry of Works and Housing, market research) and are provided for orientation, not as professional advice.', $cw, 9 );

	return $pdf->output();
}

/**
 * Write the generated thesis PDF to a temporary file for attachment.
 *
 * @param int $reader_number Founding-wave number for this reader.
 * @return string|false Absolute path on success, false on failure.
 */
function gabochie_value_thesis_write_pdf( $reader_number = 0 ) {
	$dir  = wp_upload_dir();
	$path = isset( $dir['path'] ) ? (string) $dir['path'] : '';
	if ( '' === $path ) {
		return false;
	}

	$name = 'gabochie-ghana-development-execution-gap-' . gmdate( 'Ymd' ) . '-' . wp_rand( 100, 999 ) . '.pdf';
	$file = trailingslashit( $path ) . $name;
	$pdf  = gabochie_value_thesis_pdf( $reader_number );
	$ok   = file_put_contents( $file, $pdf );

	return ( false === $ok ) ? false : $file;
}

/**
 * Render a paragraph safely across a page boundary (creates a new page when
 * the remaining space is insufficient).
 *
 * @param Gabochie_Thesis_Pdf $pdf PDF engine.
 * @param float               $x Left.
 * @param float               $y Top.
 * @param string              $text Paragraph text (may hold **bold** markers).
 * @param float               $max_width Width.
 * @param float               $leading Line height.
 * @param float               $page_h Page height.
 * @return float Y after the paragraph.
 */
function gabochie_value_thesis_safe_paragraph( $pdf, $x, $y, $text, $max_width, $leading, $page_h ) {
	$clean = $pdf->strip_bold( $text );
	$lines = $pdf->wrap( $clean, $max_width );
	foreach ( $lines as $line ) {
		if ( $y > $page_h - 70 ) {
			$pdf->new_page();
			$y = 56;
		}
		$pdf->text( $x, $y, $line );
		$y += $leading;
	}
	return $y;
}

/**
 * Render a list item (with a leading bullet) across page boundaries.
 *
 * @param Gabochie_Thesis_Pdf $pdf PDF engine.
 * @param float               $x Left.
 * @param float               $y Top.
 * @param string              $item List item (may hold **bold** markers).
 * @param float               $max_width Width.
 * @param float               $page_h Page height.
 * @return float Y after the item.
 */
function gabochie_value_thesis_safe_list_item( $pdf, $x, $y, $item, $max_width, $page_h ) {
	$clean = $pdf->strip_bold( $item );
	$pdf->set_color( 'gold' );
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
	$pdf->text( $x, $y + 1, '•' );
	$pdf->set_color( 'ink' );
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_REGULAR );

	$bullet_w = 12;
	$lines    = $pdf->wrap( $clean, $max_width - $bullet_w );
	foreach ( $lines as $i => $line ) {
		$lx = ( 0 === $i ) ? $x + $bullet_w : $x + $bullet_w;
		if ( $y > $page_h - 70 ) {
			$pdf->new_page();
			$y = 56;
		}
		$pdf->text( $lx, $y, $line );
		$y += 12.6;
	}
	return $y;
}

/**
 * Render a pull-quote band across page boundaries.
 *
 * @param Gabochie_Thesis_Pdf $pdf PDF engine.
 * @param float               $x Left.
 * @param float               $y Top.
 * @param string              $quote Quote text (may hold **bold** markers).
 * @param float               $max_width Width.
 * @param float               $page_h Page height.
 * @return float Y after the quote.
 */
function gabochie_value_thesis_safe_quote( $pdf, $x, $y, $quote, $max_width, $page_h ) {
	$clean = $pdf->strip_bold( $quote );
	$y    += 6;
	if ( $y > $page_h - 70 ) {
		$pdf->new_page();
		$y = 56;
	}
	$pdf->rect( $x, $y, $max_width, 4, 'gold' );
	$y += 10;
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_ITALIC );
	$pdf->set_size( 10.4 );
	$pdf->set_color( 'teal2' );
	$y = $pdf->paragraph( $x + 14, $y, $clean, $max_width - 28, 14.4 );
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_REGULAR );
	$pdf->set_size( 9.6 );
	$pdf->set_color( 'ink' );
	return $y + 6;
}

/* ================================================================== */
/*  Founding-wave urgency (data-driven counter)                        */
/* ================================================================== */

/**
 * Founding-wave configuration: how many founding places exist, how many are
 * gone, and the personalised marketing line for the current reader.
 *
 * @return array{headline:string,line:string,who:string}
 */
function gabochie_value_thesis_founding_offer() {
	return array(
		'headline' => 'Founding readers move first.',
		'line'     => 'Claim one of the founding places on the paid Development Intelligence system and lock the founding rate — before the general wave.',
		'who'      => 'You are founding reader ',
	);
}

/**
 * Read the founding wave counters (data-driven slots).
 *
 * @return array{total:int,used:int,remaining:int}
 */
function gabochie_value_thesis_founding_status() {
	$total = (int) get_option( 'gabochie_value_thesis_founding_total', 50 );
	$used  = (int) get_option( 'gabochie_value_thesis_founding_used', 0 );
	return array(
		'total'     => $total,
		'used'      => $used,
		'remaining' => max( 0, $total - $used ),
	);
}

/**
 * Reserve a founding place for a first-time reader. Idempotent per user.
 *
 * @param int $user_id WP user ID.
 * @return array{reserved:bool,number:int,total:int,remaining:int}
 */
function gabochie_value_thesis_founding_reserve( $user_id ) {
	$existing = (int) get_user_meta( $user_id, 'gabochie_value_thesis_founding_no', true );
	$status   = gabochie_value_thesis_founding_status();

	if ( $existing > 0 ) {
		return array(
			'reserved'  => true,
			'number'    => $existing,
			'total'     => $status['total'],
			'remaining' => $status['remaining'],
		);
	}

	if ( $status['remaining'] <= 0 ) {
		return array(
			'reserved'  => false,
			'number'    => 0,
			'total'     => $status['total'],
			'remaining' => 0,
		);
	}

	$number = $status['used'] + 1;
	update_user_meta( $user_id, 'gabochie_value_thesis_founding_no', $number );
	update_option( 'gabochie_value_thesis_founding_used', $number );

	return array(
		'reserved'  => true,
		'number'    => $number,
		'total'     => $status['total'],
		'remaining' => $status['total'] - $number,
	);
}
// phpcs:enable Universal.Files.SeparateFunctionsFromOO