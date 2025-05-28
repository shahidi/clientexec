<?php
require_once 'modules/admin/models/ServicePlugin.php';
require_once 'modules/clients/models/UserPackageGateway.php';
require_once 'modules/admin/models/StatusAliasGateway.php';

class PluginAutosuspend2 extends ServicePlugin
{
    public $hasPendingItems = true;
    protected $featureSet = 'products';

    function getVariables()
    {
        return array(
            lang('Plugin Name') => array(
                'type' => 'hidden',
                'description' => '',
                'value' => lang('Auto Suspend 2'),
            ),
            lang('Enabled') => array(
                'type' => 'yesno',
                'description' => lang('Enable automatic suspension of overdue packages.'),
                'value' => '1',
            ),
            lang('Days Overdue Before Suspending') => array(
                'type' => 'text',
                'description' => lang('Number of days a package must be overdue before suspension.'),
                'value' => '7',
            ),
            lang('Run schedule - Minute') => array(
                'type' => 'text',
                'description' => lang('Cron schedule: Minute'),
                'value' => '0',
            ),
            lang('Run schedule - Hour') => array(
                'type' => 'text',
                'description' => lang('Cron schedule: Hour'),
                'value' => '2',
            ),
            lang('Run schedule - Day') => array(
                'type' => 'text',
                'description' => lang('Cron schedule: Day'),
                'value' => '*',
            ),
            lang('Run schedule - Month') => array(
                'type' => 'text',
                'description' => lang('Cron schedule: Month'),
                'value' => '*',
            ),
            lang('Run schedule - Day of the week') => array(
                'type' => 'text',
                'description' => lang('Cron schedule: Day of the week'),
                'value' => '*',
            ),
        );
    }

    function execute()
    {
        $daysOverdue = (int)$this->settings->get('plugin_autosuspend2_Days Overdue Before Suspending');
        if ($daysOverdue <= 0) {
            return array($this->user->lang('Suspension is disabled.'));
        }

        $gateway = new UserPackageGateway($this->user);
        $overduePackages = $this->_getOverduePackages($daysOverdue);
        $suspendedCount = 0;

        foreach ($overduePackages as $packageId => $dueDate) {
            $package = new UserPackage($packageId, array(), $this->user);
            try {
                $package->suspend(true, true);
                $suspendedCount++;
            } catch (Exception $e) {
                // Log or handle the error as needed
            }
        }

        return array($this->user->lang('%s package(s) suspended.', $suspendedCount));
    }

    private function _getOverduePackages($daysOverdue)
    {
        $statusActive = StatusAliasGateway::packageActiveAliases($this->user);
        $statusActive = implode(', ', $statusActive);
        $query = "SELECT invoiceentry.appliestoid, invoice.billdate
                  FROM invoiceentry
                  JOIN invoice ON invoice.id = invoiceentry.invoiceid
                  JOIN domains ON invoiceentry.appliestoid = domains.id
                  WHERE invoice.status IN (0, 5)
                  AND invoice.billdate < DATE_SUB(NOW(), INTERVAL ? DAY)
                  AND domains.status IN ({$statusActive})";

        $result = $this->db->query($query, $daysOverdue);
        $overduePackages = array();

        while ($row = $result->fetch()) {
            $overduePackages[$row['appliestoid']] = strtotime($row['billdate']);
        }

        return $overduePackages;
    }
}
?>
