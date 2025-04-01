<?php
/**
 * @package     jt_copymoduleassignments Joomla.Plugin
 * @copyright   Copyright (C) http://www.joomlatema.net, Inc.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @author      JoomlaTema.Net
 * @link        http://www.joomlatema.net
 */

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

/**
 * Copy Module Assignments Plugin
 */
class PlgContentJt_Copymoduleassignments extends CMSPlugin
{
    public function onContentAfterSave($context, &$table, $isNew)
    {
        $app = Factory::getApplication();
        $db = Factory::getContainer()->get(DatabaseInterface::class); // Get database instance

        // Debugging output
        $app->enqueueMessage('Context: ' . $context);
        $app->enqueueMessage('Is New: ' . ($isNew ? 'Yes' : 'No'));

        // Return if invalid context
        if ($context !== 'com_menus.item') {
            $app->enqueueMessage('Invalid context, exiting.', 'error');
            return true;
        }

        // Only proceed if the item is new
        if ($isNew) {
            // Get the original menu item ID from the submitted data
            $originalMenuId = $app->input->getInt('id', 0);

            // Debugging output
            $app->enqueueMessage('New Menu Item ID: ' . $table->id);
            $app->enqueueMessage('Original Menu ID: ' . $originalMenuId);

            // Fetch assigned modules for the original menu ID
            $query = $db->getQuery(true)
                ->select($db->quoteName('moduleid'))
                ->from($db->quoteName('#__modules_menu'))
                ->where($db->quoteName('menuid') . ' = ' . (int) $originalMenuId);

            $db->setQuery($query);

            try {
                $modules = (array) $db->loadColumn();
                $app->enqueueMessage('Modules Found: ' . count($modules));
            } catch (\Exception $e) {
                $app->enqueueMessage('Error fetching modules: ' . $e->getMessage(), 'error');
                return false;
            }

            // Assign all found modules to copied menu item
            if (!empty($modules)) {
                foreach ($modules as $mid) {
                    $mdl = (object) [
                        'moduleid' => $mid,
                        'menuid'   => $table->id
                    ];

                    try {
                        $db->insertObject('#__modules_menu', $mdl);
                        $app->enqueueMessage('Assigned module ID: ' . $mid . ' to new menu item ID: ' . $table->id);
                    } catch (\Exception $e) {
                        $app->enqueueMessage('Error assigning module ID ' . $mid . ': ' . $e->getMessage(), 'error');
                    }
                }
            } else {
                $app->enqueueMessage('No modules to assign', 'warning');
            }
        } else {
            $app->enqueueMessage('Item is being edited, not duplicating.', 'warning');
        }

        return true;
    }
}
