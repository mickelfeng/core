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
require_once("load_balancer_maintable.inc");

if (!is_array($config['load_balancer']['lbprotocol'])) {
	$config['load_balancer']['lbprotocol'] = array();
}
$a_protocol = &$config['load_balancer']['lbprotocol'];

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
	if (array_key_exists($_GET['id'], $a_protocol)) {
		/* make sure no virtual servers reference this entry */
		if (is_array($config['load_balancer']['virtual_server'])) {
			foreach ($config['load_balancer']['virtual_server'] as $vs) {
				if ($vs['protocol'] == $a_protocol[$_GET['id']]['name']) {
					$input_errors[] = gettext("This entry cannot be deleted because it is still referenced by at least one virtual server.");
					break;
				}
			}
		}

		if (!$input_errors) {
			unset($a_protocol[$_GET['id']]);
			write_config();
			mark_subsystem_dirty('loadbalancer');
			header("Location: load_balancer_relay_protocol.php");
			exit;
		}
	}
}

/* Index lbpool array for easy hyperlinking */
/* for ($i = 0; isset($config['load_balancer']['lbprotocol'][$i]); $i++) {
	for ($o = 0; isset($config['load_balancer']['lbprotocol'][$i]['options'][$o]); o++) {
		$a_vs[$i]['options'][$o] = "
	$a_vs[$i]['poolname'] = "<a href=\"/load_balancer_pool_edit.php?id={$poodex[$a_vs[$i]['poolname']]}\">{$a_vs[$i]['poolname']}</a>";
	if ($a_vs[$i]['sitedown'] != '') {
		$a_vs[$i]['sitedown'] = "<a href=\"/load_balancer_pool_edit.php?id={$poodex[$a_vs[$i]['sitedown']]}\">{$a_vs[$i]['sitedown']}</a>";
	} else {
		$a_vs[$i]['sitedown'] = 'none';
	}
}
*/

$pgtitle = array(gettext("Services"), gettext("Load Balancer"),gettext("Relay Protocol"));
$shortcut_section = "relayd";

include("head.inc");

?>

<body>
<?php include("fbegin.inc"); ?>

	<section class="page-content-main">
		<div class="container-fluid">
			<div class="row">

				<?php if (isset($input_errors) && count($input_errors) > 0) print_input_errors($input_errors); ?>
				<?php if (isset($savemsg)) print_info_box($savemsg); ?>
				<?php if (is_subsystem_dirty('loadbalancer')): ?><p>
				<?php print_info_box_np(gettext("The load balancer configuration has been changed") . ".<br />" . gettext("You must apply the changes in order for them to take effect."));?><br />
				<?php endif; ?>

			    <section class="col-xs-12">

					<?php
					        /* active tabs */
					        $tab_array = array();
					        $tab_array[] = array(gettext("Monitors"), false, "load_balancer_monitor.php");
					        $tab_array[] = array(gettext("Pools"), false, "load_balancer_pool.php");
					        $tab_array[] = array(gettext("Virtual Servers"), false, "load_balancer_virtual_server.php");
					        $tab_array[] = array(gettext("Relay Actions"), false, "load_balancer_relay_action.php");
					        $tab_array[] = array(gettext("Relay Protocols"), true, "load_balancer_relay_protocol.php");
					        display_top_tabs($tab_array);
					  ?>

					<div class="tab-content content-box col-xs-12">
				    <div class="container-fluid">

					  <form action="load_balancer_relay_protocol.php" method="post" name="iform" id="iform">

								<div class="table-responsive">
<?
			$t = new MainTable();
			$t->edit_uri('load_balancer_relay_protocol_edit.php');
			$t->my_uri('load_balancer_relay_protocol.php');
			$t->add_column(gettext('Name'),'name',20);
			$t->add_column(gettext('Type'),'type',10);
			$t->add_column(gettext('Options'),'options',30);
			$t->add_column(gettext('Description'),'descr',30);
			$t->add_button('edit');
			$t->add_button('dup');
			$t->add_button('del');
			$t->add_content_array($a_protocol);
			$t->display();
?>
								</div>
					  </form>
				    </div>
					</div>
			    </section>
			</div>
		</div>
	</section>

<?php include("foot.inc"); ?>
