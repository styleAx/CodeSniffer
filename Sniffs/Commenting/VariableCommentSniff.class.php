<?php
/**
 * Parses and verifies the variable doc comment.
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Rafal Wesolowski <info@styleAx.de>
 * @copyright Nexus
 * @since     28.01.2013
 */

if (class_exists('PHP_CodeSniffer_Standards_AbstractVariableSniff', true) === false)
{
    throw new PHP_CodeSniffer_Exception('Class PHP_CodeSniffer_Standards_AbstractVariableSniff not found');
}

if (class_exists('PHP_CodeSniffer_CommentParser_MemberCommentParser', true) === false)
{
    throw new PHP_CodeSniffer_Exception('Class PHP_CodeSniffer_CommentParser_MemberCommentParser not found');
}

/**
 * Parses and verifies the variable doc comment.
 *
 * Verifies that :
 * <ul>
 *  <li>A variable doc comment exists.</li>
 *  <li>Short description ends with a full stop.</li>
 *  <li>There is a blank line after the short description.</li>
 *  <li>There is a blank line between the description and the tags.</li>
 *  <li>Check the order, indentation and content of each tag.</li>
 * </ul>
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Rafal Wesolowski <info@styleAx.de>
 * @copyright Nexus
 * @version   Release: 1.0
 */

class Nexus_Sniffs_Commenting_VariableCommentSniff extends PHP_CodeSniffer_Standards_AbstractVariableSniff
{

    /**
     * The header comment parser for the current file.
     *
     * @var PHP_CodeSniffer_Comment_Parser_ClassCommentParser
     */
    protected $commentParser = null;


    /**
     * Called to process class member vars.
     *
     * @param PHP_CodeSniffer_File $oPhpcsFile The file being scanned.
     * @param intiger              $iStackPtr  The position of the current token
     *                                         in the stack passed in $aTokens.
     *
     * @return void
     */
    public function processMemberVar(PHP_CodeSniffer_File $oPhpcsFile, $iStackPtr)
    {
        $this->currentFile = $oPhpcsFile;
        $aTokens            = $oPhpcsFile->getTokens();
        $aCommentToken      = array(
                              T_COMMENT,
                              T_DOC_COMMENT,
                             );

        $iCommentEnd = $oPhpcsFile->findPrevious($aCommentToken, ($iStackPtr - 3));

        $this->_hasVariableDocComment($oPhpcsFile, $iStackPtr, $aTokens, $iCommentEnd );

        $iCommentStart  = ($oPhpcsFile->findPrevious(T_DOC_COMMENT, ($iCommentEnd - 1), null, true) + 1);
        $iCommentString = $oPhpcsFile->getTokensAsString($iCommentStart, ($iCommentEnd - $iCommentStart + 1));

        // Parse the header comment docblock.
        try {
            $this->commentParser = new PHP_CodeSniffer_CommentParser_MemberCommentParser($iCommentString, $oPhpcsFile);
            $this->commentParser->parse();
        } catch (PHP_CodeSniffer_CommentParser_ParserException $e) {
            $line = ($e->getLineWithinComment() + $iCommentStart);
            $oPhpcsFile->addError($e->getMessage(), $line, 'ErrorParsing');
            return;
        }

        $comment = $this->commentParser->getComment();
        if (is_null($comment) === true) {
            $error = 'Variable doc comment is empty';
            $oPhpcsFile->addError($error, $iCommentStart, 'Empty');
            return;
        }

        // The first line of the comment should just be the /** code.
        $eolPos    = strpos($iCommentString, $oPhpcsFile->eolChar);
        $firstLine = substr($iCommentString, 0, $eolPos);
        if ($firstLine !== '/**') {
            $error = 'The open comment tag must be the only content on the line';
            $oPhpcsFile->addError($error, $iCommentStart, 'ContentAfterOpen');
        }

        // Check for a comment description.
        $short = $comment->getShortComment();
        $long  = '';
        if (trim($short) === '') {
            $error = 'Missing short description in variable doc comment';
            $oPhpcsFile->addError($error, $iCommentStart, 'MissingShort');
            $newlineCount = 1;
        } else {
            // No extra newline before short description.
            $newlineCount = 0;
            $newlineSpan  = strspn($short, $oPhpcsFile->eolChar);
            if ($short !== '' && $newlineSpan > 0) {
                $error = 'Extra newline(s) found before variable comment short description';
                $oPhpcsFile->addError($error, ($iCommentStart + 1), 'SpacingBeforeShort');
            }

            $newlineCount = (substr_count($short, $oPhpcsFile->eolChar) + 1);

            // Exactly one blank line between short and long description.
            $long = $comment->getLongComment();
            if (empty($long) === false) {
                $between        = $comment->getWhiteSpaceBetween();
                $newlineBetween = substr_count($between, $oPhpcsFile->eolChar);
                if ($newlineBetween !== 2) {
                    $error = 'There must be exactly one blank line between descriptions in variable comment';
                    $oPhpcsFile->addError($error, ($iCommentStart + $newlineCount + 1), 'SpacingBetween');
                }
            }//end if

        }//end if


        // Check each tag.
        $this->processVar($iCommentStart, $iCommentEnd);
        $this->processSees($iCommentStart);

        // The last content should be a newline and the content before
        // that should not be blank. If there is more blank space
        // then they have additional blank lines at the end of the comment.
        $words   = $this->commentParser->getWords();
        $lastPos = (count($words) - 1);
        if (trim($words[($lastPos - 1)]) !== ''
            || strpos($words[($lastPos - 1)], $this->currentFile->eolChar) === false
            || trim($words[($lastPos - 2)]) === ''
        ) {
            $error = 'Additional blank lines found at end of variable comment';
            $this->currentFile->addError($error, $iCommentEnd, 'SpacingAfter');
        }

    }

