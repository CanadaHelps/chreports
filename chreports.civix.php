<?php

// AUTO-GENERATED FILE -- Civix may overwrite any changes made to this file

/**
 * The ExtensionUtil class provides small stubs for accessing resources of this
 * extension.
 */
class CRM_Chreports_ExtensionUtil {
  const SHORT_NAME = "chreports";
  const LONG_NAME = "biz.jmaconsulting.chreports";
  const CLASS_PREFIX = "CRM_Chreports";

  /**
   * Translate a string using the extension's domain.
   *
   * If the extension doesn't have a specific translation
   * for the string, fallback to the default translations.
   *
   * @param string $text
   *   Canonical message text (generally en_US).
   * @param array $params
   * @return string
   *   Translated text.
   * @see ts
   */
  public static function ts($text, $params = array()) {
    if (!array_key_exists('domain', $params)) {
      $params['domain'] = array(self::LONG_NAME, NULL);
    }
    return ts($text, $params);
  }

  /**
   * Get the URL of a resource file (in this extension).
   *
   * @param string|NULL $file
   *   Ex: NULL.
   *   Ex: 'css/foo.css'.
   * @return string
   *   Ex: 'http://example.org/sites/default/ext/org.example.foo'.
   *   Ex: 'http://example.org/sites/default/ext/org.example.foo/css/foo.css'.
   */
  public static function url($file = NULL) {
    if ($file === NULL) {
      return rtrim(CRM_Core_Resources::singleton()->getUrl(self::LONG_NAME), '/');
    }
    return CRM_Core_Resources::singleton()->getUrl(self::LONG_NAME, $file);
  }

  /**
   * Get the path of a resource file (in this extension).
   *
   * @param string|NULL $file
   *   Ex: NULL.
   *   Ex: 'css/foo.css'.
   * @return string
   *   Ex: '/var/www/example.org/sites/default/ext/org.example.foo'.
   *   Ex: '/var/www/example.org/sites/default/ext/org.example.foo/css/foo.css'.
   */
  public static function path($file = NULL) {
    // return CRM_Core_Resources::singleton()->getPath(self::LONG_NAME, $file);
    return __DIR__ . ($file === NULL ? '' : (DIRECTORY_SEPARATOR . $file));
  }

  /**
   * Get the name of a class within this extension.
   *
   * @param string $suffix
   *   Ex: 'Page_HelloWorld' or 'Page\\HelloWorld'.
   * @return string
   *   Ex: 'CRM_Foo_Page_HelloWorld'.
   */
  public static function findClass($suffix) {
    return self::CLASS_PREFIX . '_' . str_replace('\\', '_', $suffix);
  }

  /**
   * Get the column name of a CustomField on basis of name.
   *
   * @param string $name
   *
   * @return string
   */
  public static function getColumnNameByName($name) {
     $values = civicrm_api3('CustomField', 'get', [
       'name' => $name,
       'sequential' => 1,
       'return' => ['column_name'],
     ])['values'];
     if (!empty($values[0])) {
       return CRM_Utils_Array::value('column_name', $values[0]);
     }

     return NULL;
  }

  /**
   * Get the column name of a CustomField on basis of name.
   *
   * @param string $name
   *
   * @return string
   */
  public static function getTableNameByName($name) {
     $values = civicrm_api3('CustomGroup', 'get', [
       'name' => $name,
       'sequential' => 1,
       'return' => ['table_name'],
     ])['values'];
     if (!empty($values[0])) {
       return CRM_Utils_Array::value('table_name', $values[0]);
     }

     return NULL;
  }


