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
	$data     = gabochie_value_thesis_dataset();
	$html     = '';
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

	const PAGE_W           = 595.28;
	const PAGE_H           = 841.89;
	const MARGIN           = 48;
	const FOOTER_H         = 34;
	const FONT_REGULAR     = 'F1';
	const FONT_BOLD        = 'F2';
	const FONT_ITALIC      = 'F3';
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
		'teal'      => '#0D5C52',
		'teal2'     => '#0A4840',
		'teal_deep' => '#063B35',
		'gold'      => '#C9A96E',
		'gold_lite' => '#D8BE8E',
		'ink'       => '#1C2B28',
		'mist'      => '#6B7C78',
		'line'      => '#D8E2DF',
		'cream'     => '#FAF7F2',
		'white'     => '#FFFFFF',
		'paper'     => '#FEFCF8',
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
		$index                  = count( $this->pages ) - 1;
		$this->pages[ $index ] .= $ops;
	}

	/**
	 * Set the active fill colour from the named palette or hex value.
	 *
	 * @param string $name Palette key or '#rrggbb'.
	 */
	public function set_color( $name ) {
		$hex         = isset( $this->palette[ $name ] ) ? $this->palette[ $name ] : $name;
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
		$pdf_y = self::PAGE_H - $y;
		$color = $this->color;
		$font  = $this->font;
		$size  = number_format( $this->size, 2 );
		$text  = $this->pdf_encode( $text );
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
	 * Draw a filled or stroked polygon path.
	 *
	 * @param array<int, array{0:float,1:float}> $points Array of [x, y] pairs (top-left origin).
	 * @param string $color Palette key or hex.
	 * @param float  $weight Stroke width (stroke mode only).
	 * @param bool   $fill True to fill, false to stroke.
	 */
	public function polygon( $points, $color, $weight = 1.0, $fill = false ) {
		$rgb = $this->hex_to_rgb( $this->has_color( $color ) ? $this->palette[ $color ] : $color );
		$ops = 'q ' . ( $fill ? "{$rgb} rg" : "{$rgb} RG {$weight} w" ) . "\n";
		foreach ( $points as $i => $pt ) {
			$pdf_y = self::PAGE_H - (float) $pt[1];
			$op    = ( 0 === $i ) ? 'm' : 'l';
			$ops  .= number_format( (float) $pt[0], 2 ) . ' ' . number_format( $pdf_y, 2 ) . " {$op}\n";
		}
		$ops .= ( $fill ? "h f\n" : "h S\n" ) . "Q\n";
		$this->emit( $ops );
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
		$len  = strlen( $text );
		for ( $i = 0; $i < $len; ) {
			$b = ord( $text[ $i ] );
			if ( $b < 0x80 ) {
				$ch = $text[ $i ];
				++$i;
			} else {
				$nb = 0;
				if ( ( $b & 0xE0 ) === 0xC0 ) {
					$nb = 1;
				} elseif ( ( $b & 0xF0 ) === 0xE0 ) {
					$nb = 2;
				} elseif ( ( $b & 0xF8 ) === 0xF0 ) {
					$nb = 3;
				}
				if ( $nb > 0 && $i + $nb < $len ) {
					$ch = substr( $text, $i, $nb + 1 );
					$i += $nb + 1;
				} else {
					$ch = $text[ $i ];
					++$i;
				}
			}
			$w += $this->char_width( $ch );
		}
		return $w * $size;
	}

	/**
	 * Approximate Helvetica width factor for a single character (codepoint).
	 *
	 * @param string $ch Single UTF-8 character.
	 * @return float Width factor.
	 */
	private function char_width( $ch ) {
		if ( ' ' === $ch ) {
			return 0.27;
		}
		if ( 'M' === $ch || 'W' === $ch ) {
			return 0.85;
		}
		if ( false !== strpos( 'iljI:.1,|-–—/\'', $ch ) ) {
			return 0.28;
		}
		if ( false !== strpos( 'tf›•“‘”’()', $ch ) ) {
			return 0.35;
		}
		if ( ord( $ch ) >= 0x80 ) {
			return 0.5; // Other non-ASCII (cedi, arrows, etc.).
		}
		return 0.58;
	}

	/**
	 * Wrap text to fit a width. Splits on spaces; keeps words intact.
	 *
	 * @param string $text Text.
	 * @param float  $max_width Max line width.
	 * @return string[] Lines.
	 */
	public function wrap( $text, $max_width ) {
		$text  = trim( $text );
		$text  = (string) preg_replace( '/\s+/', ' ', $text );
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
	 * Split text into lines, preserving **bold** emphasis. Each returned line
	 * is an array of [word, isBold] pairs for draw_rich_line().
	 *
	 * @param string $text Raw text (may contain **bold** markers).
	 * @param float  $max_width Max line width.
	 * @return array<int,array<int,array{0:string,1:bool}>> Lines.
	 */
	public function rich_lines( $text, $max_width ) {
		$parts = preg_split( '/(\*\*.*?\*\*)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
		$words = array();
		foreach ( (array) $parts as $part ) {
			$bold  = ( '**' === substr( $part, 0, 2 ) && '**' === substr( $part, -2 ) );
			$chunk = $bold ? substr( $part, 2, -2 ) : $part;
			$chunk = (string) preg_replace( '/\s+/', ' ', trim( $chunk ) );
			foreach ( explode( ' ', $chunk ) as $word ) {
				if ( '' !== $word ) {
					$words[] = array( $word, $bold );
				}
			}
			if ( ! $bold && false !== strpos( $part, "\n" ) ) {
				$words[] = array( "\n", false );
			}
		}

		$lines = array();
		$cur   = array();
		$width = 0.0;
		$space = $this->measure( ' ' );
		foreach ( $words as $wd ) {
			if ( "\n" === $wd[0] ) {
				$lines[] = $cur;
				$cur     = array();
				$width   = 0.0;
				continue;
			}
			$add = $this->measure( $wd[0] ) + ( count( $cur ) ? $space : 0 );
			if ( count( $cur ) && $width + $add > $max_width ) {
				$lines[] = $cur;
				$cur     = array();
				$width   = $add - $space;
				$cur[]   = $wd;
			} else {
				$cur[] = $wd;
				$width += $add;
			}
		}
		if ( count( $cur ) ) {
			$lines[] = $cur;
		}
		return $lines;
	}

	/**
	 * Draw one rich line, switching to bold when a word is bold.
	 *
	 * @param float                            $x Left.
	 * @param float                            $y Top.
	 * @param array<int,array{0:string,1:bool}> $line Word runs.
	 * @return float Width consumed.
	 */
	public function draw_rich_line( $x, $y, $line ) {
		$cx    = $x;
		$space = $this->measure( ' ' );
		foreach ( $line as $wd ) {
			$this->set_font( $wd[1] ? self::FONT_BOLD : self::FONT_REGULAR );
			$this->text( $cx, $y, $wd[0] );
			$cx += $this->measure( $wd[0] ) + $space;
		}
		return $cx - $x;
	}

	/**
	 * Draw running header + footer furniture for an interior page.
	 *
	 * @param string $label Optional right-hand kicker label.
	 */
	public function furniture( $label = '' ) {
		$this->set_font( self::FONT_BOLD );
		$this->set_size( 6.3 );
		$this->set_color( 'mist' );
		$this->text( self::MARGIN, 34, 'GABOCHIE DESIGN · THESIS 2026' );
		if ( '' !== $label ) {
			$this->set_color( 'gold' );
			$lw = $this->measure( $label );
			$this->text( self::PAGE_W - self::MARGIN - $lw, 34, $label );
		}
		$this->hline( self::MARGIN, 42, self::PAGE_W - self::MARGIN, 0.6, 'line' );

		$this->hline( self::MARGIN, self::PAGE_H - 34, self::PAGE_W - self::MARGIN, 0.6, 'line' );
		$this->set_font( self::FONT_REGULAR );
		$this->set_size( 6.2 );
		$this->set_color( 'mist' );
		$this->text( self::MARGIN, self::PAGE_H - 28, 'DEVELOPMENT INTELLIGENCE PLATFORM — GHANA' );
		$rt = 'GABOCHIEDESIGN.COM';
		$rw = $this->measure( $rt );
		$this->text( self::PAGE_W - self::MARGIN - $rw, self::PAGE_H - 28, $rt );
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
			$sweep = ( 2 * M_PI * ( (float) $item['share'] ) ) / $total;
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
			$ang   = $a0 + ( $a1 - $a0 ) * $i / $steps;
			$x_t   = $cx + cos( $ang ) * $r_out;
			$y_t   = $cy + sin( $ang ) * $r_out;
			$pts[] = array( $x_t, $y_t );
		}
		for ( $i = $steps; $i >= 0; $i-- ) {
			$ang   = $a0 + ( $a1 - $a0 ) * $i / $steps;
			$pt    = array( $cx + cos( $ang ) * $r_in, $cy + sin( $ang ) * $r_in );
			$pts[] = $pt;
		}

		$rgb = $this->hex_to_rgb( $color );
		$ops = "{$rgb} rg\n";
		foreach ( $pts as $idx => $pt ) {
			$op    = 0 === $idx ? 'm' : 'l';
			$pdf_y = self::PAGE_H - $pt[1];
			$ops  .= number_format( $pt[0], 2 ) . ' ' . number_format( $pdf_y, 2 ) . " {$op}\n";
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
		$rgb = $this->hex_to_rgb( $color );
		$seg = $width / count( $phases );
		$ops = '';
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
	 * Uses a deterministic pure-PHP UTF-8 → Windows-1252 encoder (no mbstring
	 * dependency), so output is identical on every host. Characters with no
	 * cp1252 equivalent are transliterated (₵ → "GHS", → → "->", …) rather than
	 * left as raw UTF-8 bytes, which is what produced the mojibake.
	 *
	 * @param string $text UTF-8 text.
	 * @return string Escaped WinAnsi bytes.
	 */
	public function pdf_encode( $text ) {
		$text = $this->strip_bold( $text );
		$text = $this->utf8_to_winansi( $text );
		$text = str_replace( array( '\\', '(', ')' ), array( '\\\\', '\\(', '\\)' ), $text );
		return $text;
	}

	/**
	 * Map a Unicode code point to a Windows-1252 byte.
	 *
	 * @param int $cp Code point.
	 * @return int Byte value, or -1 when not representable in cp1252.
	 */
	private function cp1252_byte( $cp ) {
		if ( $cp < 0x80 ) {
			return $cp;
		}
		if ( $cp >= 0xA0 && $cp <= 0xFF ) {
			return $cp; // Latin-1 block is identical in cp1252.
		}
		$map = array(
			0x20AC => 0x80,
			0x201A => 0x82,
			0x0192 => 0x83,
			0x201E => 0x84,
			0x2026 => 0x85,
			0x2020 => 0x86,
			0x2021 => 0x87,
			0x02C6 => 0x88,
			0x2030 => 0x89,
			0x0160 => 0x8A,
			0x2039 => 0x8B,
			0x0152 => 0x8C,
			0x017D => 0x8E,
			0x2018 => 0x91,
			0x2019 => 0x92,
			0x201C => 0x93,
			0x201D => 0x94,
			0x2022 => 0x95,
			0x2013 => 0x96,
			0x2014 => 0x97,
			0x02DC => 0x98,
			0x2122 => 0x99,
			0x0161 => 0x9A,
			0x203A => 0x9B,
			0x0153 => 0x9C,
			0x017E => 0x9E,
			0x0178 => 0x9F,
		);
		return isset( $map[ $cp ] ) ? $map[ $cp ] : -1;
	}

	/**
	 * Deterministic pure-PHP UTF-8 → Windows-1252 encoder.
	 *
	 * @param string $text UTF-8 text.
	 * @return string WinAnsi bytes.
	 */
	private function utf8_to_winansi( $text ) {
		$translit = array(
			0x20B5 => 'GHS ', // ₵ cedi sign.
			0x20A6 => 'GHS ', // ₦ (cedi variants).
			0x2192 => '->',   // →
			0x2190 => '<-',
			0x2191 => '^',
			0x2193 => 'v',
			0x2264 => '<=',
			0x2265 => '>=',
			0x2212 => '-',    // − minus sign.
			0x2713 => 'ok',   // ✓
			0x2717 => 'x',    // ✗
		);

		$out = '';
		$len = strlen( $text );
		$i   = 0;
		while ( $i < $len ) {
			$b0 = ord( $text[ $i ] );
			if ( $b0 < 0x80 ) {
				$out .= $text[ $i ];
				++$i;
				continue;
			}

			$cp = 0;
			$nb = 0;
			if ( ( $b0 & 0xE0 ) === 0xC0 ) {
				$cp = $b0 & 0x1F;
				$nb = 1;
			} elseif ( ( $b0 & 0xF0 ) === 0xE0 ) {
				$cp = $b0 & 0x0F;
				$nb = 2;
			} elseif ( ( $b0 & 0xF8 ) === 0xF0 ) {
				$cp = $b0 & 0x07;
				$nb = 3;
			} else {
				$out .= '?';
				++$i;
				continue;
			}
			if ( $i + $nb >= $len ) {
				$out .= '?';
				++$i;
				continue;
			}

			$valid = true;
			for ( $k = 1; $k <= $nb; $k++ ) {
				$byte = ord( $text[ $i + $k ] );
				if ( ( $byte & 0xC0 ) !== 0x80 ) {
					$valid = false;
					break;
				}
				$cp = ( $cp << 6 ) | ( $byte & 0x3F );
			}
			if ( ! $valid ) {
				$out .= '?';
				++$i;
				continue;
			}
			$i += $nb + 1;

			$byte = $this->cp1252_byte( $cp );
			if ( $byte >= 0 ) {
				$out .= chr( $byte );
			} elseif ( isset( $translit[ $cp ] ) ) {
				$out .= $translit[ $cp ];
			} else {
				$out .= '?';
			}
		}
		return $out;
	}

	/**
	 * Assemble and return the full PDF binary.
	 *
	 * @return string
	 */
	public function output() {
		$num_pages    = count( $this->pages );
		$objects      = array();
		$content_objs = array();

		// Object numbering: 1 catalog, 2 pages, then page objects, then fonts, then content streams.
		$catalog_id   = 1;
		$pages_id     = 2;
		$page_start   = 3;
		$font_start   = $page_start + $num_pages;
		$stream_start = $font_start + 4;

		$kids       = array();
		$page_ids   = array();
		$stream_ids = array();
		for ( $p = 0; $p < $num_pages; $p++ ) {
			$page_ids[]   = $page_start + $p;
			$stream_ids[] = $stream_start + $p;
		}
		foreach ( $page_ids as $pid ) {
			$kids[] = "{$pid} 0 R";
		}

		$objects[ $catalog_id ] = "<< /Type /Catalog /Pages {$pages_id} 0 R >>";
		$objects[ $pages_id ]   = '<< /Type /Pages /Kids [' . implode( ' ', $kids ) . "] /Count {$num_pages} >>";

		for ( $p = 0; $p < $num_pages; $p++ ) {
			$fonts_ref                  = '<< /F1 ' . ( $font_start + 0 ) . ' 0 R /F2 ' . ( $font_start + 1 ) . ' 0 R /F3 ' . ( $font_start + 2 ) . ' 0 R /F4 ' . ( $font_start + 3 ) . ' 0 R >>';
			$objects[ $page_ids[ $p ] ] = "<< /Type /Page /Parent {$pages_id} 0 R /MediaBox [0 0 " . self::PAGE_W . ' ' . self::PAGE_H . "] /Resources << /Font {$fonts_ref} >> /Contents " . $stream_ids[ $p ] . ' 0 R >>';
		}

		$objects[ $font_start + 0 ] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
		$objects[ $font_start + 1 ] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
		$objects[ $font_start + 2 ] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Oblique /Encoding /WinAnsiEncoding >>';
		$objects[ $font_start + 3 ] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-BoldOblique /Encoding /WinAnsiEncoding >>';

		for ( $p = 0; $p < $num_pages; $p++ ) {
			$content                       = $this->pages[ $p ];
			$length                        = strlen( $content );
			$objects[ $stream_start + $p ] = "<< /Length {$length} >>\nstream\n{$content}\nendstream";
		}

		$pdf     = "%PDF-1.4\n";
		$offsets = array();
		ksort( $objects );
		foreach ( $objects as $id => $body ) {
			$offsets[ $id ] = strlen( $pdf );
			$pdf           .= "{$id} 0 obj\n{$body}\nendobj\n";
		}

		$xref_start   = strlen( $pdf );
		$object_count = count( $objects );
		$pdf         .= "xref\n0 " . ( $object_count + 1 ) . "\n";
		$pdf         .= "0000000000 65535 f \n";
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
	$data = gabochie_value_thesis_dataset();
	$pdf  = new Gabochie_Thesis_Pdf();

	/** @var array<string,mixed> $founding */
	$founding = gabochie_value_thesis_founding_status();
	$offer    = gabochie_value_thesis_founding_offer();

	$page_w = Gabochie_Thesis_Pdf::PAGE_W;
	$page_h = Gabochie_Thesis_Pdf::PAGE_H;
	$m      = Gabochie_Thesis_Pdf::MARGIN;
	$cw     = $page_w - 2 * $m;

	/* ---------- Page 0: Cover (premium design + direct-response copy) ---------- */
	$pdf->new_page();

	// Full-bleed paper wash, then the dark hero band + gold hairline strip.
	$pdf->rect( 0, 0, $page_w, $page_h, 'paper' );
	$pdf->rect( 0, 0, $page_w, 112, 'teal_deep' );
	$pdf->rect( 0, 112, $page_w, 4, 'gold' );

	// Double hexagon logo mark (brand motif), gold + gold_lite.
	$cx = 84;
	$cy = 56;
	$hex = array();
	for ( $k = 0; $k < 6; $k++ ) {
		$a     = deg2rad( 30 + 60 * $k );
		$hex[] = array( $cx + 13.5 * cos( $a ), $cy + 13.5 * sin( $a ) );
	}
	$pdf->polygon( $hex, 'gold', 1.2, false );
	$hex_i = array();
	for ( $k = 0; $k < 6; $k++ ) {
		$a       = deg2rad( 30 + 60 * $k );
		$hex_i[] = array( $cx + 7.4 * cos( $a ), $cy + 7.4 * sin( $a ) );
	}
	$pdf->polygon( $hex_i, 'gold_lite', 0.8, false );

	// Wordmark lockup (left).
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
	$pdf->set_size( 14.5 );
	$pdf->set_color( 'white' );
	$pdf->text( 110, 48, strtoupper( $data['brand'][0] ) );
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_REGULAR );
	$pdf->set_size( 6.4 );
	$pdf->set_color( 'cream' );
	$pdf->text( 110, 68, strtoupper( $data['brand'][1] ) );

	// Edition block (right).
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
	$pdf->set_size( 8 );
	$pdf->set_color( 'gold_lite' );
	$tag_a = 'THE DEVELOPMENT INTELLIGENCE PLATFORM';
	$pdf->text( $page_w - $m - $pdf->measure( $tag_a ), 48, $tag_a );
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_REGULAR );
	$pdf->set_size( 6.6 );
	$pdf->set_color( 'cream' );
	$tag_b = 'FREE BRIEFING · EDITION 2026';
	$pdf->text( $page_w - $m - $pdf->measure( $tag_b ), 68, $tag_b );

	// Kicker row.
	$y0 = 146;
	$pdf->rect( $m, $y0, 5, 5, 'gold' );
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
	$pdf->set_size( 8 );
	$pdf->set_color( 'teal' );
	$pdf->text( $m + 14, $y0, 'A FREE 5-MINUTE BRIEFING ON THE DEVELOPMENT EXECUTION GAP' );
	$y0 += 28;

	// Headline: two-tone, uppercase, curiosity + specificity.
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
	$pdf->set_size( 25 );
	$pdf->set_color( 'ink' );
	$pdf->text( $m, $y0, 'WHY 60% OF GHANAIAN' );
	$y0 += 30;
	$pdf->set_color( 'teal' );
	$pdf->text( $m, $y0, 'PROJECTS NEVER BREAK GROUND.' );
	$y0 += 16;
	$pdf->rect( $m, $y0, 46, 3, 'gold' );
	$y0 += 24;

	// Subhead: mechanism + benefit.
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_ITALIC );
	$pdf->set_size( 12.4 );
	$pdf->set_color( 'teal2' );
	$y0 = $pdf->paragraph(
		$m,
		$y0,
		'The three invisible barriers every stalled project shares — and the 14-stage system professional firms use to finish on time and on budget.',
		$cw,
		15.6
	);
	$y0 += 12;

	// Proof band: giant stat + mechanism + credential.
	$band_y = $y0;
	$pdf->rect( $m, $band_y, $cw, 100, 'teal_deep' );
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
	$pdf->set_size( 44 );
	$pdf->set_color( 'gold' );
	$pdf->text( $m + 20, $band_y + 34, '60%' );
	$mid_w = $cw - 128 - 108;
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
	$pdf->set_size( 10.2 );
	$pdf->set_color( 'white' );
	$pdf->paragraph( $m + 128, $band_y + 22, 'of development projects in Ghana stall before ground is ever broken.', $mid_w, 13.4 );
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_REGULAR );
	$pdf->set_size( 8.6 );
	$pdf->set_color( 'cream' );
	$pdf->paragraph( $m + 128, $band_y + 52, 'Not for lack of money — for lack of a clear delivery system.', $mid_w, 11.2 );
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
	$pdf->set_size( 6.8 );
	$pdf->set_color( 'gold_lite' );
	$pdf->paragraph( $m + 128, $band_y + 76, 'CLOSED ONE WAY: MAKING THE PROFESSIONAL SYSTEM VISIBLE TO YOU.', $mid_w, 9 );
	// Right credential chip.
	$rx = $page_w - $m - 104;
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
	$pdf->set_size( 16 );
	$pdf->set_color( 'gold' );
	$pdf->text( $rx, $band_y + 36, '#1' );
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_REGULAR );
	$pdf->set_size( 6.6 );
	$pdf->set_color( 'white' );
	$pdf->paragraph( $rx + 24, $band_y + 26, "West Africa's fastest-growing construction market.", 80, 8.6 );
	$y0 = $band_y + 118;

	// Inside-this-briefing header + benefit list.
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
	$pdf->set_size( 8 );
	$pdf->set_color( 'gold' );
	$pdf->text( $m, $y0, 'INSIDE THIS BRIEFING' );
	$hdr_w = $pdf->measure( 'INSIDE THIS BRIEFING' );
	$pdf->hline( $m + $hdr_w + 8, $y0 + 2.5, $page_w - $m, 0.8, 'line' );
	$y0 += 17;

	$cover_bullets = array(
		array(
			'lead' => 'The paradox.',
			'rest' => 'Ghana is building and stalling at the same time — where the growth leaks, in numbers you can act on.',
		),
		array(
			'lead' => 'The real barrier.',
			'rest' => 'Not capital. Information, coordination and visibility — the three failures behind every stalled project.',
		),
		array(
			'lead' => 'The 14-stage system.',
			'rest' => 'The delivery pipeline professional firms already use, drawn as a map you can follow.',
		),
		array(
			'lead' => 'Your next move.',
			'rest' => 'A five-minute Readiness Score that names exactly what your project needs next — free.',
		),
	);
	foreach ( $cover_bullets as $item ) {
		$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
		$pdf->set_size( 8.8 );
		$pdf->set_color( 'gold' );
		$pdf->text( $m, $y0 + 1, '›' );
		$pdf->set_color( 'ink' );
		$lead_w = $pdf->measure( $item['lead'] );
		$pdf->text( $m + 16, $y0, $item['lead'] );
		$pdf->set_font( Gabochie_Thesis_Pdf::FONT_REGULAR );
		$pdf->set_color( 'mist' );
		$pdf->paragraph( $m + 16 + $lead_w + 4, $y0, $item['rest'], $cw - 16 - $lead_w - 4, 11.6 );
		$y0 += 22;
	}
	$y0 += 6;

	// Personalised founding chip.
	$has_slot = $founding['remaining'] > 0;
	if ( $reader_number > 0 ) {
		$chip   = 'FOUNDING READER #' . $reader_number . ' OF ' . $founding['total'] . '  ·  LOCK THE FOUNDING RATE';
		$chip_c = 'ink';
		$chip_f = 'gold';
	} elseif ( $has_slot ) {
		$chip   = 'FOUNDING WAVE OPEN — ' . $founding['remaining'] . ' OF ' . $founding['total'] . ' LEFT';
		$chip_c = 'white';
		$chip_f = 'teal';
	} else {
		$chip   = 'FOUNDING WAVE FULL — ACCESS STAYS FREE';
		$chip_c = 'white';
		$chip_f = 'mist';
	}
	$notch  = ( 'gold' === $chip_f ) ? 'teal_deep' : 'gold';
	$chip_w = $pdf->measure( $chip ) + 30;
	$pdf->rect( $m, $y0, $chip_w, 22, $chip_f );
	$pdf->rect( $m, $y0, 4, 22, $notch );
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
	$pdf->set_size( 8.4 );
	$pdf->set_color( $chip_c );
	$pdf->text( $m + 14, $y0 + 6, $chip );
	$y0 += 32;

	// Direct-response P.S. line.
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_ITALIC );
	$pdf->set_size( 9 );
	$pdf->set_color( 'mist' );
	if ( $has_slot ) {
		$ps_line = ( $reader_number > 0 )
			? 'P.S. Founding places are capped at ' . $founding['total'] . '. Yours is held until the general wave opens — then the founding rate closes.'
			: 'P.S. Founding places are capped at ' . $founding['total'] . ' — ' . $founding['remaining'] . ' remain before the general wave opens.';
	} else {
		$ps_line = 'P.S. The founding wave is full — standard access stays free, always.';
	}
	$y0 = $pdf->paragraph( $m, $y0, $ps_line, $cw, 12 );
	$y0 += 16;

	// CTA band.
	$pdf->rect( $m, $y0, $cw, 46, 'teal' );
	$pdf->rect( $m, $y0, $cw, 3, 'gold' );
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
	$pdf->set_size( 10.6 );
	$pdf->set_color( 'white' );
	$pdf->text( $m + 16, $y0 + 11, 'YOUR NEXT MOVE — FREE' );
	$pdf->set_size( 11.4 );
	$pdf->set_color( 'gold_lite' );
	$pdf->text( $m + 16, $y0 + 25, 'Development Readiness Score' );
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_REGULAR );
	$pdf->set_size( 7.4 );
	$pdf->set_color( 'cream' );
	$pdf->text( $m + 16, $y0 + 39, 'gabochiedesign.com/development-quiz' );
	$chip2 = '5 MIN · 8 DIMENSIONS';
	$w2    = $pdf->measure( $chip2 ) + 22;
	$pdf->rect( $page_w - $m - $w2 - 14, $y0 + 9, $w2, 28, 'teal_deep' );
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
	$pdf->set_size( 8.4 );
	$pdf->set_color( 'gold_lite' );
	$pdf->text( $page_w - $m - $w2 - 14 + 11, $y0 + 19, $chip2 );
	$y0 += 62;

	// Cover footer.
	$pdf->hline( $m, $y0, $page_w - $m, 0.8, 'line' );
	$y0 += 12;
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_REGULAR );
	$pdf->set_size( 8 );
	$pdf->set_color( 'mist' );
	$pdf->text( $m, $y0, 'Gabochie Design · Development Intelligence Platform for Ghana' );
	$pdf->text( $m, $y0 + 12, 'Free briefing · Edition 2026 · Prepared for prospective clients — ' . home_url( '/' ) );

	/* ---------- Page 1: Evidence + visualisations ---------- */
	$pdf->new_page();
	$pdf->furniture( '01 · THE EVIDENCE' );
	$y = 58;

	// Kicker + heading.
	$pdf->rect( $m, $y, 5, 5, 'gold' );
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
	$pdf->set_size( 7.6 );
	$pdf->set_color( 'gold' );
	$pdf->text( $m + 13, $y, '01 · THE EVIDENCE' );
	$y += 22;
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
	$pdf->set_size( 18 );
	$pdf->set_color( 'teal_deep' );
	$pdf->text( $m, $y, 'The numbers behind the stall' );
	$y += 8;
	$pdf->rect( $m, $y, 46, 3, 'gold' );
	$y += 22;
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_REGULAR );
	$pdf->set_size( 9.6 );
	$pdf->set_color( 'mist' );
	$y = $pdf->paragraph( $m, $y, 'Three numbers capture the argument. Everything after this page is the mechanism for closing them.', $cw, 13.6 );
	$y += 12;

	// Two cream analytics panels.
	$panel_h = 146;
	$panel_w = ( $cw - 16 ) / 2;

	// Left panel: where projects stall.
	$pdf->rect( $m, $y, $panel_w, $panel_h, 'cream' );
	$pdf->rect( $m, $y, $panel_w, 3, 'gold' );
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
	$pdf->set_size( 9 );
	$pdf->set_color( 'teal' );
	$pdf->text( $m + 14, $y + 14, 'WHERE PROJECTS STALL' );
	$gx    = $m + 14;
	$gy    = $y + 40;
	$track = $panel_w - 28 - 46;
	$pdf->rect( $gx, $gy, $track, 8, 'line' );
	$pdf->rect( $gx, $gy, $track * 0.6, 8, 'teal' );
	$pdf->set_color( 'teal' );
	$pdf->text( $gx + $track + 8, $gy - 3, '60%' );
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_REGULAR );
	$pdf->set_size( 7.2 );
	$pdf->set_color( 'mist' );
	$pdf->paragraph( $gx, $gy + 16, $data['stall']['label'], $panel_w - 28, 9.2 );
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_ITALIC );
	$pdf->set_size( 6.6 );
	$pdf->paragraph( $gx, $gy + 50, $data['stall']['note'], $panel_w - 28, 8 );

	// Right panel: the three barriers (donut + legend).
	$rx = $m + $panel_w + 16;
	$pdf->rect( $rx, $y, $panel_w, $panel_h, 'cream' );
	$pdf->rect( $rx, $y, $panel_w, 3, 'gold' );
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
	$pdf->set_size( 9 );
	$pdf->set_color( 'teal' );
	$pdf->text( $rx + 14, $y + 14, 'THE THREE REAL BARRIERS' );
	$dcx = $rx + 60;
	$dcy = $y + 88;
	$pdf->donut( $dcx, $dcy, 30, 15, $data['barriers'] );
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
	$pdf->set_size( 11 );
	$pdf->set_color( 'ink' );
	$lw = $pdf->measure( '3×' );
	$pdf->text( $dcx - $lw / 2, $dcy - 5, '3×' );
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_REGULAR );
	$pdf->set_size( 5.6 );
	$pdf->set_color( 'mist' );
	$sw = $pdf->measure( 'THE BARRIER' );
	$pdf->text( $dcx - $sw / 2, $dcy + 9, 'THE BARRIER' );
	$ly = $y + 34;
	foreach ( $data['barriers'] as $item ) {
		$pdf->rect( $dcx + 46, $ly, 6, 6, $item['color'] );
		$pdf->set_font( Gabochie_Thesis_Pdf::FONT_REGULAR );
		$pdf->set_size( 6.6 );
		$pdf->set_color( 'ink' );
		$pdf->paragraph( $dcx + 56, $ly, $item['label'], $panel_w - 56 - 44, 8 );
		$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
		$pdf->set_color( 'teal' );
		$share_w = $pdf->measure( $item['share'] . '%' );
		$pdf->text( $rx + $panel_w - 12 - $share_w, $ly, $item['share'] . '%' );
		$ly += 24;
	}
	$y += $panel_h + 16;

	// Housing deficit band.
	$pdf->rect( $m, $y, $cw, 78, 'teal_deep' );
	$pdf->rect( $m, $y, $cw, 3, 'gold' );
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
	$pdf->set_size( 26 );
	$pdf->set_color( 'gold' );
	$pdf->text( $m + 20, $y + 28, $data['deficit']['value'] );
	$pdf->rect( $m + 168, $y + 12, 1.2, 54, 'gold_lite' );
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
	$pdf->set_size( 9 );
	$pdf->set_color( 'white' );
	$pdf->paragraph( $m + 186, $y + 16, $data['deficit']['label'], $cw - 186 - 16, 11.4 );
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_ITALIC );
	$pdf->set_size( 6.8 );
	$pdf->set_color( 'cream' );
	$pdf->paragraph( $m + 186, $y + 48, $data['deficit']['note'], $cw - 186 - 16, 8.6 );
	$y += 78 + 14;

	// Delivery pipeline strip: dark track + 14 gold stage ticks.
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
	$pdf->set_size( 11 );
	$pdf->set_color( 'teal_deep' );
	$pdf->text( $m, $y, 'The 14-stage delivery pipeline' );
	$y += 6;
	$pdf->rect( $m, $y, 30, 3, 'gold' );
	$y += 18;
	$pdf->rect( $m, $y, $cw, 18, 'teal_deep' );
	$amp  = 14;
	$tick = $cw / $amp;
	for ( $i = 0; $i < $amp; $i++ ) {
		$pdf->rect( $m + 4 + $i * $tick, $y + 3, 1.8, 12, 'gold' );
	}
	$pdf->rect( $m, $y + 18, $cw, 1, 'gold' );
	$seg = $cw / count( $data['pipeline']['phases'] );
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
	$pdf->set_size( 6.4 );
	$pdf->set_color( 'ink' );
	$ly = $y + 26;
	foreach ( $data['pipeline']['phases'] as $i => $phase ) {
		$phase_t = strtoupper( $phase );
		$pw      = $pdf->measure( $phase_t );
		$cx_t    = $m + $i * $seg + ( $seg - $pw ) / 2;
		$pdf->text( $cx_t, $ly, $phase_t );
	}
	$y = $ly + 14;

	// Key takeaway callout.
	$pdf->rect( $m, $y, $cw, 40, 'cream' );
	$pdf->rect( $m, $y, 4, 40, 'gold' );
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_ITALIC );
	$pdf->set_size( 9.4 );
	$pdf->set_color( 'teal2' );
	$pdf->paragraph( $m + 18, $y + 12, 'Every stalled project is an information problem — and none of the leaks above is closed by money alone.', $cw - 40, 13 );
	$y += 54;

	/* ---------- Pages 2+: Thesis body ---------- */
	$section_kickers = array(
		1 => '01 · THE GROWTH PARADOX',
		2 => '02 · THE TRUE BARRIER',
		3 => '03 · THE DATA',
		4 => '04 · THE SOLUTION',
		5 => '05 · THE INVITATION',
	);
	$section_index   = 0;
	foreach ( $data['sections'] as $section ) {
		$section_index++;
		$label = isset( $section_kickers[ $section_index ] ) ? $section_kickers[ $section_index ] : sprintf( '%02d · THESIS', $section_index );
		$pdf->new_page();
		$pdf->furniture( $label );
		$y = 58;

		// Kicker + heading block.
		$pdf->rect( $m, $y, 5, 5, 'gold' );
		$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
		$pdf->set_size( 7.6 );
		$pdf->set_color( 'gold' );
		$pdf->text( $m + 13, $y, $label );
		$y += 22;
		$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
		$pdf->set_size( 17 );
		$pdf->set_color( 'teal_deep' );
		$y  = $pdf->paragraph( $m, $y, $pdf->strip_bold( $section['heading'] ), $cw, 21 );
		$y += 6;
		$pdf->rect( $m, $y, 46, 3, 'gold' );
		$y += 20;

		if ( ! empty( $section['body'] ) ) {
			$pdf->set_font( Gabochie_Thesis_Pdf::FONT_REGULAR );
			$pdf->set_size( 9.8 );
			$pdf->set_color( 'ink' );
			foreach ( $section['body'] as $paragraph ) {
				$y  = gabochie_value_thesis_safe_paragraph( $pdf, $m, $y, $paragraph, $cw, 14, $page_h );
				$y += 4;
			}
		}

		if ( ! empty( $section['list'] ) ) {
			$pdf->set_color( 'ink' );
			$pdf->set_size( 9.4 );
			foreach ( $section['list'] as $item ) {
				$y  = gabochie_value_thesis_safe_list_item( $pdf, $m, $y, $item, $cw, $page_h );
				$y += 3;
			}
		}

		if ( ! empty( $section['quote'] ) ) {
			$y  = gabochie_value_thesis_safe_quote( $pdf, $m, $y, $section['quote'], $cw, $page_h );
			$y += 6;
		}

		if ( ! empty( $section['after'] ) ) {
			$pdf->set_font( Gabochie_Thesis_Pdf::FONT_REGULAR );
			$pdf->set_size( 9.8 );
			$pdf->set_color( 'ink' );
			foreach ( $section['after'] as $paragraph ) {
				$y  = gabochie_value_thesis_safe_paragraph( $pdf, $m, $y, $paragraph, $cw, 14, $page_h );
				$y += 4;
			}
		}
	}

	/* ---------- Final page: conversion ---------- */
	$pdf->new_page();
	$pdf->furniture( 'FINAL STEP' );
	$y = 64;

	$pdf->rect( $m, $y, 5, 5, 'gold' );
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
	$pdf->set_size( 7.6 );
	$pdf->set_color( 'gold' );
	$pdf->text( $m + 13, $y, 'YOUR FINAL STEP' );
	$y += 24;
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
	$pdf->set_size( 20 );
	$pdf->set_color( 'teal_deep' );
	$pdf->text( $m, $y, 'What you do next decides who moves.' );
	$y += 16;
	$pdf->rect( $m, $y, 46, 3, 'gold' );
	$y += 26;
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_REGULAR );
	$pdf->set_size( 9.6 );
	$pdf->set_color( 'mist' );
	$y = $pdf->paragraph( $m, $y, 'Four free tools. One decision. Start where your project is — every path below is a step on the same delivery system.', $cw, 13.8 );
	$y += 14;

	/** @var array<int, array{n:string,t:string,d:string,u:string}> $steps */
	$steps = array(
		array(
			'n' => '1',
			't' => 'Get your free Development Readiness Score',
			'd' => '8 dimensions, 5 minutes. Name your project\'s real gaps before you spend a single cedi.',
			'u' => '/development-quiz/',
		),
		array(
			'n' => '2',
			't' => 'Estimate your build cost — Ghana rates',
			'd' => 'Market-based cost model adjusted for region, type and specification. No offshore guesswork.',
			'u' => '/building-cost-calculator/',
		),
		array(
			'n' => '3',
			't' => 'Choose the plan that fits your stage',
			'd' => 'From Free Lead to the full Delivery System — pay to finish, not to feel good.',
			'u' => '/membership-levels/',
		),
		array(
			'n' => '4',
			't' => 'Ask a community that actually builds',
			'd' => 'Developers building in Ghana right now. Real answers, zero theory.',
			'u' => '/community/',
		),
	);

	foreach ( $steps as $step ) {
		$card_h = 50;
		$pdf->rect( $m, $y, $cw, $card_h, 'cream' );
		$pdf->rect( $m, $y, 4, $card_h, 'teal' );
		$pdf->rect( $m + 14, $y + 12, 24, 24, 'teal_deep' );
		$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
		$pdf->set_size( 13 );
		$pdf->set_color( 'gold_lite' );
		$nw = $pdf->measure( $step['n'] );
		$pdf->text( $m + 14 + ( 24 - $nw ) / 2, $y + 17, $step['n'] );
		$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
		$pdf->set_size( 10.2 );
		$pdf->set_color( 'ink' );
		$pdf->text( $m + 50, $y + 12, $step['t'] );
		$pdf->set_font( Gabochie_Thesis_Pdf::FONT_REGULAR );
		$pdf->set_size( 8.2 );
		$pdf->set_color( 'mist' );
		$pdf->paragraph( $m + 50, $y + 24, $step['d'], $cw - 60, 10 );
		$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
		$pdf->set_size( 7.4 );
		$pdf->set_color( 'teal' );
		$pdf->text( $m + 50, $y + 43, home_url( $step['u'] ) );
		$y += $card_h + 8;
	}

	$y += 2;
	$pdf->rect( $m, $y, $cw, 62, 'teal_deep' );
	$pdf->rect( $m, $y, $cw, 3, 'gold' );
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
	$pdf->set_size( 9.4 );
	$pdf->set_color( 'white' );
	$pdf->text( $m + 16, $y + 12, 'FIRST STEP — FREE · 5 MINUTES' );
	$pdf->set_size( 13 );
	$pdf->set_color( 'gold_lite' );
	$pdf->text( $m + 16, $y + 27, 'Get your Development Readiness Score' );
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_REGULAR );
	$pdf->set_size( 7.6 );
	$pdf->set_color( 'cream' );
	$pdf->text( $m + 16, $y + 44, 'gabochiedesign.com/development-quiz' );
	$pill = 'FREE · 5 MIN · 8 DIMENSIONS';
	$pw   = $pdf->measure( $pill ) + 22;
	$pdf->rect( $page_w - $m - $pw - 16, $y + 18, $pw, 26, 'teal' );
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
	$pdf->set_size( 8 );
	$pdf->set_color( 'white' );
	$pdf->text( $page_w - $m - $pw - 16 + 11, $y + 28, $pill );
	$y += 74;

	// P.S. line.
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_ITALIC );
	$pdf->set_size( 8.8 );
	$pdf->set_color( 'mist' );
	if ( $founding['remaining'] > 0 ) {
		$ps_line = ( $reader_number > 0 )
			? $offer['who'] . '#' . $reader_number . ' of ' . $founding['total'] . ' — your placeholder is reserved until the general wave opens.'
			: 'P.S. ' . $founding['remaining'] . ' of ' . $founding['total'] . ' founding places remain — the rate closes when the general wave opens.';
	} else {
		$ps_line = 'P.S. The founding wave is full — standard access stays free, always.';
	}
	$y = $pdf->paragraph( $m, $y, $ps_line, $cw, 11.6 );
	$y += 12;

	$pdf->hline( $m, $y, $page_w - $m, 0.8, 'line' );
	$y += 12;
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_REGULAR );
	$pdf->set_size( 8 );
	$pdf->set_color( 'mist' );
	$pdf->text( $m, $y, $data['brand'][0] . ' — ' . home_url( '/' ) );
	$pdf->set_size( 7.2 );
	$y += 11;
	$pdf->paragraph( $m, $y, 'This report is a summary prepared for prospective clients. Figures are estimates drawn from publicly reported ranges (GSS, Ministry of Works and Housing, market research) and are provided for orientation, not as professional advice.', $cw, 9 );

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
	$lines = $pdf->rich_lines( $text, $max_width );
	foreach ( $lines as $line ) {
		if ( $y > $page_h - 74 ) {
			$pdf->new_page();
			$pdf->furniture();
			$y = 56;
		}
		$pdf->draw_rich_line( $x, $y, $line );
		$y += $leading;
	}
	return $y;
}