    /**
     * Check if Variable has a Comment
     *
     * @author Rafal Wesolowski <wesolowski@nexus-netsoft.com>
     * @param PHP_CodeSniffer_File $oPhpcsFile  The file being scanned.
     * @param intiger              $iStackPtr   The position of the current token
     *                                          in the stack passed in $aTokens.
     * @param array                $aTokens     Returns the token stack for this file.
     * @param integer              $iCommentEnd Comment End Line
     * @return boolean
     */
    protected function _hasVariableDocComment(PHP_CodeSniffer_File $oPhpcsFile,
                                              $iStackPtr,
                                              array $aTokens,
                                              $iCommentEnd )
    {
        if ($iCommentEnd !== false && $aTokens[$iCommentEnd]['code'] === T_COMMENT)
        {
            $oPhpcsFile->addError('You must use "/**" style comments for a variable comment', $iStackPtr, 'WrongStyle');
            return;
        } else if ($iCommentEnd === false || $aTokens[$iCommentEnd]['code'] !== T_DOC_COMMENT) {
            $oPhpcsFile->addError('Missing variable doc comment', $iStackPtr, 'Missing');
            return;
        } else {
            // Make sure the comment we have found belongs to us.
            $iCommentFor = $oPhpcsFile->findNext(array(T_VARIABLE, T_CLASS, T_INTERFACE), ($iCommentEnd + 1));
            if ($iCommentFor !== $iStackPtr) {
                $oPhpcsFile->addError('Missing variable doc comment', $iStackPtr, 'Missing');
                return;
            }
        }
    }

    protected function _addErrorMeldung(PHP_CodeSniffer_File $oPhpcsFile, $iStackPtr, $sMessage, $sErrorTitle, $bIsWarning = false)
    {
        if($bIsWarning)
        {

        }
        else
        {

        }
    }

    /**
     * Process the var tag.
     *
     * @param int $iCommentStart The position in the stack where the comment started.
     * @param int $iCommentEnd   The position in the stack where the comment ended.
     *
     * @return void
     */
    protected function processVar($iCommentStart, $iCommentEnd)
    {
        $var = $this->commentParser->getVar();

        if ($var !== null) {
            $errorPos = ($iCommentStart + $var->getLine());
            $index    = array_keys($this->commentParser->getTagOrders(), 'var');

            if (count($index) > 1) {
                $error = 'Only 1 @var tag is allowed in variable comment';
                $this->currentFile->addError($error, $errorPos, 'DuplicateVar');
                return;
            }

            $content = $var->getContent();
            if (empty($content) === true) {
                $error = 'Var type missing for @var tag in variable comment';
                $this->currentFile->addError($error, $errorPos, 'MissingVarType');
                return;
            } else {
                $suggestedType = PHP_CodeSniffer::suggestType($content);
                if ($content !== $suggestedType) {
                    $error = 'Expected "%s"; found "%s" for @var tag in variable comment';
                    $data  = array(
                              $suggestedType,
                              $content,
                             );
                    $this->currentFile->addError($error, $errorPos, 'IncorrectVarType', $data);
                }
            }

            $spacing = substr_count($var->getWhitespaceBeforeContent(), ' ');
            if ($spacing !== 1) {
                $error = '@var tag indented incorrectly; expected 1 space but found %s';
                $data  = array($spacing);
                $this->currentFile->addError($error, $errorPos, 'VarIndent', $data);
            }
        } else {
            $error = 'Missing @var tag in variable comment';
            $this->currentFile->addError($error, $iCommentEnd, 'MissingVar');
        }//end if

    }//end processVar()


    /**
     * Process the see tags.
     *
     * @param int $iCommentStart The position in the stack where the comment started.
     *
     * @return void
     */
    protected function processSees($iCommentStart)
    {
        $sees = $this->commentParser->getSees();
        if (empty($sees) === false) {
            foreach ($sees as $see) {
                $errorPos = ($iCommentStart + $see->getLine());
                $content  = $see->getContent();
                if (empty($content) === true) {
                    $error = 'Content missing for @see tag in variable comment';
                    $this->currentFile->addError($error, $errorPos, 'EmptySees');
                    continue;
                }

                $spacing = substr_count($see->getWhitespaceBeforeContent(), ' ');
                if ($spacing !== 1) {
                    $error = '@see tag indented incorrectly; expected 1 spaces but found %s';
                    $data  = array($spacing);
                    $this->currentFile->addError($error, $errorPos, 'SeesIndent', $data);
                }
            }
        }

    }


    /**
     * Called to process a normal variable.
     *
     * Not required for this sniff.
     *
     * @param PHP_CodeSniffer_File $oPhpcsFile The PHP_CodeSniffer file where this token was found.
     * @param intiger              $iStackPtr  The position where the double quoted
     *                                        string was found.
     *
     * @return void
     */
    protected function processVariable(PHP_CodeSniffer_File $oPhpcsFile, $iStackPtr)
    {
        return;

    }


    /**
     * Called to process variables found in duoble quoted strings.
     *
     * Not required for this sniff.
     *
     * @param PHP_CodeSniffer_File $oPhpcsFile The PHP_CodeSniffer file where this token was found.
     * @param intiger              $iStackPtr  The position where the double quoted
     *                                         string was found.
     *
     * @return void
     */
    protected function processVariableInString(PHP_CodeSniffer_File $oPhpcsFile, $iStackPtr)
    {
        return;

    }


}
