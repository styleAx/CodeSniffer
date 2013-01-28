<?php
/**
 * Squiz_Sniffs_Classes_ClassFileNameSniff.
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
 * Squiz_Sniffs_Classes_ClassFileNameSniff.
 *
 * Tests that the file name and the name of the class contained within the file
 * match.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006-2012 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class Nexus_Sniffs_Files_FileNameSufixSniff implements PHP_CodeSniffer_Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(
                T_INTERFACE,
                T_ABSTRACT
               );

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token in the
     *                                        stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens   = $phpcsFile->getTokens();
        $fullPath = basename($phpcsFile->getFilename());
        $sFileName = substr($fullPath, 0, strrpos($fullPath, '.'));
        if( $iCut = strrpos($sFileName, '.') )
        {
            $iCut += 1;
        }
        else
        {
            $iCut = 0;
        }
        $sFileSufix = substr($sFileName, $iCut);

        if( $phpcsFile->findNext(T_ABSTRACT, $stackPtr) )
        {
            $sSufixName = 'abstract';
        }
        elseif( $phpcsFile->findNext(T_INTERFACE, $stackPtr ))
        {
            $sSufixName = 'iface';
        }
        else
        {
            $sSufixName = '';
        }

        // if ($sFileSufix !== $sSufixName)
        // {
        //     if( $sFileSufix == $sFileName )
        //     {
        //         $sErrFileName = $sFileName;
        //     }
        //     else
        //     {
        //         $sErrFileName = substr($fullPath, 0, strpos($fullPath, '.'));
        //     }

        //     $error = 'Wrong filename; expected "%s.%s.php"';
        //     $data  = array(
        //                 $sErrFileName,
        //                 $sSufixName,
        //              );
        //     $phpcsFile->addError($error, $stackPtr, 'WrongFilename', $data);
        // }

    }//end process()


}//end class

?>