<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * BatchMate Bengali Text Helper
 * Unicode-to-Bijoy (ANSI) converter and mixed-script GD text renderer
 * for PHP GD imagettftext() using Kalpurush-ANSI.ttf.
 */

/**
 * Returns true if $text contains any Bengali Unicode character (U+0980–U+09FF).
 */
function batchmate_has_bangla( string $text ): bool {
	return $text !== '' && (bool) preg_match( '/[\x{0980}-\x{09FF}]/u', $text );
}

/**
 * Returns the absolute path to Kalpurush-ANSI.ttf if available, or empty string.
 */
function batchmate_get_bangla_font(): string {
	$candidates = array(
		ROYAL_BATCHMATE_PLUGIN_DIR . 'assets/fonts/Kalpurush-ANSI.ttf',
		ROYAL_BATCHMATE_PLUGIN_DIR . '/assets/fonts/Kalpurush-ANSI.ttf',
	);
	foreach ( $candidates as $path ) {
		if ( file_exists( $path ) ) {
			return $path;
		}
	}
	return '';
}

/**
 * Select Bengali font when $text contains Bengali, otherwise return $fallback.
 */
function batchmate_select_font( string $text, string $fallback = '' ): string {
	if ( batchmate_has_bangla( $text ) ) {
		$bn_font = batchmate_get_bangla_font();
		if ( $bn_font !== '' ) {
			return $bn_font;
		}
	}
	return $fallback;
}

/**
 * Normalize decomposed Bengali Unicode sequences (NFD -> NFC) so that
 * ড+়, ঢ+়, য+়, ে+া, ে+ৗ always map cleanly.
 */
function batchmate_normalize_bangla_unicode( string $text ): string {
	if ( class_exists( 'Normalizer' ) ) {
		$norm = \Normalizer::normalize( $text, \Normalizer::FORM_C );
		if ( is_string( $norm ) && $norm !== '' ) {
			$text = $norm;
		}
	}

	return str_replace(
		array(
			"\u{09A1}\u{09BC}", // ড + ় -> ড়
			"\u{09A2}\u{09BC}", // ঢ + ় -> ঢ়
			"\u{09AF}\u{09BC}", // য + ় -> য়
			"\u{09C7}\u{09BE}", // ে + া -> ো
			"\u{09C7}\u{09D7}", // ে + ৗ -> ৌ
		),
		array(
			"\u{09DC}",
			"\u{09DD}",
			"\u{09DF}",
			"\u{09CB}",
			"\u{09CC}",
		),
		$text
	);
}

/**
 * Convert Unicode Bengali text to Bijoy ANSI (UTF-8 encoded Windows-1252 codepoints)
 * for PHP GD imagettftext() with Kalpurush-ANSI.ttf.
 */