/**
 * Render a list item (with a gold square bullet) across page boundaries.
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
	$lines = $pdf->rich_lines( $item, $max_width - 18 );
	$pdf->rect( $x, $y + 4, 5, 5, 'gold' );
	foreach ( $lines as $line ) {
		if ( $y > $page_h - 74 ) {
			$pdf->new_page();
			$pdf->furniture();
			$y = 56;
		}
		$pdf->draw_rich_line( $x + 18, $y, $line );
		$y += 13.4;
	}
	return $y;
}

/**
 * Render a pull-quote block (cream band + gold bar + opening quote) across
 * page boundaries.
 *
 * @param Gabochie_Thesis_Pdf $pdf PDF engine.
 * @param float               $x Left.
 * @param float               $y Top.
 * @param string              $quote Quote text.
 * @param float               $max_width Width.
 * @param float               $page_h Page height.
 * @return float Y after the quote.
 */
function gabochie_value_thesis_safe_quote( $pdf, $x, $y, $quote, $max_width, $page_h ) {
	$clean = $pdf->strip_bold( $quote );
	$y    += 6;
	if ( $y > $page_h - 74 ) {
		$pdf->new_page();
		$pdf->furniture();
		$y = 56;
	}
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_ITALIC );
	$pdf->set_size( 9.8 );
	$band_h = max( 54, $pdf->estimate_paragraph_height( $clean, $max_width - 58, 13.4 ) + 24 );
	$pdf->rect( $x, $y, $max_width, $band_h, 'cream' );
	$pdf->rect( $x, $y, 4, $band_h, 'gold' );
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_BOLD );
	$pdf->set_size( 20 );
	$pdf->set_color( 'gold' );
	$pdf->text( $x + 16, $y + 6, '“' );
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_ITALIC );
	$pdf->set_size( 9.8 );
	$pdf->set_color( 'teal2' );
	$y = $pdf->paragraph( $x + 34, $y + 12, $clean, $max_width - 58, 13.4 );
	$pdf->set_font( Gabochie_Thesis_Pdf::FONT_REGULAR );
	$pdf->set_size( 9.6 );
	$pdf->set_color( 'ink' );
	return $y + 12;
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