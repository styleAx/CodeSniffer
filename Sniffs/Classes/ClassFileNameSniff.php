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
 * @version   Release: 1.4.3
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class Nexus_Sniffs_Classes_ClassFileNameSniff implements PHP_CodeSniffer_Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(
                T_CLASS,
                T_INTERFACE
        );

    }


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $oPhpcsFile The file being scanned.
     * @param int                  $iStackPtr  The position of the current token in the
     *                                        stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $oPhpcsFile, $iStackPtr)
    {
        $tokens   = $oPhpcsFile->getTokens();
        $decName  = $oPhpcsFile->findNext(T_STRING, $iStackPtr);
        $sClassName = $sFullClassName = $tokens[$decName]['content'];
        $this->_checkClassNameToFileName( $oPhpcsFile, $iStackPtr, $sClassName);
        $this->_checkUcFirstClass( $oPhpcsFile, $iStackPtr, $sClassName);

    }


    /**
     * [_checkClassNameToFileName description2]
     *
     * @author Rafal                Wesolowski <wesolowski@nexus-netsoft.com>
     * @param PHP_CodeSniffer_File $oPhpcsFile The file being scanned.
     * @param int                  $iStackPtr  The position of the current token in the
     *                                        stack passed in $tokens.
     * @param  string $sClassName  Class Name
     * @return void
     */
    protected function _checkClassNameToFileName(PHP_CodeSniffer_File $oPhpcsFile, $iStackPtr, $sClassName)
    {
        $sNameFromFile = basename($oPhpcsFile->getFilename());
        $sClassName = $this->_isClassNameNotStandard( $sClassName );
        $sSufixName = $this->_getRealFileSufix( $oPhpcsFile, $iStackPtr );

        $sNameFromClass = (string) $sClassName .'.'. $sSufixName . '.php';

        if ($sNameFromFile !== $sNameFromClass) {
            $this->_setErrorMessage(
                $oPhpcsFile,
                '%s name doesn\'t match filename; expected "%s"',
                $iStackPtr ,
                'NoMatch',
                array($sNameFromFile, $sNameFromClass )
            );
        }
    }

    protected function _getRealFileSufix( PHP_CodeSniffer_File $oPhpcsFile, $iStackPtr )
    {
        if( $oPhpcsFile->findFirstOnLine(T_ABSTRACT, $iStackPtr) )
        {
            $sSufixName = 'abstract';
        }
        elseif( $oPhpcsFile->findFirstOnLine(T_INTERFACE, $iStackPtr ))
        {
            $sSufixName = 'iface';
        }
        else
        {
            $sSufixName = 'class';
        }
        return $sSufixName;
    }

    protected function _isClassNameNotStandard( $sClassName )
    {
        if( strpos($sClassName, 'NXS_Modules') !== false )
        {
            $sClassName = $this->_getClassNameFromLastUnderscore( $sClassName );
        }
        return $sClassName;
    }

    protected function _getClassNameFromLastUnderscore( $sClassName )
    {
        $aClassName = explode('_', $sClassName);
        return end($aClassName);
    }

    /**
     *
     *
     * @author Rafal                Wesolowski <wesolowski@nexus-netsoft.com>
     * @param PHP_CodeSniffer_File $oPhpcsFile The file being scanned.
     * @param int                  $iStackPtr  The position of the current token in the
     *                                        stack passed in $tokens.
     * @param  string $sClassName  Class Name
     * @return void
     */
    protected function _checkUcFirstClass(PHP_CodeSniffer_File $oPhpcsFile, $iStackPtr, $sClassName)
    {
        if($sClassName[0] !== strtoupper($sClassName[0]))
        {
            $this->_setErrorMessage(
                $oPhpcsFile,
                'Classes begin with a capital letter; find "%s"; expected "%s%s"',
                $iStackPtr,
                'ucFirstChar',
                array( $sClassName, strtoupper($sClassName[0]), substr($sClassName, 1) )
            );
        }
    }

    /**
     * [_setErrorMessage description]
     *
     * @author Rafal Wesolowski <wesolowski@nexus-netsoft.com>
     * @param  PHP_CodeSniffer_File $oPhpcsFile  The file being scanned.
     * @param  string               $sErrorMsg   [description]
     * @param  int                  $iStackPtr   The position of the current token in the
     *                                           stack passed in $tokens.
     * @param  string               $sErrorTitle [description]
     * @param  array                $aData       [description]
     * @return void                              [description]
     */
    protected function _setErrorMessage(PHP_CodeSniffer_File $oPhpcsFile, $sErrorMsg, $iStackPtr, $sErrorTitle, $aData)
    {
        $oPhpcsFile->addError($sErrorMsg, $iStackPtr, $sErrorTitle, $aData);
    }

}


