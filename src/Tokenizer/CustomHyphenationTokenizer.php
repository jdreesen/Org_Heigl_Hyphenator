<?php
/**
 * Copyright (c) 2008-2011 Andreas Heigl<andreas@heigl.org>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @category   Hyphenation
 * @package    Org_Heigl_Hyphenator
 * @subpackage Tokenizer
 * @author     Andreas Heigl <andreas@heigl.org>
 * @copyright  2008-2011 Andreas Heigl<andreas@heigl.org>
 * @license    http://www.opensource.org/licenses/mit-license.php MIT-License
 * @version    2.0.1
 * @link       http://github.com/heiglandreas/Hyphenator
 * @since      11.11.2011
 */

namespace Org\Heigl\Hyphenator\Tokenizer;

use Org\Heigl\Hyphenator\Options;

/**
 * Use custom character to split a word into tokens manually or forbid
 * tokenization by prefixing a word with specific character.
 *
 * @category   Hyphenation
 * @package    Org_Heigl_Hyphenator
 * @subpackage Tokenizer
 * @author     Andreas Heigl <andreas@heigl.org>
 * @copyright  2008-2011 Andreas Heigl<andreas@heigl.org>
 * @license    http://www.opensource.org/licenses/mit-license.php MIT-License
 * @version    2.0.1
 * @link       http://github.com/heiglandreas/Hyphenator
 * @since      04.11.2011
 */
class CustomHyphenationTokenizer implements Tokenizer
{

    private $options;

    public function __construct(Options $options)
    {
        $this->options = $options;
    }

    /**
     * The input can be a string or a tokenRegistry. If the input is a
     * TokenRegistry, each item will be tokenized.
     *
     * @param string|\Org\Heigl\Hyphenator\Tokenizer\TokenRegistry $input The
     * input to be tokenized
     *
     * @return \Org\Heigl\Hyphenator\Tokenizer\TokenRegistry
     */
    public function run($input)
    {
        if ($input instanceof TokenRegistry) {
            // Tokenize a TokenRegistry
            $f = clone($input);
            foreach ($input as $token) {
                if (! $token instanceof WordToken) {
                    continue;
                }
                $newTokens = $this->tokenize($token->get());
                $f->replace($token, $newTokens);
            }

            return $f ;
        }

        // Tokenize a simple string.
        $array =  $this->tokenize($input);
        $registry = new TokenRegistry();
        foreach ($array as $item) {
            $registry->add($item);
        }

        return $registry;
    }

    /**
     * Splits words by "noHyphenateString" and "customHyphen" to prevent further
     * automatic tokenization for them.
     *
     * @param \string $input The String to tokenize
     *
     * @return Token
     */
    private function tokenize($input)
    {
        $noHyphenateString = $this->options->getNoHyphenateString();
        $hasNoHyphenateString = ! empty($noHyphenateString);
        $customHyphen = $this->options->getCustomHyphen();
        $hasCustomHyphen = ! empty($customHyphen);
        $regexParts = [];

        if (! $hasNoHyphenateString && ! $hasCustomHyphen) {
            // Nothing to do here
            return [new WordToken($input)];
        }

        if ($hasNoHyphenateString) {
            $regexParts[] = '(?<=\W)' . preg_quote($noHyphenateString, '/');
        }

        if ($hasCustomHyphen) {
            $regexParts[] = '\b\w+' . preg_quote($customHyphen, '/');
        }

        $pattern = sprintf('/((?:%s)\w+?\b)/u', implode('|', $regexParts));
        $splits = preg_split($pattern, $input, -1, PREG_SPLIT_DELIM_CAPTURE);
        $tokens = [];
        foreach ($splits as $split) {
            if ('' === $split) {
                continue;
            }

            if ($hasNoHyphenateString && 0 === mb_strpos($split, $noHyphenateString)) {
                $tokens[] = new ExcludedWordToken(str_replace(
                    $noHyphenateString,
                    '',
                    $split
                ));
                continue;
            }

            if ($hasCustomHyphen && false !== mb_strpos($split, $customHyphen)) {
                $tokens[] = new ExcludedWordToken(str_replace(
                    $customHyphen,
                    $this->options->getHyphen(),
                    $split
                ));
                continue;
            }

            $tokens[] = new WordToken($split);
        }

        return $tokens;
    }
}
