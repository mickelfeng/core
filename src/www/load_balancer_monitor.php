<?php

/*
	Copyright (C) 2014-2015 Deciso B.V.
	Copyright (C) 2008 Bill Marquette <bill.marquette@gmail.com>.
	All rights reserved.

	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:

	1. Redistributions of source code must retain the above copyright notice,
	   this list of conditions and the following disclaimer.

	2. Redistributions in binary form must reproduce the above copyright
	   notice, this list of conditions and the following disclaimer in the
	   documentation and/or other materials provided with the distribution.

	THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
	POSSIBILITY OF SUCH DAMAGE.
*/

require_once("guiconfig.inc");
require_once("functions.inc");
require_once("filter.inc");
require_once("util.inc");
require_once("load_balancer_maintable.inc");

if (!is_array($config['load_balancer']['monitor_type'])) {
	$config['load_balancer']['monitor_type'] = array();
}
$a_monitor = &$config['load_balancer']['monitor_type'];

if ($_POST) {
	$pconfig = $_POST;

	if ($_POST['apply']) {
		$retval = 0;
		$retval |= filter_configure();
		$retval |= relayd_configure();

		$savemsg = get_std_save_message($retval);
		clear_subsystem_dirty('loadbalancer');
	}
}

if ($_GET['act'] == "del") {
	if (array_key_exists($_GET['id'], $a_monitor)) {
		/* make sure no pools reference this entry */
		if (is_array($config['load_balancer']['lbpool'])) {
			foreach ($config['load_balancer']['lbpool'] as $pool) {
				if ($pool['monitor'] == $a_monitor[$_GET['id']]['name']) {
					$input_errors[] = gettext("This entry cannot be deleted because it is still referenced by at least one pool.");
					break;
				}
			}
		}

		if (!$input_errors) {
			unset($a_monitor[$_GET['id']]);
			write_config();
			mark_subsystem_dirty('loadbalancer');
			header("Location: load_balancer_monitor.php");
			exit;
		}
	}
}

$pgtitle = array(gettext("Services"),gettext("Load Balancer"),gettext("Monitor"));
$shortcut_section = "relayd";

include("head.inc");

$main_buttons = array(
	array('label'=>'Add', 'href'=>'load_balancer_monitor_edit.php'),
);
?>

<body>
<?php include("fbegin.inc"); ?>

	<section class="page-content-main">
		<div class="container-fluid">
			<div class="row">

				<?php if (isset($input_errors) && count($input_errors) > 0) print_input_errors($input_errors); ?>
				<?php if (isset($savemsg)) print_info_box($savemsg); ?>
				<?php if (is_subsystem_dirty('loadbalancer')): ?><br/>
				<?php print_info_box_np(gettext("The load balancer configuration has been changed") . ".<br />" . gettext("You must apply the changes in order for them to take effect."));?><br />
				<?php endif; ?>

			    <section class="col-xs-12">

				<?php
				        /* active tabs */
				        $tab_array = array();
				        $tab_array[] = array(gettext("Pools"), false, "load_balancer_pool.php");
				        $tab_array[] = array(gettext("Virtual Servers"), false, "load_balancer_virtual_server.php");
				        $tab_array[] = array(gettext("Monitors"), true, "load_balancer_monitor.php");
				        $tab_array[] = array(gettext("Settings"), false, "load_balancer_setting.php");
				        display_top_tabs($tab_array);
					?>

					<div class="tab-content content-box col-xs-12">


					  <form action="load_balancer_monitor.php" method="post" name="iform" id="iform">

								<div class="table-responsive">

								<?php
											$t = new MainTable();
											$t->edit_uri('load_balancer_monitor_edit.php');
											$t->my_uri('load_balancer_monitor.php');
											$t->add_column(gettext('Name'),'name',20);
											$t->add_column(gettext('Type'),'type',10);
											$t->add_column(gettext('Description'),'descr',30);
											$t->add_button('edit');
											$t->add_button('dup');
											$t->add_button('del');
											$t->add_content_array($a_monitor);
											$t->display();
								?>
								</div>
					  </form>
					</div>
			    </section>
			</div>
		</div>
	</section>


<?php include("foot.inc"); ?>
