<?php
if (!class_exists('hits_pbr_db')) 
{

    class hits_pbr_db 
	{
		var $tableName;
		var $hits_pbr_db_version="1.0";
		
        function hits_pbr(){$this->__construct();}
        function __construct()
		{
			global $wpdb;
			$this->tableName = $wpdb->prefix . 'hits_pbr_pages';
		}
		function createPagesTable()
		{
			global $wpdb;
			$sql = "CREATE TABLE " . $this->tableName . " (
					Id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
					PageId MEDIUMINT(9) NOT NULL,
					SortOrder SMALLINT(3) NOT NULL,
					OverrideText varchar(50),
					AccessRole tinyint NOT NULL,
					UNIQUE KEY  id (Id)
					);";
			
			$results = $wpdb->query($sql);
		}
		
		function install()
		{
			global $wpdb;
			$this->createPagesTable();
			
			add_option("hits_pbr_db_version",$this->hits_pbr_db_version);
		}
		
		function verifyDbState()
		{
			global $wpdb;
			echo "<!-- Verifying Database State -->";
			$value = get_option("hits_pbr_db_version");
			$tableCheck = "SHOW TABLES LIKE '$this->tableName';";
			echo "<!-- $tableCheck -->";
			if($wpdb->get_var($tableCheck) == $this->tableName)
			{
				echo "<!-- Table Exists -->";
			}
			else
			{
				echo "<!--DB missing -->";
				$this->install();
				$this->populateDefaults();
			}
		}
		
		function populateDefaults()
		{
			global $wpdb;
			$this->add_page(-1,0,'');
			$wpdb->query($insert);
			
		}
		
		function get_pages()
		{
			global $wpdb;
			$select = "SELECT PageId,AccessRole,OverrideText FROM " . $this->tableName . " ORDER BY SortOrder Asc;";
			$results = $wpdb->get_results($select);
			return $results;
			
		}
		
		function add_page($page_ID,$page_MinAccess,$page_OverrideText)
		{
			global $wpdb;
			$sortOrder=0;
			//get current max sort number
			$select = "SELECT MAX(SortOrder)+1 FROM " . $this->tableName . ";";
			$result = $wpdb->get_var($wpdb->prepare($select));
			if($result>0)
				$sortOrder = $result;
			else
				$sortOrder=1;
			
			//add record with higher sort number, placing it at the bottom
			$insert = "INSERT INTO " . $this->tableName . " (PageId,AccessRole,OverrideText,SortOrder) " .
				"VALUES ($page_ID,$page_MinAccess,'$page_OverrideText',$sortOrder);";
			$wpdb->query($insert);
		}
		
		function remove_page($page_ID)
		{
			global $wpdb;
			//get the sort number of the page being removed
			$select = "SELECT SortOrder FROM " . $this->tableName . " WHERE PageId=$page_ID;";
			$result = $wpdb->get_var($select);
			
			//remove the page
			$delete = "DELETE FROM " . $this->tableName . " WHERE PageId=$page_ID;";
			$result1 = $wpdb->query($delete);
			
			//update the sort orders to fill in the gap
			$update = "UPDATE " . $this->tableName . " SET SortOrder = SortOrder-1 WHERE SortOrder>$result;";
			$result1 = $wpdb->query($update);
		}
		
		function movePageUp($page_ID)
		{
			global $wpdb;
			$returnPageId=$page_ID;
			//get the top 2 pages containing the sortid of the page being moved
			$select = "SELECT PageId,SortOrder 
						FROM " . $this->tableName . "
					    WHERE SortOrder <= (SELECT SortOrder 
											FROM " . $this->tableName . " 
											WHERE PageId=$page_ID)
					  ORDER BY SortOrder DESC
					  LIMIT 2;";
			$results = $wpdb->get_results($select,ARRAY_N);
			
			if(count($results)==2)
			{
				//swap the ids of the 2 pages
				$update1="UPDATE " . $this->tableName . " SET SortOrder=".$results[0][1]." WHERE PageId=".$results[1][0].";";
				$update2="UPDATE " . $this->tableName . " SET SortOrder=".$results[1][1]." WHERE PageId=".$results[0][0].";";
				
				$ignoreResult=$wpdb->query($update1);
				$ignoreResult=$wpdb->query($update2);
				
				//return the id of the page that is being swapped with
				$returnPageId=$results[1][0];
				return $returnPageId;
			}
			return '';
		}
		
		function movePageDown($page_ID)
		{
			global $wpdb;
			$returnPageId=$page_ID;
			//get the top 2 pages containing the sortid of the page being moved
			$select = "SELECT PageId,SortOrder 
						FROM " . $this->tableName . "
					    WHERE SortOrder >= (SELECT SortOrder 
											FROM " . $this->tableName . "
											WHERE PageId=$page_ID)
					  ORDER BY SortOrder ASC
					  LIMIT 2;";
			$results = $wpdb->get_results($select,ARRAY_N);
			if(count($results)==2)
			{
				//swap the ids of the 2 pages
				$update1="UPDATE " . $this->tableName . " SET SortOrder=".$results[0][1]." WHERE PageId=".$results[1][0].";";
				$update2="UPDATE " . $this->tableName . " SET SortOrder=".$results[1][1]." WHERE PageId=".$results[0][0].";";
				
				$ignoreResult=$wpdb->query($update1);
				$ignoreResult=$wpdb->query($update2);
				
				//return the id of the page that is being swapped with
				$returnPageId=$results[1][0];
				return $returnPageId;
			}
			return '';
		}
	}
}
?>