<?php
namespace workingconcept\rangeslider\migrations;

use craft\db\Migration;
use craft\db\Query;

class Install extends Migration
{
    public function safeUp()
    {
        if ($this->_upgradeFromCraft2()) {
            return;
        }

        // Fresh install code goes here...
    }

    private function _upgradeFromCraft2(): bool
    {
        // Fetch the old plugin row, if it was installed
        $plugins = (new \craft\db\Query())
            ->select(['id', 'handle'])
            ->from(['{{%plugins}}'])
            ->where(['in', 'handle', ['mx--range-slider']])
            ->one();
        
        if (!$plugins) {
            return false;
        }

        // Update this one's settings to old values
        $projectConfig = \Craft::$app->projectConfig;
        $oldPluginHandle = "plugins.{$plugins['handle']}";
        $newPluginHandle = 'plugins.range-slider';
        $projectConfig->set($newPluginHandle, $projectConfig->get($oldPluginHandle));

        // Rewrite all fields to new Range Slider Type
        $fields = (new Query())
            ->select(['id'])
            ->from(['{{%fields}}'])
            ->where(['type' => ['MX_RangeSlider_Range']])
            ->column();

        if (!empty($fields)) {
            foreach ($fields as $key => $fieldId) {
                $this->update('{{%fields}}', [
                    'type' => 'workingconcept\rangeslider\fields\RangeSliderField',
                ], ['id' => $fieldId], [], false);
            }
        }

        // Rewrite the projectconfigs to new Range Slider Type
        $configs = (new Query())
            ->select(['path'])
            ->from(['{{%projectconfig}}'])
            ->where(['value' => ['MX_RangeSlider_Range']])
            ->column();

        if (!empty($configs)) {
            foreach ($configs as $key => $fieldId) {
                $this->update('{{%projectconfig}}', [
                    'value' => '"workingconcept\\rangeslider\\fields\\RangeSliderField"',
                ], ['path' => $fieldId], [], false);
            }
        }

        // Delete the old plugin row and project config data
        $this->delete('{{%plugins}}', ['id' => $plugins['id']]);
        $projectConfig->remove($oldPluginHandle);

        return true;
    }

    public function safeDown()
    {
    }
}