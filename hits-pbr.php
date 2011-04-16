<?php
/*
	Plugin Name: HITS- Pages by Role
	Version: 1.3.1
	Author: Adam Erstelle
	Author URI: http://www.itegritysolutions.ca/
	Plugin URI: http://www.itegritysolutions.ca/community/wordpress/pages-by-role/
	Description: Provides a Pages Widget that allows customizations of links per user level
	Text Domain: hits-pbr
	
	PLEASE NOTE: If you make any modifications to this plugin file directly, please contact me so that
	             the plugin can be updated for others to enjoy the same freedom and functionality you
				 are trying to add. Thank you!
	
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
/**
* Guess the wp-content and plugin urls/paths
*/
// Pre-WP-2.6 compatibility
if ( ! defined( 'WP_CONTENT_URL' ) )
      define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
if ( ! defined( 'WP_CONTENT_DIR' ) )
      define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
if ( ! defined( 'WP_PLUGIN_URL' ) )
      define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
if ( ! defined( 'WP_PLUGIN_DIR' ) )
      define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
	  
require_once WP_PLUGIN_DIR . '/' . dirname(plugin_basename(__FILE__)).'/hits-db.php';

if (!class_exists('hits_pbr')) {
    class hits_pbr {
        /**
        * @var string The options string name for this plugin
        */
        var $optionsName = 'hits_pbr_options';
        var $wp_version;
		var $version = '1.3.1';        
		
		/**
        * @var string $pluginurl The path to this plugin
        */ 
        var $thispluginurl = '';
        /**
        * @var string $pluginurlpath The path to this plugin
        */
        var $thispluginpath = '';
            
        /**
        * @var array $options Stores the options for this plugin
        */
        var $options = array();
		
		var $hits_pbr_db;
        
        /**
        * PHP 4 Compatible Constructor
        */
        function hits_pbr($pbr_db){$this->__construct($pbr_db);}
        
        /**
        * PHP 5 Constructor
        */        
        function __construct($pbr_db){
            //Language Setup
            $locale = get_locale();
            $mo = dirname(__FILE__) . "/languages/" . strtolower($this->localizationDomain) . "-".strtolower($locale).".mo";
            load_textdomain($this->localizationDomain, $mo);

            //"Constants" setup
            $this->thispluginurl = WP_PLUGIN_URL . '/' . dirname(plugin_basename(__FILE__)).'/';
            $this->thispluginpath = WP_PLUGIN_DIR . '/' . dirname(plugin_basename(__FILE__)).'/';
			$this->hits_pbr_db = $pbr_db;
			
			global $wp_version;
            $this->wp_version = substr(str_replace('.', '', $wp_version), 0, 2);
            
            //Initialize the options
            //This is REQUIRED to initialize the options when the plugin is loaded!
            $this->getOptions();
            $this->actions_filters_hooks();
        }
        
        /**
        * @var string $localizationDomain Domain used for localization
        */
        var $localizationDomain = "hits-pages-by-role";
 		
		/**
		 * Centralized place for adding all actions and filters for the plugin into wordpress
		*/
		function actions_filters_hooks()
		{
			add_action("admin_menu", array(&$this,"admin_menu_link"));
			add_action('admin_head', array(&$this, 'admin_head'));
			
			add_action('after_plugin_row', array(&$this,'plugin_check_version'), 10, 2);
			add_action('widgets_init', array(&$this, 'widget_register'));
			
			//ajax handlers
			add_action('wp_ajax_hits_pbr_add_record',array(&$this, 'add_record'));
			add_action('wp_ajax_hits_pbr_remove_record',array(&$this, 'remove_record'));
			add_action('wp_ajax_hits_pbr_moveUp_record',array(&$this, 'moveUp_record'));
			add_action('wp_ajax_hits_pbr_moveDown_record',array(&$this, 'moveDown_record'));			
		}
		
		function add_record()
		{
			$page_ID = $_POST['page_id'];
			$page_MinAccess = $_POST['minAccess'];
			$page_OverrideText = $_POST['overrideText'];
			
			$this->hits_pbr_db->add_page($page_ID,$page_MinAccess,$page_OverrideText);
			
			echo $this->getHtmlForRecord($page_ID,$page_MinAccess,$page_OverrideText);
			die();
		}
		
		function remove_record()
		{
			$page_ID=$_POST['page_id'];
			$this->hits_pbr_db->remove_page($page_ID);
			echo "$page_ID";
			die();
		}
		
		function moveUp_record()
		{
			$page_ID=$_POST['page_id'];
			$target_page_id = $this->hits_pbr_db->movePageUp($page_ID);
			echo "$page_ID,$target_page_id";
			die();			
		}
		
		function moveDown_record()
		{
			$page_ID=$_POST['page_id'];
			$target_page_id = $this->hits_pbr_db->movePageDown($page_ID);
			echo "$page_ID,$target_page_id";
			die();			
		}
		
		function admin_head()
		{
            echo("\n".'<link rel="stylesheet" href="'.$this->thispluginurl.'css/admin.css" type="text/css" media="screen" />');	
			echo("\n<script type='text/javascript' src='".$this->thispluginurl."js/hits-pbr.js'> </script>");
		}
		
        function plugin_check_version($file, $plugin_data) 
		{
            static $this_plugin;
            if (!$this_plugin) $this_plugin = plugin_basename(__FILE__);

            if ($file == $this_plugin){
                $current = $this->wp_version < 28 ? get_option('update_plugins') : get_transient('update_plugins');
                if (!isset($current->response[$file])) return false;

                $columns = $this->wp_version < 28 ? 5 : 3;
                $url = "http://plugins.svn.wordpress.org/hits-pages-by-role/trunk/updateText.txt";
                $update = wp_remote_fopen($url);
                if ($update != "") {
                    echo '<td colspan="'.$columns.'" class="hits-plugin-update"><div class="hits-plugin-update-message">';
                    echo $update;
                    echo '</div></td>';
                }
            }
        }
		
		/**
		 * Wrapping the localization methods to make them shorter and easier to read
		 */
		function getStr($string)
		{
			return __($string, $this->localizationDomain);
		}
		
		function echoStr($string)
		{
			_e($string, $this->localizationDomain);
		}
        
        /**
        * Retrieves the plugin options from the database.
        * @return array
        */
        function getOptions() 
		{
			$missingOptions=false;
			$oldVersion='Unchecked';
            if (!$theOptions = get_option($this->optionsName)) 
			{
				$missingOptions=true;
                $theOptions = array('hits_plugin_debug'=>"false",
									'hits_pbr_title'=>"Pages",
									'hits_pbr_version'=>$this->version
									);
                update_option($this->optionsName, $theOptions);
				$oldVersion='Missing';
				$this->options = $theOptions;
            }
			else
			{
				$this->options = $theOptions;
				$oldVersion = $this->options['hits_pbr_version'];
			}
            
            
			//check for missing fields on an upgrade
			if($missingOptions==true || strcmp($oldVersion,$this->version)!=0)
			{
				echo "\n<!--  Missing Options, $oldVersion, $this->version -->\n";
				$missingOptions=true;
				//an upgrade, run upgrade specific tasks.
				if(substr($oldVersion,0,3)=='1.0' || substr($oldVersion,0,5)=='1.1.0' || $oldVersion=='Missing')
				{
					//need to create database
					echo "\n<!-- Creating Database -->";
					$this->hits_pbr_db->install();
					
					//need to add sort order to existing pages	
					if(count($this->options['pages'])>0)
					{
						$pages = $this->options['pages'];
				
						foreach($pages as $page)
						{
							$pageId = $page['pageID'];
							$pageAccess = $this->translatePageAccessNameToId($page['access']);
							$overrideText = $page['linkOverride'];
							echo "<!--Adding page  $pageId,$pageAccess,$overrideText-->";
							$this->hits_pbr_db->add_page($pageId,$pageAccess,$overrideText);
						}
						unset($this->options['pages']);
					}
					else
					{
						$this->hits_pbr_db->populateDefaults();	
					}
				}
				if($oldVersion=='1.1.7')
				{
					$this->hits_pbr_db->verifyDbState();
				}
				
				//done the upgrade
				$this->options['hits_pbr_version']=$this->version;
			}
			
			//if missing options found, update them.
			if($missingOptions==true)
				$this->saveAdminOptions();
        }
		
		function translatePageAccessNameToId($pageAccess)
		{
			$compare = strtolower($pageAccess);
			if($compare=='administrator')
				return 5;
			else if($compare=='editor')
				return 4;							
			else if($compare=='author')
				return 3;							
			else if($compare=='contributor')
				return 2;							
			else if($compare=='subscriber')
				return 1;						
			else if($compare=='public')
				return 0;							
			else if($compare=='publiconly')
				return -1;	
		}
		
		function translatePageAccessIdToName($pageAccess)
		{
			if($pageAccess==5)
				return 'Administrator';
			if($pageAccess==4)
				return 'Editor';
			if($pageAccess==3)
			   	return 'Author';
			if($pageAccess==2)
			   	return 'Contributor';
			if($pageAccess==1)
				return 'Subscriber';
			if($pageAccess==0)
				return 'Public';
			if($pageAccess==-1)
				return 'PublicOnly';
		}
		
        /**
        * @desc Saves the admin options to the database.
        */
        function saveAdminOptions()
		{
			return update_option($this->optionsName, $this->options);
        }
        
        /**
        * @desc Adds the options subpanel
        */
        function admin_menu_link()
		{
            add_options_page('HITS- Pages By Role', 'HITS- Pages By Role', 10, basename(__FILE__), array(&$this,'admin_options_page'));
            add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array(&$this, 'filter_plugin_actions'), 10, 2 );
        }
        
        /**
        * @desc Adds the Settings link to the plugin activate/deactivate page
        */
        function filter_plugin_actions($links, $file) 
		{
           $settings_link = '<a href="options-general.php?page=' . basename(__FILE__) . '">' . $this->getStr('Settings') . '</a>';
           array_unshift( $links, $settings_link ); // before other links

           return $links;
        }
		
		function widget_register()
		{
			$ops = array('classname' => 'hits_pbr', 'description' => $this->getStr("Log in/out, admin, feed and WordPress links, configurable") );
			wp_register_sidebar_widget('hits_pbr', 'HITS- Pages By Role', array(&$this, 'widget'), $widget_ops);
			wp_register_widget_control('hits_pbr', 'HITS- Pages By Role', array(&$this, 'widget_config') );
		}
		
		function widget($args) 
		{
			echo "\n\n<!-- HITS- Pages By Role Widget - Start -->";
			$pages = $this->hits_pbr_db->get_pages();
	
			if(count($pages)>0)
			{
				$title = $this->options['hits_pbr_title'];
				echo '<li id="hits_pbr" class="widget widget_pages"><h2 class="widgettitle">'.$title.'</h2><ul>';
				$is_loggedIn=is_user_logged_in();
				$role=0;
				if($is_loggedIn)
				{
					$user = wp_get_current_user();
					if ( !empty( $user->roles ) && is_array( $user->roles ) ) 
					{
						$role=array_shift($user->roles);
						if($this->options['hits_plugin_debug']=='true')
							echo "\n<!-- WP Role=$role -->";
						$role=$this->translatePageAccessNameToId($role);
						if($this->options['hits_plugin_debug']=='true')
							echo "\n<!-- Translated=$role -->";
					}					
				}
					
				if($this->options['hits_plugin_debug']=='true')
					echo "\n<!-- Detected Role: $role -->";
					
				foreach($pages as $page)
				{
					$pageId = $page->PageId;
					$pageAccess = $page->AccessRole;
					$overrideText = $page->OverrideText;
					
					if($pageId==-1)
					{
						ob_start();
						wp_loginout();
						$output = ob_get_contents();
						ob_end_clean();
						$output = '<li class="page_item page-item-'.$pageId.'">'.$output.'</li>';
					}
					else if($pageId==-2)
					{
						$output = wp_register('<li class="page_item page-item--2">','</li>',false);
					}
					else
					{
						$page = get_page($pageId);
						if(strlen($overrideText)>0)
							$pageName=$overrideText;
						else
							$pageName=$page->post_title;
							
						$linkURL = get_permalink($pageId);
						$output ='<li class="page_item page-item-'.$pageId.'"><a href="'.$linkURL.'">'.$pageName.'</a></li>';
					}
					if($this->has_access_level_to_display_link($pageAccess,$role))
						echo "\n".$output;
				}
				echo '</ul></li>';
				echo "\n<!-- HITS- Pages By Role Widget - End -->";
			}
		}
		
		function has_access_level_to_display_link($needed, $has)
		{
			if($this->options['hits_plugin_debug']=='true')
				echo "\n<!-- User requires $needed, and has $has -->";
			if($needed<=$has && $needed>=0)
			{
				if($this->options['hits_plugin_debug']=='true')
					echo "\n<!-- User has required access -->";
				return true;
			}
			
			if($needed==-1 && $has==-1)
			{
				if($this->options['hits_plugin_debug']=='true')
					echo "\n<!-- Public Only, has required access -->";
				return true;
			}
			
			if($this->options['hits_plugin_debug']=='true')
				echo "\n<!-- Does not have required access -->";
			return false;
				
		}
		
		function widget_config()
		{
			echo '<p><a href="options-general.php?page=hits-pbr.php">'.$this->getStr('Configure Widget Here').'</a></p>';
		}
		
		function scrape_admin_options()
		{
			$this->options['hits_plugin_debug']= $_POST['hits_plugin_debug'];
			$this->options['hits_pbr_title']= $_POST['hits_pbr_title'];
		}
		
		function getHtmlForRecord($pageId,$minAccess,$overrideText)
		{
			echo "<!-- getting HTML, $pageId, $minAccess, $overrideText -->";
			$pageName = '';
			if($pageId==-1)
				$pageName="Login/Logout";
			else if($pageId==-2)
				$pageName="Register/Admin";
			else
			{
				$page = get_page($pageId);
				$pageName=$page->post_title;
			}
			if(strlen($overrideText)>0)
				$pageName="(".$overrideText.") " . $pageName;
				
			$html = '<div id="record-'.$pageId.'" class="pbrRecord">';
			$moveUpImageURL = $this->thispluginurl . 'images/Up.gif';
			$html.= '<div class="moveUpLink"><a class="pbrMoveUp" href="#"><img src="'.$moveUpImageURL.'" /></a></div>';
			$moveDownImageURL = $this->thispluginurl . 'images/Down.gif';
			$html.= '<div class="moveDownLink"><a class="pbrMoveDown" href="#"><img src="'.$moveDownImageURL.'" /></a></div>';
			$deleteImageURL = $this->thispluginurl . 'images/Remove.gif';
			$html.= '<div class="deleteLink"><a class="pbrDelete" href="#"><img src="'.$deleteImageURL.'" /></a></div>';
			$html.= '<div class="pageInfo"><span class="pageName">'.$pageName.'</span> ';
			$html.= 'accessible by: <span class="accessibleBy">'.$this->translatePageAccessIdToName($minAccess).'</span> ';
			$html.= '</div>';
			$html.= '</div>';
			
			return $html;
		}
        
        /**
        * Adds settings/options page
        */
        function admin_options_page() {
            if($_POST['hits_pbr_save'])
			{
                if (! wp_verify_nonce($_POST['_wpnonce'], 'hits_pbr-update-options') ) 
					die($this->echoStr('Whoops! There was a problem with the data you posted. Please go back and try again.')); 
				$this->scrape_admin_options();
                $this->saveAdminOptions();
                echo '<div class="updated"><p>'.$this->getStr('Success! Your changes were sucessfully saved!') .'</p></div>';
            }?>
            <div class="wrap">
                <h2>HITS- Pages By Role</h2>
                <form method="post" id="hits_pbr_options">
                <?php wp_nonce_field('hits_pbr-update-options');?>
                <p><?php $this->echoStr('This plugin brought to you for free by');?>
                <a href="http://www.itegritysolutions.ca/community/wordpress/pages-by-role/" target="_blank">ITegrity Solutions</a>.</p>
                
                <h3><?php $this->echoStr('Plugin Settings'); ?></h3>
                <div id="pluginSettings">
                	<div class="itemTitle"><?php $this->echoStr('Title:'); ?></div>
                    <div class="itemField"><input type="text" name="hits_pbr_title" value="<?php echo $this->options['hits_pbr_title']; ?>" /></div>
                    <div class="itemTitle"><?php $this->echoStr('Plugin Debug Mode:'); ?></div>
                    <div class="itemField"><select name="hits_plugin_debug" id="hits_plugin_debug" style="width:100px;">
                            <option value="false" <?php if (strcmp($this->options['hits_plugin_debug'],'false')==0) { echo ' selected="selected"';} ?>>
                                <?php $this->echoStr('False');?></option>
                            <option value="true" <?php if (strcmp($this->options['hits_plugin_debug'],'true')==0) { echo ' selected="selected"';} ?>>
                                <?php $this->echoStr('True');?></option>
                        </select><br /><?php $this->echoStr('Note: Please set this to true if you are having difficulties with this plugin.');?></div>
                    <div class="saveButton"><input type="submit" name="hits_pbr_save" value="Save" /></div>
                </div>
					<?php
					echo '<!-- about to get pages -->';
					$pages = $this->hits_pbr_db->get_pages();
					if(count($pages)>0)
					{
						echo '<div id="pageList"><h3>'. $this->getStr('Existing Pages').'</h3>';
						
						foreach($pages as $page)
						{
							echo $this->getHtmlForRecord($page->PageId,$page->AccessRole,$page->OverrideText);
						}
						echo '</div>';
					}					
					?>
                    
                    <div id="newRecordRow">
                		<h3><?php $this->echoStr('Add New Page'); ?></h3>
                        <div id="itemToAdd">
                        	<div class="itemTitle"><?php $this->echoStr('Page:'); ?></div>
                            <div class="itemField"><select id="hits_pbr_page_ID">
                                            <option value="-1">Login/Logout</option>
                                            <option value="-2">Register/Admin</option>
                                            <?php 
                                              $pages = get_pages(); 
                                              foreach ($pages as $pagg) {
                                                $option = '<option value="'.$pagg->ID.'">';
                                                $option .= $pagg->post_title;
                                                $option .= '</option>';
                                                echo $option;
                                              }
                                             ?>
                                        </select></div>
                             <div class="itemTitle"><?php $this->echoStr('Min Access:'); ?></div>
                             <div class="itemField"><select id="hits_pbr_page_MinAccess">
                                        	<option value="5"><?php $this->echoStr('Administrator'); ?></option>
                                        	<option value="4"><?php $this->echoStr('Editor'); ?></option>
                                        	<option value="3"><?php $this->echoStr('Author'); ?></option>
                                        	<option value="2"><?php $this->echoStr('Contributor'); ?></option>
                                        	<option value="1"><?php $this->echoStr('Subscriber'); ?></option>                                            
                                        	<option value="0"><?php $this->echoStr('Public'); ?></option>
                                            <option value="-1"><?php $this->echoStr('PublicOnly'); ?></option>
                                        </select></div>
                             <div class="itemTitle"><?php $this->echoStr('Override Text:'); ?></div>
                             <div class="itemField"><input type="text" id="hits_pbr_page_OverrideText" value="<?php echo $this->options['hits_pbr_page1-OverrideText'];  ?>"/></div>
                             <div class="addButton"><input type="button" id="hits_pbr_add_item_button" name="hits_pbr_add_item_button" value="Add" /></div>
                         </div>
                    </div>
                <div id="Feedback"><?php $this->echoStr('Feedback and requests are always welcome.');?>
                <a href="http://www.homeitsolutions.ca/websites/wordpress-plugins/pages-by-role/"><?php $this->echoStr('Visit the plugin website');?></a>
				   <?php $this->echoStr('to leave any feedback, translations, comments or donations. All donations will go towards micro loans through');?>
                   <a href="http://www.kiva.org">Kiva</a>.</div>
                </form>
                </div>
            <?php
        }
  } //End Class
} //End if class exists statement

//instantiate the class
	
if(class_exists('hits_pbr_db'))
{
	$hits_pbr_db = new hits_pbr_db();
}
if (class_exists('hits_pbr')) 
{
	global $hits_pbr_var;
    $hits_pbr_var = new hits_pbr($hits_pbr_db);
}
?>
