<?php if (!defined('APPLICATION')) exit();

// Define the plugin:
$PluginInfo['DiscussionsCategoryFilter'] = array(
   'Name' => 'Discussions Category Filter',
   'Description' => 'Filters what categories to show on all discussions page.',
   'Version' => '1.0',
   'Author' => "Brandon Summers",
   'AuthorEmail' => 'brandon@evolutionpixels.com',
   'AuthorUrl' => 'http://www.evolutionpixels.com'
);

class DiscussionsCategoryFilterPlugin extends Gdn_Plugin {

	public function Base_GetAppSettingsMenuItems_Handler(&$Sender) {
		$Menu = &$Sender->EventArguments['SideMenu'];
		
		$LinkText = T('Discussions Category Filtering');
		$Menu->AddItem('Forum', T('Forum'));
		$Menu->AddLink('Forum', $LinkText, 'plugin/discussionscategoryfilter', 'Garden.Settings.Manage');
	}

	public function PluginController_DiscussionsCategoryFilter_Create(&$Sender)
	{
		$Sender->Title('Discussions: Category Filtering');
		$Sender->AddSideMenu('plugin/discussionscategoryfilter');
		$Sender->Form = new Gdn_Form();
		$this->Dispatch($Sender, $Sender->RequestArgs);
	}

	public function Controller_Index(&$Sender)
	{
		$Sender->AddCssFile('admin.css');
		$Sender->CategoryData = $this->GetAllCategories();
		
		$Sender->Render($this->GetView('discussionscategoryfilter.php'));
	}

	public function Controller_Disable(&$Sender)
	{
		$Arguments = $Sender->RequestArgs;
		
		if (sizeof($Arguments) != 2)
			return;
		
		list($Controller, $CategoryID) = $Arguments;

		Gdn::SQL()->Delete('Flag',array(
			'ForeignURL'      => $URL
		));
		Gdn::SQL()->Update('Category')
           ->Set('ShowInAllDiscussions', 0)
           ->Where('CategoryID', $CategoryID)
           ->Put();

		$this->Controller_Index($Sender);
	}

	public function Controller_Enable(&$Sender)
	{
		$Arguments = $Sender->RequestArgs;
		
		if (sizeof($Arguments) != 2)
			return;
		
		list($Controller, $CategoryID) = $Arguments;

		Gdn::SQL()->Update('Category')
           ->Set('ShowInAllDiscussions', 1)
           ->Where('CategoryID', $CategoryID)
           ->Put();

		$this->Controller_Index($Sender);
	}

	public function Controller_EnableAll(&$Sender)
	{
		Gdn::SQL()->Update('Category')
           ->Set('ShowInAllDiscussions', 1)
           ->Put();

		$this->Controller_Index($Sender);
	}

	public function DiscussionModel_BeforeGet_Handler(&$Sender)
	{
		if (Gdn::Dispatcher()->Application() == 'vanilla' 
			AND Gdn::Dispatcher()->ControllerName() == 'DiscussionsController' 
			AND Gdn::Dispatcher()->ControllerMethod() == 'index')
		{
			$Sender->SQL->Where('ca.ShowInAllDiscussions =', '1');
		}
	}

	public function GetAllCategories()
	{
		$Categories = Gdn::SQL()
			->Select('c.ParentCategoryID, c.CategoryID, c.Name, c.CountDiscussions, c.AllowDiscussions, c.ShowInAllDiscussions')
			->From('Category c')
			->OrderBy('Sort', 'asc');

		return $Categories->Get();
	}

	public function Setup()
	{
		$Structure = Gdn::Structure();

		// Add a column to the category table.
		$Structure->Table('Category')
			->Column('ShowInAllDiscussions', 'tinyint(4)', '1')
			->Set(FALSE, FALSE);
	}

}