<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Content.DisplayConsole
 *
 * @copyright   Copyright (C) 2015 Nicolas Bernaerts
 * @license     GNU General Public License version 2 or later;
 */

defined('_JEXEC') or die;

/**
 * Github source plugin class.
 *
 * @package     Joomla.Plugin
 * @subpackage  Content.DisplayConsole
 * @since       1.5
 */
class PlgContentDisplayConsole extends JPlugin
{
	/**
	 * Plugin that display console code in console window
	 *
	 * @param   string   $context  The context of the content being passed to the plugin.
	 * @param   mixed    &$row     An object with a "text" property or the string to be analysed.
	 * @param   mixed    &$params  Additional parameters.
	 * @param   integer  $page     Optional page number. Unused. Defaults to zero.
	 *
	 * @return  boolean	True on success.
	 */
	function PlgContentDisplayConsole ( &$subject, $params )
	{
		parent::__construct ( $subject, $params );
	}

	public function onContentPrepare($context, &$row, &$params, $page = 0)
	{
		// declare embedded style
    		$document = JFactory::getDocument();
    		$document->addStyleSheet(JURI::base(). "plugins/content/displayconsole/displayconsole.css");

		// Don't run this plugin when the content is being indexed
		if ($context == 'com_finder.indexer') { return true; }

		// if parameter is an object
		if (is_object($row)) return $this->_displayConsole ($row->text, $params);

		// else parameter is a string
		else return $this->_displayConsole ($row, $params);
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
	protected function _displayConsole ( &$text, &$params )
	{
		// performance tweak : if '{displayconsole' not found, exit
		if ( strpos ( $text, "{/displayconsole}" ) == FALSE ) return true;
 
		// pattern {displayconsole title='...' class=...} content of console {/displayconsole}
		$strPattern = '/{displayconsole([^}]*)}(.*){\/displayconsole}/';
		
		// search for pattern in text
		//   $arrMatches[0] contains full displayconsole strings => {displayconsole title='...' class=...} content of console {/displayconsole}
		//   $arrMatches[1] contains displayconsole parameters   =>  title='...' class=...
		//   $arrMatches[2] contains displayconsole data         =>  content of console 
		$bFound = preg_match_all ( $strPattern, $text, $arrMatches );
		if ($bFound)
		{
			// get number of results
			$numConsole = count ( $arrMatches[0] );

			// loop thru the console patterns
			for ( $i = 0; $i < $numConsole; $i++ ) {
				// initialize data
				$strTitle = "Terminal";
				$strClass = "console";

				// retrieve strings from array
				$strConsole   = $arrMatches[0][$i];
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
					case "title":
						$strTitle = trim ( $arrPart[1], "'" );
						break;
					case "class" :
						$strClass = trim ( $arrPart[1], "'" );
						break;
					}
				}

				// generate replacement string
				$strReplacement  = "<div class='displayconsole'>\n";
				$strReplacement .= "<div class='consoletitle'>";
				$strReplacement .= "<img src='/plugins/content/displayconsole/class-" . $strClass . ".png'>";
				$strReplacement .= $strTitle;
				$strReplacement .= "</div>\n" ;
				$strReplacement .= "<div class='consolecontent'>" . $strContent . "</div>";
				$strReplacement .= "</div>";

				// replace console string in original text
				$text = str_replace ( $strConsole, $strReplacement, $text );
			}

		}

		// return always ok
		return true;
	}
}
