<?php
/**
 * Parses and verifies the class doc comment.
 *
 * PHP version 5
 *
 * @package   NXS_Modules
 * @author    Rafal Wesolowski <wesolowski@nexus-netsoft.com>
 * @copyright BSD Licence
 * @since     09.01.2013
 */

if (class_exists('PHP_CodeSniffer_CommentParser_ClassCommentParser', true) === false)
{
    throw new PHP_CodeSniffer_Exception('Class PHP_CodeSniffer_CommentParser_ClassCommentParser not found');
}

/**
 * Parses and verifies the class doc comment.
 *
 * Verifies that :
 * <ul>
 *  <li>A class doc comment exists.</li>
 *  <li>There is exactly one blank line before the class comment.</li>
 *  <li>There is a blank line after the short description.</li>
 *  <li>There is a blank line between the description and the tags.</li>
 *  <li>Check the format of the since tag (x.x.x).</li>
 * </ul>
 *
 * @category   PHP
 * @package    PHP_CodeSniffer
 * @subpackage Nexus
 * @author     Rafal Wesolowski <wesolowski@nexus-netsoft.com>
 * @version    1.0
 */
class Nexus_Sniffs_Commenting_ClassCommentSniff implements PHP_CodeSniffer_Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_CLASS);

    }


    /**
     * Returns the allowed tags withing a class comment.
     *
     * @var array
     */
    protected $aMandatoryTags = array(
                'package',
                'subpackage',
                'author'
    );

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @author Rafal Wesolowski <wesolowski@nexus-netsoft.com>
     * @param PHP_CodeSniffer_File $oPhpcsFile The file being scanned.
     * @param integer              $iStackPtr  The position of the current token
     *                                        in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $oPhpcsFile, $iStackPtr)
    {

        $this->currentFile = $oPhpcsFile;

        $aTokens = $oPhpcsFile->getTokens();

        $iCommentEnd = $this->_getCommentEnd( $oPhpcsFile, $iStackPtr );
        $bIssetDocCom = $this->_issetDocComment( $oPhpcsFile, $iStackPtr, $iCommentEnd, $aTokens );

        if( $bIssetDocCom )
        {
            $this->_checkDocComment( $oPhpcsFile, $iStackPtr, $iCommentEnd, $aTokens );
        }
    }

    /**
     * [_getCommentEnd description2]
     *
     * @author Rafal                Wesolowski  <wesolowski@nexus-netsoft.com>
     * @param  PHP_CodeSniffer_File $oPhpcsFile The file being scanned.
     * @param  integer              $iStackPtr  The position of the current token in the stack passed in $tokens.
     * @return void
     */
    protected function _getCommentEnd(PHP_CodeSniffer_File $oPhpcsFile, $iStackPtr)
    {
        $aFind   = array(
               T_ABSTRACT,
               T_WHITESPACE,
               T_FINAL,
        );
        return $oPhpcsFile->findPrevious($aFind, ($iStackPtr - 1), null, true);
    }

    /**
     * [_issetDocComment description2]
     *
     * @author Rafal                Wesolowski   <wesolowski@nexus-netsoft.com>
     * @param  PHP_CodeSniffer_File $oPhpcsFile The file being scanned.
     * @param  integer              $iStackPtr  The position of the current token in the stack passed in $tokens.
     * @param  integer              $iCommentEnd [Description]
     * @param  array                $aTokens     [Description]
     * @return void
     */
    protected function _issetDocComment(PHP_CodeSniffer_File $oPhpcsFile, $iStackPtr, $iCommentEnd, array $aTokens)
    {
        $bResult = false;
        if ($iCommentEnd !== false && $aTokens[$iCommentEnd]['code'] === T_COMMENT)
        {
            $oPhpcsFile->addError('You must use "/**" style comments for a class comment', $iStackPtr, 'WrongStyle');
        }
        else if ($iCommentEnd === false || $aTokens[$iCommentEnd]['code'] !== T_DOC_COMMENT)
        {
            $oPhpcsFile->addError('Missing class doc comment', $iStackPtr, 'Missing');
        }
        else
        {
            $bResult = true;
        }
        return $bResult;
    }

    /**
     * [_checkDocComment description2]
     *
     * @author Rafal                Wesolowski  <wesolowski@nexus-netsoft.com>
     * @param  PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param  integer              $stackPtr  The position of the current token in the stack passed in $tokens.
     * @param  [type]               $commentEnd [Description]
     * @param  array                $tokens     [Description]
     * @return void
     */
    protected function _checkDocComment( PHP_CodeSniffer_File $phpcsFile, $stackPtr, $commentEnd, array $tokens)
    {
            $commentStart = ($phpcsFile->findPrevious(T_DOC_COMMENT, ($commentEnd - 1), null, true) + 1);
            $commentNext  = $phpcsFile->findPrevious(T_WHITESPACE, ($commentEnd + 1), $stackPtr, false, $phpcsFile->eolChar);

            // Distinguish file and class comment.
            $prevClassToken = $phpcsFile->findPrevious(T_CLASS, ($stackPtr - 1));
            if ($prevClassToken === false)
            {
                // This is the first class token in this file, need extra checks.
                $prevNonComment = $phpcsFile->findPrevious(T_DOC_COMMENT, ($commentStart - 1), null, true);
                if ($prevNonComment !== false)
                {
                    $prevComment = $phpcsFile->findPrevious(T_DOC_COMMENT, ($prevNonComment - 1));
                    if ($prevComment === false)
                    {
                        // There is only 1 doc comment between open tag and class token.
                        $newlineToken = $phpcsFile->findNext(T_WHITESPACE, ($commentEnd + 1), $stackPtr, false, $phpcsFile->eolChar);
                        if ($newlineToken !== false) {
                            $newlineToken = $phpcsFile->findNext(T_WHITESPACE, ($newlineToken + 1), $stackPtr, false, $phpcsFile->eolChar);
                            if ($newlineToken !== false) {
                                // Blank line between the class and the doc block.
                                // The doc block is most likely a file comment.
                                $phpcsFile->addError('Missing class doc comment', ($stackPtr + 1), 'Missing');
                                return;
                            }
                        }//end if
                    }//end if

                    // Exactly one blank line before the class comment.
                    $prevTokenEnd = $phpcsFile->findPrevious(T_WHITESPACE, ($commentStart - 1), null, true);
                    if ($prevTokenEnd !== false)
                    {
                        $blankLineBefore = 0;
                        for ($i = ($prevTokenEnd + 1); $i < $commentStart; $i++) {
                            if ($tokens[$i]['code'] === T_WHITESPACE && $tokens[$i]['content'] === $phpcsFile->eolChar) {
                                $blankLineBefore++;
                            }
                        }

                        if ($blankLineBefore !== 2) {
                            $error = 'There must be exactly one blank line before the class comment';
                            $phpcsFile->addError($error, ($commentStart - 1), 'SpacingBefore');
                        }
                    }

                }//end if
            }//end if

            $commentString = $phpcsFile->getTokensAsString($commentStart, ($commentEnd - $commentStart + 1));

            // Parse the class comment docblock.
            try {
                $this->commentParser = new PHP_CodeSniffer_CommentParser_ClassCommentParser($commentString, $phpcsFile);
                $this->commentParser->parse();
            } catch (PHP_CodeSniffer_CommentParser_ParserException $e) {
                $line = ($e->getLineWithinComment() + $commentStart);
                $phpcsFile->addError($e->getMessage(), $line, 'FailedParse');
                return;
            }
            $comment = $this->commentParser->getComment();
            if (is_null($comment) === true) {
                $error = 'Class doc comment is empty';
                $phpcsFile->addError($error, $commentStart, 'Empty');
                return;
            }

            // The first line of the comment should just be the /** code.
            $eolPos    = strpos($commentString, $phpcsFile->eolChar);
            $firstLine = substr($commentString, 0, $eolPos);
            if ($firstLine !== '/**') {
                $error = 'The open comment tag must be the only content on the line';
                $phpcsFile->addError($error, $commentStart, 'SpacingAfterOpen');
            }

            // Check for a comment description.
            $short = rtrim($comment->getShortComment(), $phpcsFile->eolChar);
            if (trim($short) === '') {
                $error = 'Missing short description in class doc comment';
                $phpcsFile->addWarning($error, $commentStart, 'MissingShort');
                return;
            }

            // No extra newline before short description.
            $newlineCount = 0;
            $newlineSpan  = strspn($short, $phpcsFile->eolChar);
            if ($short !== '' && $newlineSpan > 0) {
                $error = 'Extra newline(s) found before class comment short description';
                $phpcsFile->addWarning($error, ($commentStart + 1), 'SpacingBeforeShort');
            }

            $newlineCount = (substr_count($short, $phpcsFile->eolChar) + 1);

            // Exactly one blank line between short and long description.
            $long = $comment->getLongComment();
            if (empty($long) === false) {
                $between        = $comment->getWhiteSpaceBetween();
                $newlineBetween = substr_count($between, $phpcsFile->eolChar);
                if ($newlineBetween !== 2) {
                    $error = 'There must be exactly one blank line between descriptions in class comment';
                    $phpcsFile->addWarning($error, ($commentStart + $newlineCount + 1), 'SpacingBetween');
                }
            }

            // Exactly one blank line before tags.
            $tags = $this->commentParser->getTagOrders();
            if (count($tags) > 1) {
                $newlineSpan = $comment->getNewlineAfter();
                if ($newlineSpan !== 2) {
                    $error = 'There must be exactly one blank line before the tags in class comment';
                    if ($long !== '') {
                        $newlineCount += (substr_count($long, $phpcsFile->eolChar) - $newlineSpan + 1);
                    }

                    $phpcsFile->addWarning($error, ($commentStart + $newlineCount), 'SpacingBeforeTags');
                    $short = rtrim($short, $phpcsFile->eolChar.' ');
                }
            }




            // No tags are allowed in the class comment.
            $tags = $this->commentParser->getTags();

            $aCommentTag = array();
            foreach ($tags as $sTag)
            {
                $aCommentTag[strtolower($sTag['tag'])] = $sTag['line'];
                // $error = '@%s tag is not allowed in class comment';
                // $data  = array($errorTag['tag']);
                // $phpcsFile->addWarning($error, ($commentStart + $errorTag['line']), 'TagNotAllowed', $data);
            }

            $aCommentDiff = array_diff($this->aMandatoryTags, array_keys($aCommentTag));

            foreach ($aCommentDiff as $sComment)
            {
                $aCommentTag[strtolower($sTag['tag'])] = $sTag['line'];
                $error = 'Missing @%s tag in class comment';
                $data  = array($sComment);
                $phpcsFile->addError($error, ($commentStart + $aCommentTag[$sComment]), 'MissingTag', $data);
            }

            // The last content should be a newline and the content before
            // that should not be blank. If there is more blank space
            // then they have additional blank lines at the end of the comment.
            $words   = $this->commentParser->getWords();
            $lastPos = (count($words) - 1);
            if (trim($words[($lastPos - 1)]) !== ''
                || strpos($words[($lastPos - 1)], $this->currentFile->eolChar) === false
                || trim($words[($lastPos - 2)]) === ''
            ) {
                $error = 'Additional blank lines found at end of class comment';
                $this->currentFile->addWarning($error, $commentEnd, 'SpacingAfter');
            }
    }



}

