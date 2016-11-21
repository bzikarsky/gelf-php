<?php

/**
 * The mbstring extension allows for overloadig `strlen` with the `mb_strlen`
 * equivalent which counts valid multibyte character-sequence as a single char
 * (depending on the default-charset settings). Since gelf-php relies on
 * `strlen` to return the number of bytes in a (binary) string (as specified)
 * by the PHP documentation this is considered to be a buggy configuration
 * and an exception will be thrown.
 */
if (extension_loaded('mbstring')) {
    // 2 - MB_OVERLOAD_STRING
    if (ini_get('mbstring.func_overload') & 2) {
        throw new UnexpectedValueException(
            'Overloading of string functions using mbstring.func_overload ' .
            'is not supported by this library.'
        );
    }
}
