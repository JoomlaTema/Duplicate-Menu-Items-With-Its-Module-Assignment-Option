<?php
/***
 * @package     jt_copymoduleassignments Joomla.Plugin
 * @copyright   Copyright (C) http://www.joomlatema.net, Inc. All rights reserved.
 * @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @author     	JoomlaTema.Net
 * @link 		http://www.joomlatema.net
 ***/
defined('_JEXEC') or die;

/**
 * Copy Module Assignments Plugin
 */
class PlgContentJt_Copymoduleassignments extends JPlugin
{
    protected $db;

    public function __construct($app, $plugin)
    {
        parent::__construct($app, $plugin);
        $this->db = JFactory::getDbo();
    }

  public function onContentAfterSave($context, &$table, $isNew)
{
    // Debugging output
    JFactory::getApplication()->enqueueMessage('Context: ' . $context);
    JFactory::getApplication()->enqueueMessage('Is New: ' . ($isNew ? 'Yes' : 'No'));

    // Return if invalid context
    if ($context != 'com_menus.item') {
        JFactory::getApplication()->enqueueMessage('Invalid context, exiting.', 'error');
        return true;
    }

    // Only proceed if the item is new
    if ($isNew) {
        // Get the original menu item ID from the submitted data (this assumes the ID is part of the submitted form data)
        $originalMenuId = JFactory::getApplication()->input->getInt('id', 0);

        // Debugging output
        JFactory::getApplication()->enqueueMessage('New Menu Item ID: ' . $table->id);
        JFactory::getApplication()->enqueueMessage('Original Menu ID: ' . $originalMenuId);

        // Proceed with fetching assigned modules for the original menu ID
        $query1 = $this->db->getQuery(true)
            ->select($this->db->quoteName('moduleid'))
            ->from($this->db->quoteName('#__modules_menu'))
            ->where($this->db->quoteName('menuid') . ' = ' . (int) $originalMenuId);
        $this->db->setQuery($query1);

        try {
            $modules = (array) $this->db->loadColumn();
            JFactory::getApplication()->enqueueMessage('Modules Found: ' . count($modules));
        } catch (Exception $e) {
            JFactory::getApplication()->enqueueMessage('Error fetching modules: ' . $e->getMessage(), 'error');
            return false;
        }

        // Assign all found modules to copied menu item
        if (!empty($modules)) {
            foreach ($modules as $mid) {
                $mdl = new stdClass();
                $mdl->moduleid = $mid;
                $mdl->menuid = $table->id; // This is the new menu item ID
                try {
                    $this->db->insertObject('#__modules_menu', $mdl);
                    JFactory::getApplication()->enqueueMessage('Assigned module ID: ' . $mid . ' to new menu item ID: ' . $table->id);
                } catch (Exception $e) {
                    JFactory::getApplication()->enqueueMessage('Error assigning module ID ' . $mid . ': ' . $e->getMessage(), 'error');
                }
            }
        } else {
            JFactory::getApplication()->enqueueMessage('No modules to assign', 'warning');
        }

        // Continue with any additional logic for exception modules if needed...
    } else {
        JFactory::getApplication()->enqueueMessage('Item is being edited, not duplicating.', 'warning');
    }

    return true;
}
}