<?php
require_once 'modules/admin/models/ServicePlugin.php';
require_once 'modules/clients/models/UserPackageGateway.php';
require_once 'modules/admin/models/StatusAliasGateway.php';

class PluginAutounsuspend2 extends ServicePlugin
{
    public $hasPendingItems = true;
    protected $featureSet = 'products';

    function getVariables()
    {
        return array(
            lang('Plugin Name') => array(
                'type' => 'hidden',
                'description' => '',
                'value' => lang('Auto Unsuspend 2'),
            ),
            lang('Enabled') => array(
                'type' => 'yesno',
                'description' => lang('Enable automatic unsuspension of paid packages.'),
                'value' => '1',
            ),
            lang('Run schedule - Minute') => array(
                'type' => 'text',
                'description' => lang('Cron schedule: Minute'),
                'value' => '30',
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
        $gateway = new UserPackageGateway($this->user);
        $suspendedPackages = $this->_getSuspendedPackages();
        $unsuspendedCount = 0;

        foreach ($suspendedPackages as $packageId) {
            $package = new UserPackage($packageId, array(), $this->user);
            try {
                $package->unsuspend(true, true);
                $unsuspendedCount++;
            } catch (Exception $e) {
                // Log or handle the error as needed
            }
        }

        return array($this->user->lang('%s package(s) unsuspended.', $unsuspendedCount));
    }

    private function _getSuspendedPackages()
    {
        $statusSuspended = StatusAliasGateway::getInstance($this->user)->getPackageStatusIdsFor(PACKAGE_STATUS_SUSPENDED);
        $userStatusActive = StatusAliasGateway::getInstance($this->user)->getUserStatusIdsFor(USER_STATUS_ACTIVE);

        $query = "SELECT d.id AS domain_id
                  FROM domains d
                  JOIN users u ON d.CustomerID = u.id
                  WHERE d.status IN (" . implode(', ', $statusSuspended) . ")
                  AND u.status IN (" . implode(', ', $userStatusActive) . ")
                  AND NOT EXISTS (
                      SELECT 1
                      FROM invoice i
                      JOIN invoiceentry ie ON i.id = ie.invoiceid
                      WHERE i.status IN (0, 5)
                      AND i.customerid = d.CustomerID
                      AND ie.appliestoid = d.id
                      AND i.billdate < NOW()
                  )";

        $result = $this->db->query($query);
        $packages = array();

        while ($row = $result->fetch()) {
            $packages[] = $row['domain_id'];
        }

        return $packages;
    }
}
?>
