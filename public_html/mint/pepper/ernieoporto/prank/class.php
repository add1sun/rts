<?php
/******************************************************************************
 Pepper
 
 Developer: Ernie Oporto
 Plug-in Name: Prank
 
 http://www.shokk.com/blog/articles/2007/01/30/google-pagerank-pepper-for-mint-prank
 
 Displays the pagerank of your popular pages
 ******************************************************************************/

if (!defined('MINT')) { header('Location:/'); }; // Prevent viewing this file 

$installPepper = "EO_Prank";
include_once('pepper/ernieoporto/prank/pagerank.php');
	
class EO_Prank extends Pepper
{
	var $version	= 103; 
	var $info		= array
	(
		'pepperName'	=> 'Prank',
		'pepperUrl'	=> 'http://www.shokk.com/blog/articles/2007/01/30/google-pagerank-pepper-for-mint-prank',
		'pepperDesc'	=> 'Displays the pagerank of your pages.',
		'developerName'	=> 'Ernie Oporto',
		'developerUrl'	=> 'http://www.shokk.com/blog/'
	);
	var $panes = array
	(
		'Prank' => array
		(
			'Refresh'
		)
	);
        var $prefs = array
        (
             'prankCacheDays' => 7
        );
        var $manifest = array( );
        var $data = array( );
	
	/**************************************************************************
	 isCompatible()
	 **************************************************************************/
	function isCompatible()
	{
		if ($this->Mint->version >= 120)
		{
			return array
			(
				'isCompatible'	=> true
			);
		}
		else
		{
			return array
			(
				'isCompatible'	=> false,
				'explanation'	=> '<p>This Pepper is only compatible with Mint 1.2 and higher.</p>'
		);
		}
	}
	
        function onJavaScript() 
        {       
            return array();
        }

        function onRecord()
        {       
            return array();
        }
	
	/**************************************************************************
	 onDisplay()
	 **************************************************************************/
	function onDisplay($pane, $tab, $column = '', $sort = '')
	{
		$html = '';
		switch($pane) 
		{
			case 'Prank': 
				switch($tab)
				{
					case 'Refresh':
					    $html .= $this->getHTML_PageRanks();
					break;
				}
			break;
		}
		return $html;
	}
	
	/**************************************************************************
	 onDisplayPreferences()
	 **************************************************************************/
	function onDisplayPreferences() 
	{
            $prankCacheDays = $this->prefs['prankCacheDays'];
            $preferences['Cache Time'] = "<table><tr><td><label for=\"prankCacheDays\">Cache Google Pagerank for <input maxlength=\"1\" type=\"text\" name=\"prankCacheDays\" value=\"" . $prankCacheDays . "\" style=\"width:20px; font-size: 10px;\"/>&nbsp;days</label></td></tr></table>";
            return $preferences;
	}
	
	/**************************************************************************
	 onSavePreferences()
	 **************************************************************************/
	function onSavePreferences() 
	{
            $this->prefs['prankCacheDays'] = (isset($_POST['prankCacheDays'])) ? $_POST['prankCacheDays'] : 7;
	}
	
	/**************************************************************************
	onCustom()
	**************************************************************************/
	function onCustom() { }		
	
	/**************************************************************************
	 getHTML_PageRanks()
	 **************************************************************************/
	function getHTML_PageRanks()		
	{		
                $html = '';

                $EO_Prank_cache_path = "pepper/ernieoporto/prank/cache/";
                $EO_Prank_cache_limit = $this->prefs['prankCacheDays'] *24*3600;

                $tableData['table'] = array('id'=>'','class'=>'');
		$html .= $this->Mint->generateTable($tableData);
                unset($tableData);
                $html .= "&nbsp;&nbsp;<font color=\"white\">Google PageRank for top Popular pages</font>";
		$tableData['thead'] = array
                (
                        // display name, CSS class(es) for each column
                        array('value'=>'Page','class'=>''),
                        array('value'=>'Rank','class'=>'')
                );

                          #WHERE `resource` not like '%ownloads%'
                $query = "SELECT `resource`, `resource_checksum`, `resource_title`, COUNT(`resource_checksum`) as `total`, `dt`
                          FROM `{$this->Mint->db['tblPrefix']}visit` 
                          GROUP BY `resource_checksum` 
                          ORDER BY `total` DESC, `dt` DESC
                          LIMIT 0,{$this->Mint->cfg['preferences']['rows']}";
                if ($result = $this->query($query))
                {
                        while ($r = mysql_fetch_array($result))
                        {
                                $res_title = $this->Mint->abbr((!empty($r['resource_title']))?stripslashes($r['resource_title']):$r['resource']);
                                $res_html = "<a href=\"{$r['resource']}\">$res_title</a>";
                                $rank = " <img src=\"pepper/ernieoporto/prank/pr".getrank($r['resource'],$EO_Prank_cache_path,$EO_Prank_cache_limit).".gif\" />";
                                $tableData['tbody'][] = array
                                (
                                        $res_html,
                                        $rank
                                );
                        }

		        $html .= $this->Mint->generateTable($tableData);
                        unset($tableData);
		        return $html;
                }
	}
}
