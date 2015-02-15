<?php 
require_once(dirname(dirname(__FILE__)).'/Morpheus.php');

class Morpheus_analyse_template{
	function skin_url(){ return dirname(__FILE__).'/'; }
}

switch(strtolower($_GET['step'])){
	case 'input': /* open [skin|archive|morph-rules]* and temporarily save */
		break;
	case 'index': /* index files (from temporarily directory) */
		break;
	case 'analyse': /* compare selected *.html files > morph-rules */
		break;
	case 'edit': /* edit and extent morph-rules */
		break;
	case 'output': /* apply morph-rules and export adapted template as archive, or update skin */
		break;
	default:
}
?>