function batchmate_unicode_to_bijoy( string $text ): string {
	if ( $text === '' || ! batchmate_has_bangla( $text ) ) {
		return $text;
	}

	static $char_map = null;
	static $kar_tokens_regex = null;

	if ( $char_map === null ) {
		$char_map = array(
			// Conjuncts (Juktabarnas) — sorted longest-first below
			'ক্ক'   => '°',
			'ক্ট'   => '±',
			'ক্ট্র'  => '±ª',
			'ক্ত'   => '³',
			'ক্ত্র'  => '³«',
			'ক্ব'   => 'K¡',
			'ক্ম'   => '´',
			'ক্য'   => 'K¨',
			'ক্র'   => 'µ',
			'ক্ল'   => 'K¬',
			'ক্ষ্ণ'  => '¶è',
			'ক্ষ্ব'  => '¶¡',
			'ক্ষ্ম'  => '²',
			'ক্ষ্ম্য' => '²¨',
			'ক্ষ্য'  => '¶¨',
			'ক্ষ'   => '¶',
			'ক্স'   => '·',
			'খ্য'   => 'L¨',
			'খ্র'   => 'Lª',
			'গ্‌ণ'  => 'M&Y',
			'গ্ধ'   => '»',
			'গ্ধ্য'  => '»¨',
			'গ্ধ্র'  => '»ª',
			'গ্ন'   => 'Mœ',
			'গ্ন্য'  => 'Mœ¨',
			'গ্ব'   => 'M¦',
			'গ্ম'   => 'M¥',
			'গ্য'   => 'M¨',
			'গ্র'   => 'MÖ',
			'গ্র্য'  => 'M¨©',
			'গ্ল'   => 'M­',
			'ঘ্ন'   => 'Nœ',
			'ঘ্য'   => 'N¨',
			'ঘ্র'   => 'Nª',
			'ঙ্ক'   => '¼',
			'ঙ্‌ক্ত' => 'O&³',
			'ঙ্ক্য'  => '¼¨',
			'ঙ্ক্ষ'  => '•¶',
			'ঙ্খ'   => '•L',
			'ঙ্গ'   => '½',
			'ঙ্গ্য'  => '½¨',
			'ঙ্ঘ'   => '•N',
			'ঙ্ঘ্য'  => '•N¨',
			'ঙ্ঘ্র'  => '•Nª',
			'ঙ্ম'   => '•g',
			'চ্চ'   => '”P',
			'চ্ছ'   => '”Q',
			'চ্ছ্ব'  => '”Q¡',
			'চ্ছ্র'  => '”Qª',
			'চ্ঞ'   => '”T',
			'চ্ব'   => '”¡',
			'চ্য'   => 'P¨',
			'জ্জ'   => '¾',
			'জ্জ্ব'  => '¾¡',
			'জ্ঝ'   => 'À',
			'জ্ঞ'   => 'Á',
			'জ্ব'   => 'R¡',
			'জ্য'   => 'R¨',
			'জ্র'   => 'Rª',
			'ঞ্চ'   => 'Â',
			'ঞ্ছ'   => 'Ã',
			'ঞ্জ'   => 'Ä',
			'ঞ্ঝ'   => 'Å',
			'ট্ট'   => 'Æ',
			'ট্ব'   => 'U¡',
			'ট্ম'   => 'U¥',
			'ট্য'   => 'U¨',
			'ট্র'   => 'Uª',
			'ড্ড'   => 'Ç',
			'ড্ব'   => 'W¡',
			'ড্য'   => 'W¨',
			'ড্র'   => 'Wª',
			'ড়্গ'  => 'ÿ',
			'ঢ়্গ'  => 'ÿ',
			'ঢ্য'   => 'X¨',
			'ঢ্র'   => 'Xª',
			'ণ্ট'   => 'È',
			'ণ্ঠ'   => 'É',
			'ণ্ঠ্য'  => 'É¨',
			'ণ্ড'   => 'Ê',
			'ণ্ড্য'  => 'Ê¨',
			'ণ্ড্র'  => 'Êª',
			'ণ্ঢ'   => 'YX',
			'ণ্ণ'   => 'Yè',
			'ণ্ব'   => 'Y¡',
			'ণ্ম'   => 'Y¥',
			'ণ্য'   => 'Y¨',
			'ৎক'   => 'rK',
			'ত্ত'   => 'Ë',
			'ত্ত্ব'  => 'Ë¡',
			'ত্ত্য'  => 'Ë¨',
			'ত্থ'   => 'Ì',
			'ত্ন'   => 'Zœ',
			'ত্ব'   => 'Z¡',
			'ত্ম'   => 'Í',
			'ত্ম্য'  => 'Í¨',
			'ত্য'   => 'Z¨',
			'ত্র'   => 'Î',
			'ত্র্য'  => 'Z¨©',
			'ৎল'   => 'rj',
			'ৎস'   => 'rm',
			'থ্ব'   => '_¡',
			'থ্য'   => '_¨',
			'থ্র'   => '_ª',
			'দ্গ'   => '˜M',
			'দ্ঘ'   => '™N',
			'দ্দ'   => 'Ï',
			'দ্দ্ব'  => 'Ï¡',
			'দ্ধ'   => '×',
			'দ্ব'   => 'Ø',
			'দ্ভ'   => '™¢',
			'দ্ভ্র'  => '™£',
			'দ্ম'   => 'Ù',
			'দ্য'   => '`¨',
			'দ্র'   => '`ª',
			'দ্র্য'  => '`¨©',
			'ধ্ন'   => 'aœ',
			'ধ্ব'   => 'aŸ',
			'ধ্ম'   => 'a¥',
			'ধ্র'   => 'aª',
			'ন্ট্র'  => '›Uª',
			'ন্ট'   => '›U',
			'ন্ঠ'   => 'Ú',
			'ন্ড'   => 'Û',
			'ন্ড্র'  => 'Ûª',
			'ন্ত্ব'  => 'š—¡',
			'ন্ত্য'  => 'š—¨',
			'ন্ত্র'  => 'š¿',
			'ন্ত্র্য' => 'š—¨©',
			'ন্ত'   => 'š—',
			'ন্থ্র'  => 'š’ª',
			'ন্থ'   => 'š’',
			'ন্দ'   => '›`',
			'ন্দ্য'  => '›`¨',
			'ন্দ্ব'  => '›Ø',
			'ন্দ্র'  => '›`ª',
			'ন্ধ্য'  => 'Ü¨',
			'ন্ধ্র'  => 'Üª',
			'ন্ধ'   => 'Ü',
			'ধ্য'   => 'a¨',
			'ন্ন'   => 'bœ',
			'ন্ব'   => 'š^',
			'ন্ম'   => 'b¥',
			'ন্য'   => 'b¨',
			'প্ট'   => 'Þ',
			'প্ত'   => 'ß',
			'প্ন'   => 'cœ',
			'প্প'   => 'à',
			'প্য'   => 'c¨',
			'প্র'   => 'cÖ',
			'প্র্য'  => 'c¨©',
			'প্ল'   => 'c­',
			'প্স'   => 'á',
			'ফ্র'   => 'd«',
			'ফ্ল'   => 'd¬',
			'ব্জ'   => 'â',
			'ব্দ'   => 'ã',
			'ব্ধ'   => 'ä',
			'ব্ব'   => 'eŸ',
			'ব্য'   => 'e¨',
			'ব্র'   => 'eª',
			'ব্ল'   => 'e­',
			'ভ্ব'   => 'f¡',
			'ভ্য'   => 'f¨',
			'ভ্র'   => 'å',
			'ম্ন'   => 'æ',
			'ম্প'   => '¤c',
			'ম্প্র'  => '¤cÖ',
			'ম্ফ'   => 'ç',
			'ম্ব'   => '¤^',
			'ম্ব্র'  => '¤^ª',
			'ম্ভ'   => '¤¢',
			'ম্ভ্র'  => '¤£',
			'ম্ম'   => '¤§',
			'ম্য'   => 'g¨',
			'ম্র'   => 'gª',
			'ম্ল'   => '¤¬',
			'অ্য'   => 'A¨',
			'য্য'   => 'h¨',
			'র্ক'   => 'K©',
			'র্ক্য'  => 'K¨©',
			'র্গ্য'  => 'M¨©',
			'র্ঘ্য'  => 'N¨©',
			'র্চ্য'  => 'P¨©',
			'র্জ্য'  => 'R¨©',
			'র্ণ্য'  => 'Y¨©',
			'র্ত্য'  => 'Z¨©',
			'র্থ্য'  => '_¨©',
			'র্ব্য'  => 'e¨©',
			'র্ম্য'  => 'g¨©',
			'র্শ্য'  => 'k¨©',
			'র্ষ্য'  => 'l¨©',
			'র্হ্য'  => 'n¨©',
			'র্খ'   => 'L©',
			'র্গ'   => 'M©',
			'র্গ্র'  => 'MÖ©',
			'র্ঘ'   => 'N©',
			'র্চ'   => 'P©',
			'র্ছ'   => 'Q©',
			'র্জ'   => 'R©',
			'র্ঝ'   => 'S©',
			'র্ট'   => 'U©',
			'র্ড'   => 'W©',
			'র্ণ'   => 'Y©',
			'র্ত'   => 'Z©',
			'র্ত্র'  => 'Î©',
			'র্থ'   => '_©',
			'র্দ'   => '`©',
			'র্দ্ব'  => 'Ø©',
			'র্দ্র'  => '`ª©',
			'র্ধ'   => 'a©',
			'র্ধ্ব'  => 'aŸ©',
			'র্ন'   => 'b©',
			'র্প'   => 'c©',
			'র্ফ'   => 'd©',
			'র্ব'   => 'e©',
			'র্ভ'   => 'f©',
			'র্ম'   => 'g©',
			'র্য'   => 'h©',
			'র্ল'   => 'j©',
			'র্শ'   => 'k©',
			'র্শ্ব'  => 'k¦©',
			'র্ষ'   => 'l©',
			'র্স'   => 'm©',
			'র্হ'   => 'n©',
			'র্ঢ্য'  => 'X¨©',
			'ল্ক'   => 'é',
			'ল্ক্য'  => 'é¨',
			'ল্গ'   => 'ê',
			'ল্ট'   => 'ë',
			'ল্ড'   => 'ì',
			'ল্প'   => 'í',
			'ল্‌ফ'  => 'j&d',
			'ল্ব'   => 'j¡',
			'ল্‌ভ'  => 'j&f',
			'ল্ম'   => 'j¥',
			'ল্য'   => 'j¨',
			'ল্ল'   => 'j­',
			'শ্চ'   => 'ð',
			'শ্ছ'   => 'ñ',
			'শ্ন'   => 'kœ',
			'শ্ব'   => 'k¦',
			'শ্ম'   => 'k¥',
			'শ্য'   => 'k¨',
			'শ্র'   => 'kª',
			'শ্ল'   => 'k­',
			'ষ্ক'   => '®‹',
			'ষ্ক্র'  => '®Œ',
			'ষ্ট'   => 'ó',
			'ষ্ট্য'  => 'ó¨',
			'ষ্ট্র'  => 'óª',
			'ষ্ঠ'   => 'ô',
			'ষ্ঠ্য'  => 'ô¨',
			'ষ্ণ'   => 'ò',
			'ষ্প'   => '®c',
			'ষ্প্র'  => '®cÖ',
			'ষ্ফ'   => 'õ',
			'ষ্ব'   => '®^',
			'ষ্ম'   => '®§',
			'ষ্য'   => 'l¨',
			'স্ক'   => '¯‹',
			'স্ক্র'  => '¯Œ',
			'স্খ'   => 'ö',
			'স্ট'   => '÷',
			'স্ট্র'  => '÷ª',
			'স্ত'   => '¯—',
			'স্ত্ব'  => '¯—¡',
			'স্ত্য'  => '¯—¨',
			'স্ত্র'  => '¯¿',
			'স্থ'   => '¯’',
			'স্থ্য'  => '¯’¨',
			'স্ন'   => 'ø',
			'স্প'   => '¯c',
			'স্প্র'  => '¯cÖ',
			'স্প্‌ল' => '¯c&j',
			'স্ফ'   => 'ù',
			'স্ব'   => '¯^',
			'স্ম'   => '¯§',
			'স্য'   => 'm¨',
			'স্র'   => 'mª',
			'স্ল'   => '¯¬',
			'হ্ণ'   => 'nœ',
			'হ্ন'   => 'ý',
			'হ্ব'   => 'nŸ',
			'হ্ম'   => 'þ',
			'হ্য'   => 'n¨',
			'হ্র'   => 'nª',
			'হ্ল'   => 'n¬',
			'ভ্ল'   => 'f¬',

			// Independent Vowels & Modifiers
			'ঁ' => 'u',
			'ং' => 's',
			'ঃ' => 't',
			'অ' => 'A',
			'আ' => 'Av',
			'ই' => 'B',
			'ঈ' => 'C',
			'উ' => 'D',
			'ঊ' => 'E',
			'ঋ' => 'F',
			'এ' => 'G',
			'ঐ' => 'H',
			'ও' => 'I',
			'ঔ' => 'J',

			// Consonants
			'ক' => 'K',
			'খ' => 'L',
			'গ' => 'M',
			'ঘ' => 'N',
			'ঙ' => 'O',
			'চ' => 'P',
			'ছ' => 'Q',
			'জ' => 'R',
			'ঝ' => 'S',
			'ঞ' => 'T',
			'ট' => 'U',
			'ঠ' => 'V',
			'ড' => 'W',
			'ঢ' => 'X',
			'ণ' => 'Y',
			'ত' => 'Z',
			'থ' => '_',
			'দ' => '`',
			'ধ' => 'a',
			'ন' => 'b',
			'প' => 'c',
			'ফ' => 'd',
			'ব' => 'e',
			'ভ' => 'f',
			'ম' => 'g',
			'য' => 'h',
			'র' => 'i',
			'ল' => 'j',
			'শ' => 'k',
			'ষ' => 'l',
			'স' => 'm',
			'হ' => 'n',
			'ড়' => 'o',
			'ঢ়' => 'p',
			'য়' => 'q',
			'ৎ' => 'r',

			// Post-positioned Kars
			'া' => 'v',
			'ী' => 'x',
			'ু' => 'y',
			'ূ' => '‚',
			'ৃ' => '„',
			'্' => '',

			// Bengali Digits & Dari
			'০' => '0',
			'১' => '1',
			'২' => '2',
			'৩' => '3',
			'৪' => '4',
			'৫' => '5',
			'৬' => '6',
			'৭' => '7',
			'৮' => '8',
			'৯' => '9',
			'।' => '|',
			'॥' => '||',
		);

		// Critical: sort keys longest-first so 3/4-char conjuncts (e.g. স্ত্র, ন্ত্র) match before 2-char ones (স্ত, ত্র)
		uksort( $char_map, static function( string $a, string $b ): int {
			return strlen( $b ) <=> strlen( $a );
		} );

		// Build token alternation for kar reordering (sorted longest-first)
		$tokens = array(
			'°','±','±ª','³','³«','K¡','´','K¨','µ','K¬','¶','¶è','¶¡','²','²¨','¶¨','·','L¨','Lª','M&Y',
			'»','»¨','»ª','Mœ','Mœ¨','M¦','M¥','M¨','MÖ','M¨©','M­','Nœ','N¨','Nª','¼','O&³','¼¨','•¶','•L',
			'½','½¨','•N','•N¨','•Nª','•g','”P','”Q','”Q¡','”Qª','”T','”¡','P¨','¾','¾¡','À','Á','R¡','R¨','Rª',
			'Â','Ã','Ä','Å','Æ','U¡','U¥','U¨','Uª','Ç','W¡','W¨','Wª','ÿ','X¨','Xª','È','É','É¨','Ê','Ê¨','Êª',
			'YX','Yè','Y¡','Y¥','Y¨','rK','Ë','Ë¡','Ë¨','Ì','Zœ','Z¡','Í','Í¨','Z¨','Î','Z¨©','rj','rm','_¡','_¨','_ª',
			'˜M','™N','Ï','Ï¡','×','Ø','™¢','™£','Ù','`¨','`ª','`¨©','aœ','aŸ','a¥','a¨','aª','›U','›Uª','Ú','Û','Ûª',
			'š—','š—¡','š—¨','š¿','š—¨©','š’','š’ª','›`','›`¨','›Ø','›`ª','Ü','Ü¨','Üª','bœ','š^','b¥','b¨','Þ','ß','cœ',
			'à','c¨','cÖ','c¨©','c­','á','d«','d¬','â','ã','ä','eŸ','e¨','eª','e­','f¡','f¨','å','æ','¤c','¤cÖ','ç',
			'¤^','¤^ª','¤¢','¤£','¤§','g¨','gª','¤¬','h¨','K©','K¨©','M¨©','N¨©','P¨©','R¨©','Y¨©','Z¨©','_¨©','e¨©',
			'g¨©','k¨©','l¨©','n¨©','L©','M©','MÖ©','N©','P©','Q©','R©','S©','U©','W©','Y©','Z©','Î©','_©','`©','Ø©',
			'`ª©','a©','aŸ©','b©','c©','d©','e©','f©','g©','h©','j©','k©','k¦©','l©','m©','n©','X¨©','é','é¨','ê','ë',
			'ì','í','j&d','j¡','j&f','j¥','j¨','j­','ð','ñ','kœ','k¦','k¥','k¨','kª','k­','®‹','®Œ','ó','ó¨','óª','ô',
			'ô¨','ò','®c','®cÖ','õ','®^','®§','l¨','¯‹','¯Œ','ö','÷','÷ª','¯—','¯—¡','¯—¨','¯¿','¯’','¯’¨','ø','¯c',
			'¯cÖ','¯c&j','ù','¯^','¯§','m¨','mª','¯¬','nœ','ý','nŸ','þ','n¨','nª','n¬','f¬',
			'F','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','_','`','a','b','c','d','e','f','g',
			'h','i','j','k','l','m','n','o','p','q',
		);
		$tokens = array_values( array_unique( $tokens ) );
		usort( $tokens, static function( string $a, string $b ): int {
			return strlen( $b ) <=> strlen( $a );
		} );
		$escaped = array_map( static fn( string $t ) => preg_quote( $t, '/' ), $tokens );
		$kar_tokens_regex = implode( '|', $escaped );
	}

	$text = batchmate_normalize_bangla_unicode( $text );
	$str  = str_replace( array_keys( $char_map ), array_values( $char_map ), $text );

	// Remove any leftover zero-width joiners/non-joiners
	$str = str_replace( array( "\u{200C}", "\u{200D}" ), '', $str );

	// Pre-positioned vowel signs (ি, ে, ো, ৌ, ৈ) at start of string or after whitespace
	$str = preg_replace_callback(
		'/(\A|\s)(' . $kar_tokens_regex . ')(ি|ে|ো|ৌ|ৈ)/u',
		static function( array $m ): string {
			$prefix = $m[1];
			$base   = $m[2];
			switch ( $m[3] ) {
				case 'ি': return $prefix . 'w' . $base;
				case 'ে': return $prefix . '†' . $base;
				case 'ো': return $prefix . '†' . $base . 'v';
				case 'ৈ': return $prefix . 'ˆ' . $base;
				case 'ৌ': return $prefix . '†' . $base . 'Š';
			}
			return $m[0];
		},
		$str
	);

	// Pre-positioned vowel signs anywhere else in string
	$str = preg_replace_callback(
		'/(' . $kar_tokens_regex . ')(ি|ে|ো|ৌ|ৈ)/u',
		static function( array $m ): string {
			$base = $m[1];
			switch ( $m[2] ) {
				case 'ি': return 'w' . $base;
				case 'ে': return '‡' . $base;
				case 'ো': return '‡' . $base . 'v';
				case 'ৈ': return 'ˆ' . $base;
				case 'ৌ': return '‡' . $base . 'Š';
			}
			return $m[0];
		},
		$str
	);

	return $str;
}

