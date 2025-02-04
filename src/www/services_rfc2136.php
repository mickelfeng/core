<?php

/*
	Copyright (C) 2014-2015 Deciso B.V.
	Copyright (C) 2008 Ermal Luçi
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

if (!is_array($config['dnsupdates']['dnsupdate']))
	$config['dnsupdates']['dnsupdate'] = array();

$a_rfc2136 = &$config['dnsupdates']['dnsupdate'];

if ($_GET['act'] == "del") {
	unset($a_rfc2136[$_GET['id']]);

	write_config();

	header("Location: services_rfc2136.php");
	exit;
}

$pgtitle = array(gettext("Services"), gettext("RFC 2136 clients"));
include("head.inc");

$main_buttons = array(
	array('label'=>'Add', 'href'=>'services_rfc2136_edit.php'),
);
?>

<body>
<?php include("fbegin.inc"); ?>

	<section class="page-content-main">
		<div class="container-fluid">
			<div class="row">

				<?php if (isset($input_errors) && count($input_errors) > 0) print_input_errors($input_errors); ?>

			    <section class="col-xs-12">

				<?php
						$tab_array = array();
						$tab_array[] = array(gettext("DynDns"), false, "services_dyndns.php");
						$tab_array[] = array(gettext("RFC 2136"), true, "services_rfc2136.php");
						display_top_tabs($tab_array);
					?>

					<div class="tab-content content-box col-xs-12">


					  <form action="services_rfc2136.php" method="post" name="iform" id="iform">

								<div class="table-responsive">
									<table class="table table-striped">


										<tr>
										  <td width="5%"  class="listhdrr"><?=gettext("If");?></td>
										  <td width="15%" class="listhdrr"><?=gettext("Server");?></td>
										  <td width="20%" class="listhdrr"><?=gettext("Hostname");?></td>
										  <td width="25%" class="listhdrr"><?=gettext("Cached IP");?></td>
										  <td width="25%" class="listhdr"><?=gettext("Description");?></td>
										  <td width="10%" class="list"></td>
										</tr>
										<?php $i = 0; foreach ($a_rfc2136 as $rfc2136): ?>
										<tr ondblclick="document.location='services_rfc2136_edit.php?id=<?=$i;?>'">
										  <td class="listlr">
										  <?php
											$iflist = get_configured_interface_with_descr();
											foreach ($iflist as $if => $ifdesc) {
												if ($rfc2136['interface'] == $if) {
													if (!isset($rfc2136['enable']))
														echo "<span class=\"gray\">{$ifdesc}</span>";
													else
														echo "{$ifdesc}";
													break;
												}
											}
										  ?>
										  </td>
										  <td class="listr">
										  <?php
											if (!isset($rfc2136['enable']))
												echo "<span class=\"gray\">" . htmlspecialchars($rfc2136['server']) . "</span>";
											else
												echo htmlspecialchars($rfc2136['server']);
										  ?>
										  </td>
										  <td class="listr">
										  <?php
											if (!isset($rfc2136['enable']))
												echo "<span class=\"gray\">" . htmlspecialchars($rfc2136['host']) . "</span>";
											else
												echo htmlspecialchars($rfc2136['host']);
										  ?>
										  </td>
										  <td class="listr">
										  <?php
											$filename = "/conf/dyndns_{$rfc2136['interface']}_rfc2136_" . escapeshellarg($rfc2136['host']) . "_{$rfc2136['server']}.cache";
											if (file_exists($filename)) {
												echo "IPv4: ";
												if (isset($rfc2136['usepublicip']))
													$ipaddr = dyndnsCheckIP($rfc2136['interface']);
												else
													$ipaddr = get_interface_ip($rfc2136['interface']);
												$cached_ip_s = explode("|", file_get_contents($filename));
												$cached_ip = $cached_ip_s[0];
												if ($ipaddr <> $cached_ip)
													echo "<font color='red'>";
												else
													echo "<font color='green'>";
												echo htmlspecialchars($cached_ip);
												echo "</font>";
											} else {
												echo "IPv4: N/A";
											}
											echo "<br />";
											if (file_exists("{$filename}.ipv6")) {
												echo "IPv6: ";
												$ipaddr = get_interface_ipv6($rfc2136['interface']);
												$cached_ip_s = explode("|", file_get_contents("{$filename}.ipv6"));
												$cached_ip = $cached_ip_s[0];
												if ($ipaddr <> $cached_ip)
													echo "<font color='red'>";
												else
													echo "<font color='green'>";
												echo htmlspecialchars($cached_ip);
												echo "</font>";
											} else {
												echo "IPv6: N/A";
											}
										  ?>
										  </td>
										  <td class="listbg">
										  <?php
											if (!isset($rfc2136['enable']))
												echo "<span class=\"gray\">" . htmlspecialchars($rfc2136['descr']) . "</span>";
											else
												echo htmlspecialchars($rfc2136['descr']);
										  ?>
										  </td>
										  <td valign="middle" class="list nowrap">
											<a href="services_rfc2136_edit.php?id=<?=$i;?>"><img src="./themes/<?= $g['theme']; ?>/images/icons/icon_e.gif" width="17" height="17" border="0" alt="edit" /></a>
											&nbsp;<a href="services_rfc2136.php?act=del&amp;id=<?=$i;?>" onclick="return confirm('<?=gettext("Do you really want to delete this client?");?>')"><img src="./themes/<?= $g['theme']; ?>/images/icons/icon_x.gif" width="17" height="17" border="0" alt="delete" /></a>
										  </td>
										</tr>
										<?php $i++; endforeach; ?>


									  </table>
									  </div>
					  </form>

					</div>
			    </section>
			</div>
		</div>
	</section>

<?php include("foot.inc"); ?>
