<?php
/**
 * Verifies that control statements conform to their coding standards.
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006-2012 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */

if (class_exists('PHP_CodeSniffer_Standards_AbstractPatternSniff', true) === false) {
    throw new PHP_CodeSniffer_Exception('Class PHP_CodeSniffer_Standards_AbstractPatternSniff not found');
}

/**
 * Verifies that control statements conform to their coding standards.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006-2012 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @version   Release: 1.4.3
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class Nexus_Sniffs_ControlStructures_ControlSignatureSniff extends PHP_CodeSniffer_Standards_AbstractPatternSniff
{

    /**
     * If true, comments will be ignored if they are found in the code.
     *
     * @var boolean
     */
    public $ignoreComments = true;


    /**
     * Returns the patterns that this test wishes to verify.
     *
     * @return array(string)
     */
    protected function getPatterns()
    {
        return array(
                'do EOL{...} while (...);EOL',
                'while(...)EOL',
                'for(...)EOL',
                'if(...)EOL',
                'foreach(...)EOL',
                'foreach (...)EOL',
                'elseif(...)EOL',
                'else if (...)EOL',
                'elseEOL',
                'do EOL',
               );

    }//end getPatterns()


    protected function processPattern(
        $patternInfo,
        PHP_CodeSniffer_File $phpcsFile,
        $stackPtr
    ) {
        $tokens      = $phpcsFile->getTokens();
        $pattern     = $patternInfo['pattern'];
        $patternCode = $patternInfo['pattern_code'];
        $errors      = array();
        $found       = '';


        $ignoreTokens = array(T_WHITESPACE);
        if ($this->ignoreComments === true) {
            $ignoreTokens
                = array_merge($ignoreTokens, PHP_CodeSniffer_Tokens::$commentTokens);
        }

        $origStackPtr = $stackPtr;
        $hasError     = false;

        if ($patternInfo['listen_pos'] > 0) {
            $stackPtr--;

            for ($i = ($patternInfo['listen_pos'] - 1); $i >= 0; $i--) {
                if ($pattern[$i]['type'] === 'token') {
                    if ($pattern[$i]['token'] === T_WHITESPACE) {
                        if ($tokens[$stackPtr]['code'] === T_WHITESPACE) {
                            $found = $tokens[$stackPtr]['content'].$found;
                        }

                        // Only check the size of the whitespace if this is not
                        // the first token. We don't care about the size of
                        // leading whitespace, just that there is some.
                        if ($i !== 0) {
                            if ($tokens[$stackPtr]['content'] !== $pattern[$i]['value']) {
                                $hasError = true;
                            }
                        }
                    } else {
                        // Check to see if this important token is the same as the
                        // previous important token in the pattern. If it is not,
                        // then the pattern cannot be for this piece of code.
                        $prev = $phpcsFile->findPrevious(
                            $ignoreTokens,
                            $stackPtr,
                            null,
                            true
                        );

                        if ($prev === false
                            || $tokens[$prev]['code'] !== $pattern[$i]['token']
                        ) {
                            return false;
                        }

                        // If we skipped past some whitespace tokens, then add them
                        // to the found string.
                        $tokenContent = $phpcsFile->getTokensAsString(
                            ($prev + 1),
                            ($stackPtr - $prev - 1)
                        );

                        $found = $tokens[$prev]['content'].$tokenContent.$found;

                        if (isset($pattern[($i - 1)]) === true
                            && $pattern[($i - 1)]['type'] === 'skip'
                        ) {
                            $stackPtr = $prev;
                        } else {
                            $stackPtr = ($prev - 1);
                        }
                    }//end if
                } else if ($pattern[$i]['type'] === 'skip') {
                    // Skip to next piece of relevant code.
                    if ($pattern[$i]['to'] === 'parenthesis_closer') {
                        $to = 'parenthesis_opener';
                    } else {
                        $to = 'scope_opener';
                    }

                    // Find the previous opener.
                    $next = $phpcsFile->findPrevious(
                        $ignoreTokens,
                        $stackPtr,
                        null,
                        true
                    );

                    if ($next === false || isset($tokens[$next][$to]) === false) {
                        // If there was not opener, then we must be
                        // using the wrong pattern.
                        return false;
                    }

                    if ($to === 'parenthesis_opener') {
                        $found = '{'.$found;
                    } else {
                        $found = '('.$found;
                    }

                    $found = '...'.$found;

                    // Skip to the opening token.
                    $stackPtr = ($tokens[$next][$to] - 1);
                } else if ($pattern[$i]['type'] === 'string') {
                    $found = 'abc';
                } else if ($pattern[$i]['type'] === 'newline') {
                    if ($this->ignoreComments === true
                        && in_array($tokens[$stackPtr]['code'], PHP_CodeSniffer_Tokens::$commentTokens) === true
                    ) {
                        $startComment = $phpcsFile->findPrevious(
                            PHP_CodeSniffer_Tokens::$commentTokens,
                            ($stackPtr - 1),
                            null,
                            true
                        );

                        if ($tokens[$startComment]['line'] !== $tokens[($startComment + 1)]['line']) {
                            $startComment++;
                        }

                        $tokenContent = $phpcsFile->getTokensAsString(
                            $startComment,
                            ($stackPtr - $startComment + 1)
                        );

                        $found    = $tokenContent.$found;
                        $stackPtr = ($startComment - 1);
                    }

                    if ($tokens[$stackPtr]['code'] === T_WHITESPACE) {
                        if ($tokens[$stackPtr]['content'] !== $phpcsFile->eolChar) {
                            $found = $tokens[$stackPtr]['content'].$found;

                            // This may just be an indent that comes after a newline
                            // so check the token before to make sure. If it is a newline, we
                            // can ignore the error here.
                            if ($tokens[($stackPtr - 1)]['content'] !== $phpcsFile->eolChar) {
                                $hasError = true;
                            } else {
                                $stackPtr--;
                            }
                        } else {
                            $found = 'EOL'.$found;
                        }
                    } else {
                        $found    = $tokens[$stackPtr]['content'].$found;
                        $hasError = true;
                    }

                    if ($hasError === false && $pattern[($i - 1)]['type'] !== 'newline') {
                        // Make sure they only have 1 newline.
                        $prev = $phpcsFile->findPrevious($ignoreTokens, ($stackPtr - 1), null, true);
                        if ($prev !== false && $tokens[$prev]['line'] !== $tokens[$stackPtr]['line']) {
                            $hasError = true;
                        }
                    }
                }//end if
            }//end for
        }//end if

        $stackPtr          = $origStackPtr;
        $lastAddedStackPtr = null;
        $patternLen        = count($pattern);

        for ($i = $patternInfo['listen_pos']; $i < $patternLen; $i++) {
            if ($pattern[$i]['type'] === 'token') {
                if ($pattern[$i]['token'] === T_WHITESPACE) {
                    if ($this->ignoreComments === true) {
                        // If we are ignoring comments, check to see if this current
                        // token is a comment. If so skip it.
                        if (in_array($tokens[$stackPtr]['code'], PHP_CodeSniffer_Tokens::$commentTokens) === true) {
                            continue;
                        }

                        // If the next token is a comment, the we need to skip the
                        // current token as we should allow a space before a
                        // comment for readability.
                        if (in_array($tokens[($stackPtr + 1)]['code'], PHP_CodeSniffer_Tokens::$commentTokens) === true) {
                            continue;
                        }
                    }

                    $tokenContent = '';
                    if ($tokens[$stackPtr]['code'] === T_WHITESPACE) {
                        if (isset($pattern[($i + 1)]) === false) {
                            // This is the last token in the pattern, so just compare
                            // the next token of content.
                            $tokenContent = $tokens[$stackPtr]['content'];
                        } else {
                            // Get all the whitespace to the next token.
                            $next = $phpcsFile->findNext(
                                PHP_CodeSniffer_Tokens::$emptyTokens,
                                $stackPtr,
                                null,
                                true
                            );

                            $tokenContent = $phpcsFile->getTokensAsString(
                                $stackPtr,
                                ($next - $stackPtr)
                            );

                            $lastAddedStackPtr = $stackPtr;
                            $stackPtr          = $next;
                        }

                        if ($stackPtr !== $lastAddedStackPtr) {
                            $found .= $tokenContent;
                        }
                    } else {
                        if ($stackPtr !== $lastAddedStackPtr) {
                            $found            .= $tokens[$stackPtr]['content'];
                            $lastAddedStackPtr = $stackPtr;
                        }
                    }//end if

                    if (isset($pattern[($i + 1)]) === true
                        && $pattern[($i + 1)]['type'] === 'skip'
                    ) {
                        // The next token is a skip token, so we just need to make
                        // sure the whitespace we found has *at least* the
                        // whitespace required.
                        if (strpos($tokenContent, $pattern[$i]['value']) !== 0) {
                            $hasError = true;
                        }
                    } else {
                        if ($tokenContent !== $pattern[$i]['value']) {
                            $hasError = true;
                        }
                    }
                } else {
                    // Check to see if this important token is the same as the
                    // next important token in the pattern. If it is not, then
                    // the pattern cannot be for this piece of code.
                    $next = $phpcsFile->findNext(
                        $ignoreTokens,
                        $stackPtr,
                        null,
                        true
                    );

                    if ($next === false
                        || $tokens[$next]['code'] !== $pattern[$i]['token']
                    ) {
                        // The next important token did not match the pattern.
                        return false;
                    }

                    if ($lastAddedStackPtr !== null) {
                        if (($tokens[$next]['code'] === T_OPEN_CURLY_BRACKET
                            || $tokens[$next]['code'] === T_CLOSE_CURLY_BRACKET)
                            && isset($tokens[$next]['scope_condition']) === true
                            && $tokens[$next]['scope_condition'] > $lastAddedStackPtr
                        ) {
                            // This is a brace, but the owner of it is after the current
                            // token, which means it does not belong to any token in
                            // our pattern. This means the pattern is not for us.
                            return false;
                        }

                        if (($tokens[$next]['code'] === T_OPEN_PARENTHESIS
                            || $tokens[$next]['code'] === T_CLOSE_PARENTHESIS)
                            && isset($tokens[$next]['parenthesis_owner']) === true
                            && $tokens[$next]['parenthesis_owner'] > $lastAddedStackPtr
                        ) {
                            // This is a bracket, but the owner of it is after the current
                            // token, which means it does not belong to any token in
                            // our pattern. This means the pattern is not for us.
                            return false;
                        }
                    }//end if

                    // If we skipped past some whitespace tokens, then add them
                    // to the found string.
                    // if (($next - $stackPtr) > 0) {
                    //     $hasComment = false;
                    //     for ($j = $stackPtr; $j < $next; $j++) {
                    //         $found .= $tokens[$j]['content'];
                    //         if (in_array($tokens[$j]['code'], PHP_CodeSniffer_Tokens::$commentTokens) === true) {
                    //             $hasComment = true;
                    //         }
                    //     }

                    //     // If we are not ignoring comments, this additional
                    //     // whitespace or comment is not allowed. If we are
                    //     // ignoring comments, there needs to be at least one
                    //     // comment for this to be allowed.
                    //     if ($this->ignoreComments === false
                    //         || ($this->ignoreComments === true
                    //         && $hasComment === false)
                    //     ) {
                    //         $hasError = true;
                    //     }

                    //     // Even when ignoring comments, we are not allowed to include
                    //     // newlines without the pattern specifying them, so
                    //     // everything should be on the same line.
                    //     if ($tokens[$next]['line'] !== $tokens[$stackPtr]['line']) {
                    //         $hasError = true;
                    //     }
                    // }//end if

                    if ($next !== $lastAddedStackPtr) {
                        $found            .= $tokens[$next]['content'];
                        $lastAddedStackPtr = $next;
                    }

                    if (isset($pattern[($i + 1)]) === true
                        && $pattern[($i + 1)]['type'] === 'skip'
                    ) {
                        $stackPtr = $next;
                    } else {
                        $stackPtr = ($next + 1);
                    }
                }//end if
            } else if ($pattern[$i]['type'] === 'skip') {
                if ($pattern[$i]['to'] === 'unknown') {
                    $next = $phpcsFile->findNext(
                        $pattern[($i + 1)]['token'],
                        $stackPtr
                    );

                    if ($next === false) {
                        // Couldn't find the next token, sowe we must
                        // be using the wrong pattern.
                        return false;
                    }

                    $found   .= '...';
                    $stackPtr = $next;
                } else {
                    // Find the previous opener.
                    $next = $phpcsFile->findPrevious(
                        PHP_CodeSniffer_Tokens::$blockOpeners,
                        $stackPtr
                    );

                    if ($next === false
                        || isset($tokens[$next][$pattern[$i]['to']]) === false
                    ) {
                        // If there was not opener, then we must
                        // be using the wrong pattern.
                        return false;
                    }

                    $found .= '...';
                    if ($pattern[$i]['to'] === 'parenthesis_closer') {
                        $found .= ')';
                    } else {
                        $found .= '}';
                    }

                    // Skip to the closing token.
                    $stackPtr = ($tokens[$next][$pattern[$i]['to']] + 1);
                }//end if
            } else if ($pattern[$i]['type'] === 'string') {
                if ($tokens[$stackPtr]['code'] !== T_STRING) {
                    $hasError = true;
                }

                if ($stackPtr !== $lastAddedStackPtr) {
                    $found            .= 'abc';
                    $lastAddedStackPtr = $stackPtr;
                }

                $stackPtr++;
            } else if ($pattern[$i]['type'] === 'newline') {
                // Find the next token that contains a newline character.
                $newline = 0;
                for ($j = $stackPtr; $j < $phpcsFile->numTokens; $j++) {
                    if (strpos($tokens[$j]['content'], $phpcsFile->eolChar) !== false) {
                        $newline = $j;
                        break;
                    }
                }

                if ($newline === 0) {
                    // We didn't find a newline character in the rest of the file.
                    $next     = ($phpcsFile->numTokens - 1);
                    $hasError = true;
                } else {
                    if ($this->ignoreComments === false) {
                        // The newline character cannot be part of a comment.
                        if (in_array($tokens[$newline]['code'], PHP_CodeSniffer_Tokens::$commentTokens) === true) {
                            $hasError = true;
                        }
                    }

                    if ($newline === $stackPtr) {
                        $next = ($stackPtr + 1);
                    } else {
                        // Check that there were no significant tokens that we
                        // skipped over to find our newline character.
                        $next = $phpcsFile->findNext(
                            $ignoreTokens,
                            $stackPtr,
                            null,
                            true
                        );

                        if ($next < $newline) {
                            // We skipped a non-ignored token.
                            $hasError = true;
                        } else {
                            $next = ($newline + 1);
                        }
                    }
                }//end if

                if ($stackPtr !== $lastAddedStackPtr) {
                    $found .= $phpcsFile->getTokensAsString(
                        $stackPtr,
                        ($next - $stackPtr)
                    );

                    $diff = ($next - $stackPtr);
                    $lastAddedStackPtr = ($next - 1);
                }

                $stackPtr = $next;
            }//end if
        }//end for

        if ($hasError === true) {
            $error = $this->prepareError($found, $patternCode);
            $errors[$origStackPtr] = $error;
        }

        return $errors;

    }//end processPattern()


}//end class