  public static function getOptionGroupNameByColumnName($columnName) {
    return CRM_Core_DAO::singlevalueQuery("
      SELECT g.name
      FROM civicrm_option_group g
        INNER JOIN civicrm_custom_field cf ON cf.option_group_id = g.id
      WHERE cf.column_name = '$columnName'
    ");
  }
  public static function getBaseTemplate ($reportInstance) {
    $title = $reportInstance['title'];
    $description = $reportInstance['description'];
    $report_id = $reportInstance['report_id'];
    $formValues = $reportInstance['form_values'];

    // Check whether report ID is base or has got sub templates as well
    $migratedTemplate = self::getMigratedTemplate($report_id);
    if(isset($migratedTemplate['sub_templates'])) {
      // Step 1: Get it by Title
      $match = self::matchKeywordForTemplate($reportInstance['title']);
      if(!$match['title']) {
        // Step 2: Get it by description
        $match = self::matchKeywordForTemplate($reportInstance['description']);
        if(!$match) {    
          // Step 3: Get it by formvalues
          $match = self::matchbyFormValue($formValues, $migratedTemplate);
        }
      }
      if($match) {
        $base_report_name = $migratedTemplate['sub_templates'][$match]['name'];
      } else {
        // Step 4: Assign base/common template for the corresponding report_id
        // catch all template
        $base_report_name = $migratedTemplate['name'];
      }
      
    } else {
      $base_report_name = $migratedTemplate['name'];
    }
    if($base_report_name) {
      $base_report = civicrm_api3('ReportInstance', 'get', [
        'name' => $base_report_name,
        'created_id' => ['IS NULL' => 1]
      ]);
      $base_report['entity'] = $migratedTemplate['entity'];
      return $base_report;
    }
  }

  private function getMigratedTemplate($reportId) {
    switch (strtolower($reportId)) {
      case 'biz.jmaconsulting.chreports/extendeddetail' :
      case 'contribution/contributions' :
        $template = [
          'report_id' => 'chreports/contrib_detailed',
          'entity' => 'contribution',
          'name' => 'contrib_detailed',
          'sub_templates' => [
            'fund' => [
              'name' => 'contrib_detailed_fund',
            ],
            'source' => [
              'name' => 'contrib_detailed_source',
            ],
            'campaign' => [
              'name' => 'contrib_detailed_campaign',
            ],
            'campaign_group' => [
              'name' => 'contrib_detailed_campaign_group',
            ],
            'ch_fund' => [
              'name' => 'contrib_detailed_chfund'
            ],
            'glaccount' => [
              'name' => 'contrib_detailed_glaccount'
            ],
            'inhonour' => [
              'name' => 'contrib_detailed_inhonour'
            ],
            'inmemory' => [
              'name' => 'contrib_detailed_inmemory'
            ],
          ],
        ];
        break;
      case 'chreports/extendsummary' : 
        $template = [
          'report_id' => 'chreports/contrib_summary',
          'entity' => 'contribution',
          'name' => 'contrib_summary',
          'sub_templates' => [
            'fund' => [
              'name' => 'contrib_summary_fund',
            ],
            'source' => [
              'name' => 'contrib_summary_source',
            ],
            'campaign' => [
              'name' => 'contrib_summary_campaign',
            ],
            'campaign_group' => [
              'name' => 'contrib_summary_campaign_group',
            ],
            'glaccount' => [
              'name' => 'contrib_summary_glaccount'
            ],
            'ch_fund' => [
              'name' => 'contrib_detailed_chfund'
            ],
          ],
        ];
        break;
      case 'contribute/lybunt' :
      case 'chreports/extendlybunt' :
      case 'contribute/revisedlybunt' : 
        $template = [
          'name' => 'contrib_lybunt',
          'report_id' => 'chreports/lybunt',
          'entity' => 'contribution',
        ];
        break;
      case 'contribute/sybunt' :
        $template =  [
        'name' => 'contrib_sybunt',
        'report_id' => 'chreports/sybunt',
        'entity' => 'contribution',
      ];
      case 'grant/detail' :
        $template = [
        'name' => 'opportunity_detailed',
        'report_id' => 'chreports/opportunity_detailed',
        'entity' => 'contribution',
        ];
      break;
      case 'contribute/summary' : 
        $template = [
          'report_id' => 'chreports/contrib_period_detailed',
          'entity' => 'contribution',
          'name' => 'contrib_summary',
          'sub_templates' => [
            'monthly' => [
              'name' => 'contrib_monthly_fiscal_year',
            ],
            'quaterly' => [
              'name' => 'contrib_quarterly_past_year',
            ],
          ],
        ];
      break;
    }
    return $template;
  }


  public function matchKeywordForTemplate($inputString, $type = 'contribution') {
    // Define the keywords to search for
    $keywords = [
      'contribution' => [
        'campaign group', 
        'fund', 
        'campaign',
        'source',
        'in honor',
        'in memory',
        'CH Fund',
        'GL Account'
      ],
      'pivot' => [
        'monthly',
        'quaterly',
        'yearly'
      ]
    ];
    
    // Loop through each keyword depending on the type of report and check if it exists in the input string
    foreach ($keywords[$type] as $keyword) {
      if (strpos(strtolower($inputString), $keyword) !== false) {
        return $keyword;
      }
    }
    return false;
  }

  public function matchbyFormValue($formValues, $reportId, $type = 'contribution') {
    if($type == 'contribution') {
      //Loop through fields 
      $fieldsPreferenceOrder = [
        "financial_type_id",
        "contribution_page_id",
        "campaign",
        "source",
        "ch_fund" //Translate the custom value by coulmn name
      ];
      if($reportId['name'] == 'contrib_detailed' || $reportId['name'] == 'contrib_summary') {
        foreach($formValues['fields'] as $kField => $vField) {
          if(in_array($kField, $fieldsPreferenceOrder)) {
            return self::wordReplace($kField);
          }
        }
      }
    }
  }

  private function wordReplace ($inputString) {
    $wordReplace = [
      'contribution_page_id' => 'campaign',
      'financial_type_id' => 'fund',
      'campaign' => 'campaign_group'
    ];
    if (array_key_exists($inputString, $wordReplace))
      return $wordReplace[$inputString];
    return $inputString;
  }
}

use CRM_Chreports_ExtensionUtil as E;

/**
 * (Delegated) Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function _chreports_civix_civicrm_config(&$config = NULL) {
  static $configured = FALSE;
  if ($configured) {
    return;
  }
  $configured = TRUE;

  $template =& CRM_Core_Smarty::singleton();

  $extRoot = dirname(__FILE__) . DIRECTORY_SEPARATOR;
  $extDir = $extRoot . 'templates';

  if (is_array($template->template_dir)) {
    array_unshift($template->template_dir, $extDir);
  }
  else {
    $template->template_dir = array($extDir, $template->template_dir);
  }

  $include_path = $extRoot . PATH_SEPARATOR . get_include_path();
  set_include_path($include_path);
}

/**
 * (Delegated) Implements hook_civicrm_xmlMenu().
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function _chreports_civix_civicrm_xmlMenu(&$files) {
  foreach (_chreports_civix_glob(__DIR__ . '/xml/Menu/*.xml') as $file) {
    $files[] = $file;
  }
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function _chreports_civix_civicrm_install() {
  _chreports_civix_civicrm_config();
  if ($upgrader = _chreports_civix_upgrader()) {
    $upgrader->onInstall();
  }
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function _chreports_civix_civicrm_postInstall() {
  _chreports_civix_civicrm_config();
  if ($upgrader = _chreports_civix_upgrader()) {
    if (is_callable(array($upgrader, 'onPostInstall'))) {
      $upgrader->onPostInstall();
    }
  }
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function _chreports_civix_civicrm_uninstall() {
  _chreports_civix_civicrm_config();
  if ($upgrader = _chreports_civix_upgrader()) {
    $upgrader->onUninstall();
  }
}

/**
 * (Delegated) Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function _chreports_civix_civicrm_enable() {
  _chreports_civix_civicrm_config();
  if ($upgrader = _chreports_civix_upgrader()) {
    if (is_callable(array($upgrader, 'onEnable'))) {
      $upgrader->onEnable();
    }
  }
}

/**
 * (Delegated) Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 * @return mixed
 */
function _chreports_civix_civicrm_disable() {
  _chreports_civix_civicrm_config();
  if ($upgrader = _chreports_civix_upgrader()) {
    if (is_callable(array($upgrader, 'onDisable'))) {
      $upgrader->onDisable();
    }
  }
}

/**
 * (Delegated) Implements hook_civicrm_upgrade().
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function _chreports_civix_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  if ($upgrader = _chreports_civix_upgrader()) {
    return $upgrader->onUpgrade($op, $queue);
  }
}

/**
 * @return CRM_Chreports_Upgrader
 */
function _chreports_civix_upgrader() {
  if (!file_exists(__DIR__ . '/CRM/Chreports/Upgrader.php')) {
    return NULL;
  }
  else {
    return CRM_Chreports_Upgrader_Base::instance();
  }
}

/**
 * Search directory tree for files which match a glob pattern
 *
 * Note: Dot-directories (like "..", ".git", or ".svn") will be ignored.
 * Note: In Civi 4.3+, delegate to CRM_Utils_File::findFiles()
 *
 * @param $dir string, base dir
 * @param $pattern string, glob pattern, eg "*.txt"
 * @return array(string)
 */
function _chreports_civix_find_files($dir, $pattern) {
  if (is_callable(array('CRM_Utils_File', 'findFiles'))) {
    return CRM_Utils_File::findFiles($dir, $pattern);
  }

  $todos = array($dir);
  $result = array();
  while (!empty($todos)) {
    $subdir = array_shift($todos);
    foreach (_chreports_civix_glob("$subdir/$pattern") as $match) {
      if (!is_dir($match)) {
        $result[] = $match;
      }
    }
    if ($dh = opendir($subdir)) {
      while (FALSE !== ($entry = readdir($dh))) {
        $path = $subdir . DIRECTORY_SEPARATOR . $entry;
        if ($entry[0] == '.') {
        }
        elseif (is_dir($path)) {
          $todos[] = $path;
        }
      }
      closedir($dh);
    }
  }
  return $result;
}
/**
 * (Delegated) Implements hook_civicrm_managed().
 *
 * Find any *.mgd.php files, merge their content, and return.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function _chreports_civix_civicrm_managed(&$entities) {
  $mgdFiles = _chreports_civix_find_files(__DIR__, '*.mgd.php');
  foreach ($mgdFiles as $file) {
    $es = include $file;
    foreach ($es as $e) {
      if (empty($e['module'])) {
        $e['module'] = E::LONG_NAME;
      }
      $entities[] = $e;
      if (empty($e['params']['version'])) {
        $e['params']['version'] = '3';
      }
    }
  }
}

/**
 * (Delegated) Implements hook_civicrm_caseTypes().
 *
 * Find any and return any files matching "xml/case/*.xml"
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function _chreports_civix_civicrm_caseTypes(&$caseTypes) {
  if (!is_dir(__DIR__ . '/xml/case')) {
    return;
  }

  foreach (_chreports_civix_glob(__DIR__ . '/xml/case/*.xml') as $file) {
    $name = preg_replace('/\.xml$/', '', basename($file));
    if ($name != CRM_Case_XMLProcessor::mungeCaseType($name)) {
      $errorMessage = sprintf("Case-type file name is malformed (%s vs %s)", $name, CRM_Case_XMLProcessor::mungeCaseType($name));
      CRM_Core_Error::fatal($errorMessage);
      // throw new CRM_Core_Exception($errorMessage);
    }
    $caseTypes[$name] = array(
      'module' => E::LONG_NAME,
      'name' => $name,
      'file' => $file,
    );
  }
}

/**
 * (Delegated) Implements hook_civicrm_angularModules().
 *
 * Find any and return any files matching "ang/*.ang.php"
 *
 * Note: This hook only runs in CiviCRM 4.5+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
 */
function _chreports_civix_civicrm_angularModules(&$angularModules) {
  if (!is_dir(__DIR__ . '/ang')) {
    return;
  }

  $files = _chreports_civix_glob(__DIR__ . '/ang/*.ang.php');
  foreach ($files as $file) {
    $name = preg_replace(':\.ang\.php$:', '', basename($file));
    $module = include $file;
    if (empty($module['ext'])) {
      $module['ext'] = E::LONG_NAME;
    }
    $angularModules[$name] = $module;
  }
}

/**
 * Glob wrapper which is guaranteed to return an array.
 *
 * The documentation for glob() says, "On some systems it is impossible to
 * distinguish between empty match and an error." Anecdotally, the return
 * result for an empty match is sometimes array() and sometimes FALSE.
 * This wrapper provides consistency.
 *
 * @link http://php.net/glob
 * @param string $pattern
 * @return array, possibly empty
 */
function _chreports_civix_glob($pattern) {
  $result = glob($pattern);
  return is_array($result) ? $result : array();
}

/**
 * Inserts a navigation menu item at a given place in the hierarchy.
 *
 * @param array $menu - menu hierarchy
 * @param string $path - path to parent of this item, e.g. 'my_extension/submenu'
 *    'Mailing', or 'Administer/System Settings'
 * @param array $item - the item to insert (parent/child attributes will be
 *    filled for you)
 */
function _chreports_civix_insert_navigation_menu(&$menu, $path, $item) {
  // If we are done going down the path, insert menu
  if (empty($path)) {
    $menu[] = array(
      'attributes' => array_merge(array(
        'label'      => CRM_Utils_Array::value('name', $item),
        'active'     => 1,
      ), $item),
    );
    return TRUE;
  }
  else {
    // Find an recurse into the next level down
    $found = FALSE;
    $path = explode('/', $path);
    $first = array_shift($path);
    foreach ($menu as $key => &$entry) {
      if ($entry['attributes']['name'] == $first) {
        if (!isset($entry['child'])) {
          $entry['child'] = array();
        }
        $found = _chreports_civix_insert_navigation_menu($entry['child'], implode('/', $path), $item, $key);
      }
    }
    return $found;
  }
}

/**
 * (Delegated) Implements hook_civicrm_navigationMenu().
 */
function _chreports_civix_navigationMenu(&$nodes) {
  if (!is_callable(array('CRM_Core_BAO_Navigation', 'fixNavigationMenu'))) {
    _chreports_civix_fixNavigationMenu($nodes);
  }
}

/**
 * Given a navigation menu, generate navIDs for any items which are
 * missing them.
 */
function _chreports_civix_fixNavigationMenu(&$nodes) {
  $maxNavID = 1;
  array_walk_recursive($nodes, function($item, $key) use (&$maxNavID) {
    if ($key === 'navID') {
      $maxNavID = max($maxNavID, $item);
    }
  });
  _chreports_civix_fixNavigationMenuItems($nodes, $maxNavID, NULL);
}

function _chreports_civix_fixNavigationMenuItems(&$nodes, &$maxNavID, $parentID) {
  $origKeys = array_keys($nodes);
  foreach ($origKeys as $origKey) {
    if (!isset($nodes[$origKey]['attributes']['parentID']) && $parentID !== NULL) {
      $nodes[$origKey]['attributes']['parentID'] = $parentID;
    }
    // If no navID, then assign navID and fix key.
    if (!isset($nodes[$origKey]['attributes']['navID'])) {
      $newKey = ++$maxNavID;
      $nodes[$origKey]['attributes']['navID'] = $newKey;
      $nodes[$newKey] = $nodes[$origKey];
      unset($nodes[$origKey]);
      $origKey = $newKey;
    }
    if (isset($nodes[$origKey]['child']) && is_array($nodes[$origKey]['child'])) {
      _chreports_civix_fixNavigationMenuItems($nodes[$origKey]['child'], $maxNavID, $nodes[$origKey]['attributes']['navID']);
    }
  }
}

/**
 * (Delegated) Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function _chreports_civix_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  static $configured = FALSE;
  if ($configured) {
    return;
  }
  $configured = TRUE;

  $settingsDir = __DIR__ . DIRECTORY_SEPARATOR . 'settings';
  if (is_dir($settingsDir) && !in_array($settingsDir, $metaDataFolders)) {
    $metaDataFolders[] = $settingsDir;
  }
}

/**
 * (Delegated) Implements hook_civicrm_entityTypes().
 *
 * Find any *.entityType.php files, merge their content, and return.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_entityTypes
 */

function _chreports_civix_civicrm_entityTypes(&$entityTypes) {
  $entityTypes = array_merge($entityTypes, array (
  ));
}
