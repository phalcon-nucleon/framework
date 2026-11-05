<?php

declare(strict_types=1);

namespace Neutrino\Support;

/**
 * String helpers.
 *
 * Only helpers adding something to PHP are kept: thin aliases of native
 * functions (mb_strlen, mb_strtolower, mb_strtoupper, mb_substr, ...) were
 * removed in 2.0.
 */
final class Str
{
    /** @var array<string, string> */
    private static array $camelCache = [];

    /** @var array<string, array<string, string>> */
    private static array $snakeCache = [];

    /** @var array<string, string> */
    private static array $studlyCache = [];

    /** @var array<string, string> */
    private static array $capitalizeCache = [];

    /** @var array<string, string>|null character => ASCII replacement */
    private static ?array $asciiMap = null;

    /**
     * Transliterate a UTF-8 value to ASCII.
     */
    public static function ascii(string $value): string
    {
        return (string) preg_replace('/[^\x20-\x7E]/u', '', strtr($value, self::asciiMap()));
    }

    /**
     * Convert a value to camel case.
     */
    public static function camel(string $value): string
    {
        return self::$camelCache[$value] ??= lcfirst(self::studly($value));
    }

    /**
     * Determine if a given string contains one of the given substrings.
     *
     * @param string|iterable<string> $needles
     */
    public static function contains(string $haystack, string|iterable $needles): bool
    {
        foreach (is_string($needles) ? [$needles] : $needles as $needle) {
            if ($needle !== '' && str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if a given string ends with one of the given substrings.
     *
     * @param string|iterable<string> $needles
     */
    public static function endsWith(string $haystack, string|iterable $needles): bool
    {
        foreach (is_string($needles) ? [$needles] : $needles as $needle) {
            if ($needle !== '' && str_ends_with($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cap a string with a single instance of a given value.
     */
    public static function finish(string $value, string $cap): string
    {
        $quoted = preg_quote($cap, '/');

        return (string) preg_replace('/(?:' . $quoted . ')+$/u', '', $value) . $cap;
    }

    /**
     * Determine if a given string matches a given pattern (`*` is a wildcard).
     */
    public static function is(string $pattern, string $value): bool
    {
        if ($pattern === $value) {
            return true;
        }

        $pattern = str_replace('\\*', '.*', preg_quote($pattern, '#'));

        return preg_match('#^' . $pattern . '\z#u', $value) === 1;
    }

    /**
     * Compute the levenshtein distance between a word and a list of words,
     * sorted by distance.
     *
     * @param iterable<string> $words
     *
     * @return array<string, int>
     */
    public static function levenshtein(string $word, iterable $words, int $order = SORT_ASC, int $flags = SORT_REGULAR): array
    {
        $result = [];
        foreach ($words as $w) {
            $result[$w] = levenshtein($word, $w);
        }

        if ($order & SORT_DESC) {
            arsort($result, $flags);
        } else {
            asort($result, $flags);
        }

        return $result;
    }

    /**
     * Limit the number of characters in a string.
     */
    public static function limit(string $value, int $limit = 100, string $end = '...'): string
    {
        if (mb_strwidth($value, 'UTF-8') <= $limit) {
            return $value;
        }

        return rtrim(mb_strimwidth($value, 0, $limit, '', 'UTF-8')) . $end;
    }

    /**
     * Limit the number of words in a string.
     */
    public static function words(string $value, int $words = 100, string $end = '...'): string
    {
        preg_match('/^\s*+(?:\S++\s*+){1,' . $words . '}/u', $value, $matches);

        if (!isset($matches[0]) || mb_strlen($value) === mb_strlen($matches[0])) {
            return $value;
        }

        return rtrim($matches[0]) . $end;
    }

    /**
     * Parse a `Class@method` style callback into class and method.
     *
     * @return array{0: string, 1: string|null}
     */
    public static function parseCallback(string $callback, ?string $default = null): array
    {
        if (str_contains($callback, '@')) {
            [$class, $method] = explode('@', $callback, 2);

            return [$class, $method];
        }

        return [$callback, $default];
    }

    /**
     * Generate a cryptographically secure random alpha-numeric string.
     */
    public static function random(int $length = 16): string
    {
        $string = '';

        while (($len = strlen($string)) < $length) {
            $size = $length - $len;
            $bytes = random_bytes(max(1, (int) ceil($size * 3 / 4)) + 2);
            $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
        }

        return $string;
    }

    /**
     * Replace the first occurrence of a given value in the string.
     */
    public static function replaceFirst(string $search, string $replace, string $subject): string
    {
        if ($search === '') {
            return $subject;
        }

        $position = strpos($subject, $search);

        return $position === false ? $subject : substr_replace($subject, $replace, $position, strlen($search));
    }

    /**
     * Replace the last occurrence of a given value in the string.
     */
    public static function replaceLast(string $search, string $replace, string $subject): string
    {
        if ($search === '') {
            return $subject;
        }

        $position = strrpos($subject, $search);

        return $position === false ? $subject : substr_replace($subject, $replace, $position, strlen($search));
    }

    /**
     * Generate a URL friendly "slug" from a given string.
     */
    public static function slug(string $title, string $separator = '-'): string
    {
        $title = self::ascii($title);

        // Convert all dashes/underscores into separator
        $flip = $separator === '-' ? '_' : '-';
        $title = (string) preg_replace('![' . preg_quote($flip) . ']+!u', $separator, $title);

        // Remove all characters that are not the separator, letters, numbers, or whitespace.
        $title = (string) preg_replace('![^' . preg_quote($separator) . '\pL\pN\s]+!u', '', mb_strtolower($title));

        // Replace all separator characters and whitespace by a single separator
        $title = (string) preg_replace('![' . preg_quote($separator) . '\s]+!u', $separator, $title);

        return trim($title, $separator);
    }

    /**
     * Convert a string to snake case.
     */
    public static function snake(string $value, string $delimiter = '_'): string
    {
        $key = $value;

        if (isset(self::$snakeCache[$key][$delimiter])) {
            return self::$snakeCache[$key][$delimiter];
        }

        if (!ctype_lower($value)) {
            $value = (string) preg_replace('/\s+/u', '', $value);
            $value = mb_strtolower((string) preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, $value), 'UTF-8');
        }

        return self::$snakeCache[$key][$delimiter] = $value;
    }

    /**
     * Determine if a given string starts with one of the given substrings.
     *
     * @param string|iterable<string> $needles
     */
    public static function startsWith(string $haystack, string|iterable $needles): bool
    {
        foreach (is_string($needles) ? [$needles] : $needles as $needle) {
            if ($needle !== '' && str_starts_with($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Convert a value to studly caps case.
     */
    public static function studly(string $value): string
    {
        return self::$studlyCache[$value] ??= str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $value)));
    }

    /**
     * Capitalize each word of a string (the rest of each word is lowered).
     */
    public static function capitalize(string $value): string
    {
        return self::$capitalizeCache[$value] ??= ucwords(strtolower($value));
    }

    /**
     * Make a string's first character uppercase (multibyte safe).
     */
    public static function ucfirst(string $string): string
    {
        return mb_strtoupper(mb_substr($string, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($string, 1, null, 'UTF-8');
    }

    /**
     * @return array<string, string>
     */
    private static function asciiMap(): array
    {
        if (self::$asciiMap === null) {
            self::$asciiMap = [];

            foreach (self::charsArray() as $replacement => $chars) {
                foreach ($chars as $char) {
                    self::$asciiMap[$char] = (string) $replacement;
                }
            }
        }

        return self::$asciiMap;
    }

    /**
     * @return array<array-key, list<string>>
     */
    private static function charsArray(): array
    {
        return [
            '0'    => ['°', '₀', '۰'],
            '1'    => ['¹', '₁', '۱'],
            '2'    => ['²', '₂', '۲'],
            '3'    => ['³', '₃', '۳'],
            '4'    => ['⁴', '₄', '۴', '٤'],
            '5'    => ['⁵', '₅', '۵', '٥'],
            '6'    => ['⁶', '₆', '۶', '٦'],
            '7'    => ['⁷', '₇', '۷'],
            '8'    => ['⁸', '₈', '۸'],
            '9'    => ['⁹', '₉', '۹'],
            'a'    => ['à', 'á', 'ả', 'ã', 'ạ', 'ă', 'ắ', 'ằ', 'ẳ', 'ẵ', 'ặ', 'â', 'ấ', 'ầ', 'ẩ', 'ẫ', 'ậ', 'ā', 'ą', 'å',
                'α', 'ά', 'ἀ', 'ἁ', 'ἂ', 'ἃ', 'ἄ', 'ἅ', 'ἆ', 'ἇ', 'ᾀ', 'ᾁ', 'ᾂ', 'ᾃ', 'ᾄ', 'ᾅ', 'ᾆ', 'ᾇ', 'ὰ', 'ά',
                'ᾰ', 'ᾱ', 'ᾲ', 'ᾳ', 'ᾴ', 'ᾶ', 'ᾷ', 'а', 'أ', 'အ', 'ာ', 'ါ', 'ǻ', 'ǎ', 'ª', 'ა', 'अ', 'ا'],
            'b'    => ['б', 'β', 'Ъ', 'Ь', 'ب', 'ဗ', 'ბ'],
            'c'    => ['ç', 'ć', 'č', 'ĉ', 'ċ'],
            'd'    => ['ď', 'ð', 'đ', 'ƌ', 'ȡ', 'ɖ', 'ɗ', 'ᵭ', 'ᶁ', 'ᶑ', 'д', 'δ', 'د', 'ض', 'ဍ', 'ဒ', 'დ'],
            'e'    => ['é', 'è', 'ẻ', 'ẽ', 'ẹ', 'ê', 'ế', 'ề', 'ể', 'ễ', 'ệ', 'ë', 'ē', 'ę', 'ě', 'ĕ', 'ė', 'ε', 'έ', 'ἐ',
                'ἑ', 'ἒ', 'ἓ', 'ἔ', 'ἕ', 'ὲ', 'έ', 'е', 'ё', 'э', 'є', 'ə', 'ဧ', 'ေ', 'ဲ', 'ე', 'ए', 'إ', 'ئ'],
            'f'    => ['ф', 'φ', 'ف', 'ƒ', 'ფ'],
            'g'    => ['ĝ', 'ğ', 'ġ', 'ģ', 'г', 'ґ', 'γ', 'ဂ', 'გ', 'گ'],
            'h'    => ['ĥ', 'ħ', 'η', 'ή', 'ح', 'ه', 'ဟ', 'ှ', 'ჰ'],
            'i'    => ['í', 'ì', 'ỉ', 'ĩ', 'ị', 'î', 'ï', 'ī', 'ĭ', 'į', 'ı', 'ι', 'ί', 'ϊ', 'ΐ', 'ἰ', 'ἱ', 'ἲ', 'ἳ', 'ἴ',
                'ἵ', 'ἶ', 'ἷ', 'ὶ', 'ί', 'ῐ', 'ῑ', 'ῒ', 'ΐ', 'ῖ', 'ῗ', 'і', 'ї', 'и', 'ဣ', 'ိ', 'ီ', 'ည်', 'ǐ', 'ი',
                'इ'],
            'j'    => ['ĵ', 'ј', 'Ј', 'ჯ', 'ج'],
            'k'    => ['ķ', 'ĸ', 'к', 'κ', 'Ķ', 'ق', 'ك', 'က', 'კ', 'ქ', 'ک'],
            'l'    => ['ł', 'ľ', 'ĺ', 'ļ', 'ŀ', 'л', 'λ', 'ل', 'လ', 'ლ'],
            'm'    => ['м', 'μ', 'م', 'မ', 'მ'],
            'n'    => ['ñ', 'ń', 'ň', 'ņ', 'ŉ', 'ŋ', 'ν', 'н', 'ن', 'န', 'ნ'],
            'o'    => ['ó', 'ò', 'ỏ', 'õ', 'ọ', 'ô', 'ố', 'ồ', 'ổ', 'ỗ', 'ộ', 'ơ', 'ớ', 'ờ', 'ở', 'ỡ', 'ợ', 'ø', 'ō', 'ő',
                'ŏ', 'ο', 'ὀ', 'ὁ', 'ὂ', 'ὃ', 'ὄ', 'ὅ', 'ὸ', 'ό', 'о', 'و', 'θ', 'ို', 'ǒ', 'ǿ', 'º', 'ო', 'ओ'],
            'p'    => ['п', 'π', 'ပ', 'პ', 'پ'],
            'q'    => ['ყ'],
            'r'    => ['ŕ', 'ř', 'ŗ', 'р', 'ρ', 'ر', 'რ'],
            's'    => ['ś', 'š', 'ş', 'с', 'σ', 'ș', 'ς', 'س', 'ص', 'စ', 'ſ', 'ს'],
            't'    => ['ť', 'ţ', 'т', 'τ', 'ț', 'ت', 'ط', 'ဋ', 'တ', 'ŧ', 'თ', 'ტ'],
            'u'    => ['ú', 'ù', 'ủ', 'ũ', 'ụ', 'ư', 'ứ', 'ừ', 'ử', 'ữ', 'ự', 'û', 'ū', 'ů', 'ű', 'ŭ', 'ų', 'µ', 'у', 'ဉ',
                'ု', 'ူ', 'ǔ', 'ǖ', 'ǘ', 'ǚ', 'ǜ', 'უ', 'उ'],
            'v'    => ['в', 'ვ', 'ϐ'],
            'w'    => ['ŵ', 'ω', 'ώ', 'ဝ', 'ွ'],
            'x'    => ['χ', 'ξ'],
            'y'    => ['ý', 'ỳ', 'ỷ', 'ỹ', 'ỵ', 'ÿ', 'ŷ', 'й', 'ы', 'υ', 'ϋ', 'ύ', 'ΰ', 'ي', 'ယ'],
            'z'    => ['ź', 'ž', 'ż', 'з', 'ζ', 'ز', 'ဇ', 'ზ'],
            'aa'   => ['ع', 'आ', 'آ'],
            'ae'   => ['ä', 'æ', 'ǽ'],
            'ai'   => ['ऐ'],
            'at'   => ['@'],
            'ch'   => ['ч', 'ჩ', 'ჭ', 'چ'],
            'dj'   => ['ђ', 'đ'],
            'dz'   => ['џ', 'ძ'],
            'ei'   => ['ऍ'],
            'gh'   => ['غ', 'ღ'],
            'ii'   => ['ई'],
            'ij'   => ['ĳ'],
            'kh'   => ['х', 'خ', 'ხ'],
            'lj'   => ['љ'],
            'nj'   => ['њ'],
            'oe'   => ['ö', 'œ', 'ؤ'],
            'oi'   => ['ऑ'],
            'oii'  => ['ऒ'],
            'ps'   => ['ψ'],
            'sh'   => ['ш', 'შ', 'ش'],
            'shch' => ['щ'],
            'ss'   => ['ß'],
            'sx'   => ['ŝ'],
            'th'   => ['þ', 'ϑ', 'ث', 'ذ', 'ظ'],
            'ts'   => ['ц', 'ც', 'წ'],
            'ue'   => ['ü'],
            'uu'   => ['ऊ'],
            'ya'   => ['я'],
            'yu'   => ['ю'],
            'zh'   => ['ж', 'ჟ', 'ژ'],
            '(c)'  => ['©'],
            'A'    => ['Á', 'À', 'Ả', 'Ã', 'Ạ', 'Ă', 'Ắ', 'Ằ', 'Ẳ', 'Ẵ', 'Ặ', 'Â', 'Ấ', 'Ầ', 'Ẩ', 'Ẫ', 'Ậ', 'Å', 'Ā', 'Ą',
                'Α', 'Ά', 'Ἀ', 'Ἁ', 'Ἂ', 'Ἃ', 'Ἄ', 'Ἅ', 'Ἆ', 'Ἇ', 'ᾈ', 'ᾉ', 'ᾊ', 'ᾋ', 'ᾌ', 'ᾍ', 'ᾎ', 'ᾏ', 'Ᾰ', 'Ᾱ',
                'Ὰ', 'Ά', 'ᾼ', 'А', 'Ǻ', 'Ǎ'],
            'B'    => ['Б', 'Β', 'ब'],
            'C'    => ['Ç', 'Ć', 'Č', 'Ĉ', 'Ċ'],
            'D'    => ['Ď', 'Ð', 'Đ', 'Ɖ', 'Ɗ', 'Ƌ', 'ᴅ', 'ᴆ', 'Д', 'Δ'],
            'E'    => ['É', 'È', 'Ẻ', 'Ẽ', 'Ẹ', 'Ê', 'Ế', 'Ề', 'Ể', 'Ễ', 'Ệ', 'Ë', 'Ē', 'Ę', 'Ě', 'Ĕ', 'Ė', 'Ε', 'Έ', 'Ἐ',
                'Ἑ', 'Ἒ', 'Ἓ', 'Ἔ', 'Ἕ', 'Έ', 'Ὲ', 'Е', 'Ё', 'Э', 'Є', 'Ə'],
            'F'    => ['Ф', 'Φ'],
            'G'    => ['Ğ', 'Ġ', 'Ģ', 'Г', 'Ґ', 'Γ'],
            'H'    => ['Η', 'Ή', 'Ħ'],
            'I'    => ['Í', 'Ì', 'Ỉ', 'Ĩ', 'Ị', 'Î', 'Ï', 'Ī', 'Ĭ', 'Į', 'İ', 'Ι', 'Ί', 'Ϊ', 'Ἰ', 'Ἱ', 'Ἳ', 'Ἴ', 'Ἵ', 'Ἶ',
                'Ἷ', 'Ῐ', 'Ῑ', 'Ὶ', 'Ί', 'И', 'І', 'Ї', 'Ǐ', 'ϒ'],
            'K'    => ['К', 'Κ'],
            'L'    => ['Ĺ', 'Ł', 'Л', 'Λ', 'Ļ', 'Ľ', 'Ŀ', 'ल'],
            'M'    => ['М', 'Μ'],
            'N'    => ['Ń', 'Ñ', 'Ň', 'Ņ', 'Ŋ', 'Н', 'Ν'],
            'O'    => ['Ó', 'Ò', 'Ỏ', 'Õ', 'Ọ', 'Ô', 'Ố', 'Ồ', 'Ổ', 'Ỗ', 'Ộ', 'Ơ', 'Ớ', 'Ờ', 'Ở', 'Ỡ', 'Ợ', 'Ø', 'Ō', 'Ő',
                'Ŏ', 'Ο', 'Ό', 'Ὀ', 'Ὁ', 'Ὂ', 'Ὃ', 'Ὄ', 'Ὅ', 'Ὸ', 'Ό', 'О', 'Θ', 'Ө', 'Ǒ', 'Ǿ'],
            'P'    => ['П', 'Π'],
            'R'    => ['Ř', 'Ŕ', 'Р', 'Ρ', 'Ŗ'],
            'S'    => ['Ş', 'Ŝ', 'Ș', 'Š', 'Ś', 'С', 'Σ'],
            'T'    => ['Ť', 'Ţ', 'Ŧ', 'Ț', 'Т', 'Τ'],
            'U'    => ['Ú', 'Ù', 'Ủ', 'Ũ', 'Ụ', 'Ư', 'Ứ', 'Ừ', 'Ử', 'Ữ', 'Ự', 'Û', 'Ū', 'Ů', 'Ű', 'Ŭ', 'Ų', 'У', 'Ǔ', 'Ǖ',
                'Ǘ', 'Ǚ', 'Ǜ'],
            'V'    => ['В'],
            'W'    => ['Ω', 'Ώ', 'Ŵ'],
            'X'    => ['Χ', 'Ξ'],
            'Y'    => ['Ý', 'Ỳ', 'Ỷ', 'Ỹ', 'Ỵ', 'Ÿ', 'Ῠ', 'Ῡ', 'Ὺ', 'Ύ', 'Ы', 'Й', 'Υ', 'Ϋ', 'Ŷ'],
            'Z'    => ['Ź', 'Ž', 'Ż', 'З', 'Ζ'],
            'AE'   => ['Ä', 'Æ', 'Ǽ'],
            'CH'   => ['Ч'],
            'DJ'   => ['Ђ'],
            'DZ'   => ['Џ'],
            'GX'   => ['Ĝ'],
            'HX'   => ['Ĥ'],
            'IJ'   => ['Ĳ'],
            'JX'   => ['Ĵ'],
            'KH'   => ['Х'],
            'LJ'   => ['Љ'],
            'NJ'   => ['Њ'],
            'OE'   => ['Ö', 'Œ'],
            'PS'   => ['Ψ'],
            'SH'   => ['Ш'],
            'SHCH' => ['Щ'],
            'SS'   => ['ẞ'],
            'TH'   => ['Þ'],
            'TS'   => ['Ц'],
            'UE'   => ['Ü'],
            'YA'   => ['Я'],
            'YU'   => ['Ю'],
            'ZH'   => ['Ж'],
            ' '    => ["\xC2\xA0", "\xE2\x80\x80", "\xE2\x80\x81", "\xE2\x80\x82", "\xE2\x80\x83", "\xE2\x80\x84",
                "\xE2\x80\x85", "\xE2\x80\x86", "\xE2\x80\x87", "\xE2\x80\x88", "\xE2\x80\x89", "\xE2\x80\x8A",
                "\xE2\x80\xAF", "\xE2\x81\x9F", "\xE3\x80\x80"],
        ];
    }
}