/**
 * Split text into runs separating Bengali segments from ASCII Latin/numeric segments
 * so mixed names (e.g. "রেজাউল ইসলাম (Reza)") render Bengali in Kalpurush-ANSI
 * and English/digits in the fallback Latin font without mangling ASCII letters.
 *
 * @return array<int, array{text: string, is_bangla: bool}>
 */
function batchmate_split_script_runs( string $text ): array {
	if ( $text === '' ) {
		return array();
	}
	if ( ! batchmate_has_bangla( $text ) ) {
		return array( array( 'text' => $text, 'is_bangla' => false ) );
	}

	// Capture contiguous Latin/ASCII-digit tokens or non-Bengali non-ASCII symbols (e.g. bullets •, dashes —)
	// so they render with the Latin fallback font instead of colliding with Bijoy ANSI glyph slots.
	$parts = preg_split( '/([A-Za-z0-9]+|[^\x{0980}-\x{09FF}\x{0020}-\x{007E}\s]+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
	$runs  = array();
	foreach ( $parts as $part ) {
		$is_non_bn_token = (bool) preg_match( '/^(?:[A-Za-z0-9]+|[^\x{0980}-\x{09FF}\x{0020}-\x{007E}\s]+)$/u', $part );
		$is_bn           = ! $is_non_bn_token && batchmate_has_bangla( $part );
		if ( ! empty( $runs ) && $runs[ count( $runs ) - 1 ]['is_bangla'] === $is_bn ) {
			$runs[ count( $runs ) - 1 ]['text'] .= $part;
		} else {
			$runs[] = array( 'text' => $part, 'is_bangla' => $is_bn );
		}
	}

	return $runs;
}

/**
 * Measure total width & height of text (supporting pure English, pure Bengali, or mixed text).
 *
 * @return array{w: int, h: int}
 */
function batchmate_gd_measure_text( float $size, string $text, string $fallback_font ): array {
	if ( $text === '' ) {
		return array( 'w' => 0, 'h' => 0 );
	}

	if ( ! batchmate_has_bangla( $text ) ) {
		$b = imagettfbbox( $size, 0, $fallback_font, $text );
		return array(
			'w' => abs( $b[2] - $b[0] ),
			'h' => abs( $b[1] - $b[7] ),
		);
	}

	$bn_font = batchmate_get_bangla_font();
	if ( $bn_font === '' ) {
		$b = imagettfbbox( $size, 0, $fallback_font, $text );
		return array(
			'w' => abs( $b[2] - $b[0] ),
			'h' => abs( $b[1] - $b[7] ),
		);
	}

	$runs    = batchmate_split_script_runs( $text );
	$total_w = 0;
	$max_h   = 0;

	foreach ( $runs as $run ) {
		$f = $run['is_bangla'] ? $bn_font : $fallback_font;
		$t = $run['is_bangla'] ? batchmate_unicode_to_bijoy( $run['text'] ) : $run['text'];
		$b = imagettfbbox( $size, 0, $f, $t );
		$total_w += abs( $b[2] - $b[0] );
		$h = abs( $b[1] - $b[7] );
		if ( $h > $max_h ) {
			$max_h = $h;
		}
	}

	return array(
		'w' => (int) $total_w,
		'h' => (int) $max_h,
	);
}

/**
 * Draw text onto a GD image using Kalpurush-ANSI (with Bijoy conversion) for Bengali runs
 * and $fallback_font for Latin/ASCII runs.
 */
function batchmate_gd_draw_text( $image, float $size, float $angle, int $x, int $y, int $color, string $fallback_font, string $text ): void {
	if ( $text === '' ) {
		return;
	}

	$bn_font = batchmate_has_bangla( $text ) ? batchmate_get_bangla_font() : '';
	if ( $bn_font === '' ) {
		imagettftext( $image, $size, $angle, $x, $y, $color, $fallback_font, $text );
		return;
	}

	$runs  = batchmate_split_script_runs( $text );
	$cur_x = $x;

	foreach ( $runs as $run ) {
		$f   = $run['is_bangla'] ? $bn_font : $fallback_font;
		$t   = $run['is_bangla'] ? batchmate_unicode_to_bijoy( $run['text'] ) : $run['text'];
		$res = imagettftext( $image, $size, $angle, (int) $cur_x, $y, $color, $f, $t );
		if ( is_array( $res ) && isset( $res[2] ) ) {
			$cur_x = $res[2];
		} else {
			$b = imagettfbbox( $size, $angle, $f, $t );
			$cur_x += abs( $b[2] - $b[0] );
		}
	}
}

/**
 * Prepare single-script text & font array for legacy callers.
 *
 * @return array{text: string, font: string}
 */
function batchmate_prepare_text_for_gd( string $text, string $fallback_font = '' ): array {
	if ( batchmate_has_bangla( $text ) ) {
		$bn_font = batchmate_get_bangla_font();
		if ( $bn_font !== '' ) {
			return array(
				'text' => batchmate_unicode_to_bijoy( $text ),
				'font' => $bn_font,
			);
		}
	}
	return array(
		'text' => $text,
		'font' => $fallback_font,
	);
}
