<div class="entries form">
<?php echo $this->Form->create('Entry');?>
	<fieldset>
		<legend><?php __('Add Entry'); ?></legend>
	<?php
		echo $this->Form->input('dbtype_id');
		echo $this->Form->input('title');
		echo $this->Form->input('slug');
		echo $this->Form->input('description');
		echo $this->Form->input('media_id');
		echo $this->Form->input('parent_id');
		echo $this->Form->input('status');
		echo $this->Form->input('count');
		echo $this->Form->input('created_by');
		echo $this->Form->input('modified_by');
	?>
	</fieldset>
<?php echo $this->Form->end(__('Submit', true));?>
</div>
<div class="actions">
	<h3><?php __('Actions'); ?></h3>
	<ul>

		<li><?php echo $this->Html->link(__('List Entries', true), array('action' => 'index'));?></li>
		<li><?php echo $this->Html->link(__('List Dbtypes', true), array('controller' => 'dbtypes', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Dbtype', true), array('controller' => 'dbtypes', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(__('List Media', true), array('controller' => 'media', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Media', true), array('controller' => 'media', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(__('List Entries', true), array('controller' => 'entries', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Parent Entry', true), array('controller' => 'entries', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(__('List Entry Details', true), array('controller' => 'entry_details', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Entry Detail', true), array('controller' => 'entry_details', 'action' => 'add')); ?> </li>
	</ul>
</div>