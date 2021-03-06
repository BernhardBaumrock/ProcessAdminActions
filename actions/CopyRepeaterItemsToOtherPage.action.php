<?php

class CopyRepeaterItemsToOtherPage extends ProcessAdminActions {

    protected $title = 'Copy Repeater Items to Other Page';
    protected $description = 'Add the items from a Repeater/RepeaterMatrix field on one page to the same field on another page.';
    protected $notes = 'If the field on the destination page already has items, you can choose to append or reset.';
    protected $author = 'Adrian Jones';
    protected $authorLinks = array(
        'pwforum' => '985-adrian',
        'pwdirectory' => 'adrian-jones',
        'github' => 'adrianbj',
    );

    protected $executeButtonLabel = 'Copy Repeater Items';
    protected $icon = 'copy';

    protected function checkRequirements() {
        if(!$this->wire('modules')->isInstalled("FieldtypeRepeater")) {
            $this->wire()->error('The Repeater field type is not currently installed.');
            return false;
        }
        else {
            return true;
        }
    }

    protected function defineOptions() {
        return array(
            array(
                'name' => 'repeaterField',
                'label' => 'Repeater Field',
                'description' => 'Choose the Repeater field that you want to copy',
                'type' => 'select',
                'required' => true,
                'options' => $this->wire('fields')->find("type=FieldtypeRepeater|FieldtypeRepeaterMatrix")->getArray()
            ),
            array(
                'name' => 'repeaterItemSelector',
                'label' => 'Repeater Item Selector',
                'description' => 'Optional selector to limit repeater items. Leave empty to select all items.',
                'notes' => 'eg. price>50',
                'type' => 'text'
            ),
            array(
                'name' => 'sourcePage',
                'label' => 'Source Page',
                'description' => 'The source page for the contents of the repeater field',
                'type' => 'pageListSelect',
                'required' => true
            ),
            array(
                'name' => 'destinationPage',
                'label' => 'Destination Page',
                'description' => 'The destination page for the contents of the repeater field',
                'type' => 'pageListSelect',
                'required' => true
            ),
            array(
                'name' => 'appendReset',
                'label' => 'Append or Reset',
                'description' => 'Should the items be appended to existing items, or should all existing items be removed?',
                'type' => 'radios',
                'required' => true,
                'options' => array(
                    'append' => 'Append',
                    'reset' => 'Reset'
                ),
                'optionColumns' => 1,
                'value' => 'append',
            )
        );
    }


    protected function executeAction($options) {

        $repeaterField = $this->wire('fields')->get((int)$options['repeaterField']);
        $repeaterFieldName = $repeaterField->name;
        $sourcePage = $this->wire('pages')->get((int)$options['sourcePage']);
	    if(!$sourcePage->fields->get($repeaterFieldName)) {
		    $this->failureMessage = 'Field ' . $repeaterFieldName . ' does not exist on page ' . $sourcePage->path;
		    return false;
	    }
        $destinationPage = $this->wire('pages')->get((int)$options['destinationPage']);
	    if(!$destinationPage->fields->get($repeaterFieldName)) {
		    $this->failureMessage = 'Field ' . $repeaterFieldName . ' does not exist on page ' . $destinationPage->path;
		    return false;
	    }

        $sourcePage->of(false);
        $destinationPage->of(false);

        if($options['appendReset'] == 'reset') {
            $destinationPage->$repeaterFieldName->removeAll();
            $destinationPage->save($repeaterFieldName);
        }

        if($options['repeaterItemSelector'] != '') {
            $repeaterItems = $sourcePage->$repeaterFieldName->find("{$options['repeaterItemSelector']}");
        }
        else {
            $repeaterItems = $sourcePage->$repeaterFieldName;
        }

        $newRepeaterParent = $this->wire->pages->get("name=for-page-".$destinationPage);
        foreach($repeaterItems as $item) {
            $this->wire->pages->clone($item, $newRepeaterParent);
        }

        $this->successMessage = 'The contents of the ' . $repeaterFieldName . ' field were successfully copied from the ' . $sourcePage->path . ' page to the ' . $destinationPage->path . ' page.';
        return true;

    }

}