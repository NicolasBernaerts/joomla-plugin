<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Content.DisplayFile
 *
 * @copyright   Copyright (C) 2015 Nicolas Bernaerts
 * @license     GNU General Public License version 2 or later;
 */

defined('_JEXEC') or die;

/**
 * Github source plugin class.
 *
 * @package     Joomla.Plugin
 * @subpackage  Content.DisplayFile
 * @since       1.5
 */
class PlgContentDisplayFile extends JPlugin
{
	/**
	 * Plugin that replace all links to external Github source code by the code itself
	 *
	 * @param   string   $context  The context of the content being passed to the plugin.
	 * @param   mixed    &$row     An object with a "text" property or the string to be analysed.
	 * @param   mixed    &$params  Additional parameters.
	 * @param   integer  $page     Optional page number. Unused. Defaults to zero.
	 *
	 * @return  boolean	True on success.
	 */
	function PlgContentDisplayFile ( &$subject, $params )
	{
		parent::__construct ( $subject, $params );
	}

	public function onContentPrepare($context, &$row, &$params, $page = 0)
	{
		// declare embedded style
    		$document = JFactory::getDocument();
    		$document->addStyleSheet(JURI::base(). "plugins/content/displayfile/displayfile.css");

		// Don't run this plugin when the content is being indexed
		if ($context == 'com_finder.indexer') { return true; }

		// if parameter is an object
		if (is_object($row)) return $this->_displayFile ($row->text, $params);

		// else parameter is a string
		else return $this->_displayFile ($row, $params);
	}

	/**
	 * Display source content as a file content
        * Triggered with any chain like {displayfile filename="..." source="https://..." class="..."} {/displayfile}
	 * If external source is used, it should provide raw source content.
	 *
	 * @param   string  &$text    The string where link should be replaced.
	 * @param   mixed   &$params  Additional parameters. Parameter "mode" (integer, default 1)
	 *                             replaces addresses with "mailto:" links if nonzero.
	 *
	 * @return  boolean  True on success.
	 */
	protected function _displayFile ( &$text, &$params )
	{
		// performance tweak : if '{displayfile' not found, exit
		if ( strpos ( $text, "{/displayfile}" ) == FALSE ) return true;
 
		// pattern {displayfile filename='...' source=... class='...'} content of file {/displayfile}
		$strPattern = '/{displayfile([^}]*)}(.*){\/displayfile}/';
		
		// search for pattern in text
		//   $arrMatches[0] contains full displayfile strings => {displayfile filename='...' source=... class='...'} content of file {/displayfile}
		//   $arrMatches[1] contains displayfile parameters   =>  filename='...' source=... class='...'
		//   $arrMatches[2] contains displayfile data         =>  content of file 
		$bFound = preg_match_all ( $strPattern, $text, $arrMatches );
		if ($bFound)
		{
			// get number of results
			$numFile = count ( $arrMatches[0] );

			// loop thru the console patterns
			for ( $i = 0; $i < $numFile; $i++ ) {
				// initialize data
				$strFilename = "";
				$strSource   = "local";
				$strClass    = "none";
				$strLink     = "";

				// retrieve strings from array
				$strValue     = $arrMatches[0][$i];
				$strParameter = trim ( $arrMatches[1][$i] );
				$strContent   = trim ( $arrMatches[2][$i] );

				// load array of parameters without quotes and with quotes and merge both arrays
				preg_match_all ( "/[a-z]+=[^ \']+/", $strParameter, $arrNoQuote );
				preg_match_all ( "/[a-z]+=\'[^\']+\'/", $strParameter, $arrQuote );
				$arrParam = array_merge ( $arrNoQuote[0], $arrQuote[0] );

				// loop thru parameters
				foreach ($arrParam as $strParam) 
				{
					// retrieve left and right part around '='
					$arrPart = explode ( "=", $strParam);
					switch ($arrPart[0]) {
					case "filename":
						$strFilename = trim ( $arrPart[1], "'" );
						break;
					case "source" :
						$strSource = trim ( $arrPart[1], "'" );
						break;
					case "class" :
						$strClass = trim ( $arrPart[1], "'" );
						break;
					}
				}

				// if source is not local, download content
				if ( $strSource != "local") {
					// read URL stream
					$strLink = $strContent;
					$arrLines = file ($strLink);
					$strContent = "";
					foreach ($arrLines as $strLine) 
					{
						// replace spaces by unbreakable spaces and HTML end of line
						$strLine = str_replace (' ', '&nbsp;', $strLine);
						$strLine = str_replace ('\t', '&nbsp;&nbsp;&nbsp;&nbsp;', $strLine);
						$strLine = str_replace ('$', '&#36;', $strLine);
						$strContent .= trim ($strLine) . "<br />\n"; 
					}
				}

				// detect if file is a shell script
				$isShell = strpos ( $strContent, "#!" );
				if ( ( $isShell !== FALSE ) && ( $isShell == 0 ) ) $strClass = "shell";

				// loop thru the stream to generate content
				$strReplacement  = "<div class='displayfile'>\n";
				$strReplacement .= "<div class='filetitle'>";
				$strReplacement .= "<img src='/plugins/content/displayfile/class-" . $strClass . ".png'>";
				$strReplacement .= $strFilename;
				if ( $strLink != "" ) {
					$strReplacement .= "<a href='" . $strLink . "' title='Source is available from " . $strSource . "'>"; 
					$strReplacement .= "<img class='right' src='/plugins/content/displayfile/source-" . $strSource . ".png'>";
					$strReplacement .= "</a>";
				} 
				$strReplacement .= "</div>\n" ;
				$strReplacement .= "<div class='filecontent'>" . $strContent . "</div>";
				$strReplacement .= "</div>";

				// replace chain in original text
				$text = str_replace ( $strValue, $strReplacement, $text );
			}

		}

		// return always ok
		return true;
	}
}
