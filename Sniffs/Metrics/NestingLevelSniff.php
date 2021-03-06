<?php
/**
 * Checks the nesting level for methods.
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

/**
 * Checks the nesting level for methods.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Johann-Peter Hartmann <hartmann@mayflower.de>
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2007 Mayflower GmbH
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @version   Release: 1.4.3
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class Nexus_Sniffs_Metrics_NestingLevelSniff implements PHP_CodeSniffer_Sniff
{



    /**
     * A nesting level than this value will throw an error.
     *
     * @var int
     */
    public $iErrorNestingLevel = 5;


    public $iWarningNestingLevel = 3;

    public $iWarningComplexitySemicolon = 5;

    public $iErrorComplexitySemicolon = 8;
    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(
                 T_CASE,
                 T_CATCH,
                 T_IF,
                 T_FOR,
                 T_FOREACH,
                 T_WHILE,
                 T_DO,
                 T_ELSEIF,
                 T_ELSE
        );

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token
     *                                        in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if($tokens[$stackPtr]['level'] >= $this->iErrorNestingLevel )
        {
            // print_r($tokens);
            // echo PHP_EOL . $stackPtr . PHP_EOL;
            $error = 'Nesting level (%s) exceeds allowed maximum of %s';
            $data  = array(
                      $tokens[$stackPtr]['level'],
                      $this->iErrorNestingLevel
                     );
            $phpcsFile->addError($error, $stackPtr, 'MaxExceededError', $data);


        }
        elseif($tokens[$stackPtr]['level'] >= $this->iWarningNestingLevel )
        {
            // print_r($tokens);
            // echo PHP_EOL . $stackPtr . PHP_EOL;
            $error = 'Nesting level (%s) exceeds recommended maximum of %s';
            $data  = array(
                      $tokens[$stackPtr]['level'],
                      $this->iWarningNestingLevel
                     );
            $phpcsFile->addWarning($error, $stackPtr, 'MaxExceededWarn', $data);


        }
        if( !isset($tokens[$stackPtr]['scope_opener']) && !isset($tokens[$stackPtr]['scope_closer']) )
        {
            return;
        }
        $start = $tokens[$stackPtr]['scope_opener'];
        $end   = $tokens[$stackPtr]['scope_closer'];

        $find = array(
                 'T_CASE',
                 'T_DEFAULT',
                 'T_CATCH',
                 'T_IF',
                 'T_FOR',
                 'T_FOREACH',
                 'T_WHILE',
                 'T_DO',
                 'T_ELSEIF',
                );

        $iComplexity = 0;


        for ($i = ($start + 1); $i < $end; $i++)
        {
            if(in_array($tokens[$i]['type'], $find) === true)
            {
                break;
            }
            if ($tokens[$i]['type'] === 'T_SEMICOLON')
            {
                $iComplexity++;
            }
        }

        if($iComplexity > $this->iErrorComplexitySemicolon)
        {
            $error = 'Nesting Semicolon level (%s) exceeds allowed maximum of %s';
            $data  = array(
                      $iComplexity,
                      $this->iErrorComplexitySemicolon
                     );
            $phpcsFile->addError($error, $stackPtr, 'MaxSemicolonError', $data);
        }
        elseif ($iComplexity > $this->iWarningComplexitySemicolon)
        {
            $error = 'Nesting Semicolon level (%s) exceeds recommended maximum of %s';
            $data  = array(
                      $iComplexity,
                      $this->iWarningComplexitySemicolon
                     );
            $phpcsFile->addError($error, $stackPtr, 'MaxSemicolonWarn', $data);
        }

    }//end process()


}//end class

?>
