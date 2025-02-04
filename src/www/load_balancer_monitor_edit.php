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

$rfc2616 = array(
	100 => "100 Continue",
	101 => "101 Switching Protocols",
	200 => "200 OK",
	201 => "201 Created",
	202 => "202 Accepted",
	203 => "203 Non-Authoritative Information",
	204 => "204 No Content",
	205 => "205 Reset Content",
	206 => "206 Partial Content",
	300 => "300 Multiple Choices",
	301 => "301 Moved Permanently",
	302 => "302 Found",
	303 => "303 See Other",
	304 => "304 Not Modified",
	305 => "305 Use Proxy",
	306 => "306 (Unused)",
	307 => "307 Temporary Redirect",
	400 => "400 Bad Request",
	401 => "401 Unauthorized",
	402 => "402 Payment Required",
	403 => "403 Forbidden",
	404 => "404 Not Found",
	405 => "405 Method Not Allowed",
	406 => "406 Not Acceptable",
	407 => "407 Proxy Authentication Required",
	408 => "408 Request Timeout",
	409 => "409 Conflict",
	410 => "410 Gone",
	411 => "411 Length Required",
	412 => "412 Precondition Failed",
	413 => "413 Request Entity Too Large",
	414 => "414 Request-URI Too Long",
	415 => "415 Unsupported Media Type",
	416 => "416 Requested Range Not Satisfiable",
	417 => "417 Expectation Failed",
	500 => "500 Internal Server Error",
	501 => "501 Not Implemented",
	502 => "502 Bad Gateway",
	503 => "503 Service Unavailable",
	504 => "504 Gateway Timeout",
	505 => "505 HTTP Version Not Supported"
);

function is_rfc2616_code($code) {
	global $rfc2616;
	if (isset($rfc2616[$code]))
		return true;
	else
		return false;
}

function print_rfc2616_select($tag, $current){
	global $rfc2616;

	/* Default to 200 OK if not set */
	if ($current == "")
		$current = 200;

	echo "<select id=\"{$tag}\" name=\"{$tag}\">\n";
	foreach($rfc2616 as $code => $message) {
		if ($code == $current) {
			$sel = " selected=\"selected\"";
		} else {
			$sel = "";
		}
		echo "<option value=\"{$code}\"{$sel}>{$message}</option>\n";
	}
	echo "</select>\n";
}


$referer = (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/load_balancer_monitor.php');

if (!is_array($config['load_balancer']['monitor_type'])) {
	$config['load_balancer']['monitor_type'] = array();
}
$a_monitor = &$config['load_balancer']['monitor_type'];

if (is_numericint($_GET['id']))
	$id = $_GET['id'];
if (isset($_POST['id']) && is_numericint($_POST['id']))
	$id = $_POST['id'];

if (isset($id) && $a_monitor[$id]) {
	$pconfig['name'] = $a_monitor[$id]['name'];
	$pconfig['type'] = $a_monitor[$id]['type'];
	$pconfig['descr'] = $a_monitor[$id]['descr'];
	$pconfig['options'] = array();
	$pconfig['options'] = $a_monitor[$id]['options'];
} else {
	/* Some sane page defaults */
	$pconfig['options']['path'] = '/';
	$pconfig['options']['code'] = 200;
}

$changedesc = gettext("Load Balancer: Monitor:") . " ";
$changecount = 0;

if ($_POST) {
	$changecount++;

	unset($input_errors);
	$pconfig = $_POST;

	/* turn $_POST['http_options_*'] into $pconfig['options'][*] */
	foreach($_POST as $key => $val) {
		if (stristr($key, 'options') !== false) {
			if (stristr($key, $pconfig['type'].'_') !== false) {
				$opt = explode('_',$key);
				$pconfig['options'][$opt[2]] = $val;
			}
			unset($pconfig[$key]);
		}
	}

	/* input validation */
	$reqdfields = explode(" ", "name type descr");
	$reqdfieldsn = array(gettext("Name"),gettext("Type"),gettext("Description"));

	do_input_validation($_POST, $reqdfields, $reqdfieldsn, $input_errors);

	/* Ensure that our monitor names are unique */
	for ($i=0; isset($config['load_balancer']['monitor_type'][$i]); $i++)
		if (($_POST['name'] == $config['load_balancer']['monitor_type'][$i]['name']) && ($i != $id))
			$input_errors[] = gettext("This monitor name has already been used.  Monitor names must be unique.");

	if (strpos($_POST['name'], " ") !== false)
		$input_errors[] = gettext("You cannot use spaces in the 'name' field.");

	switch($_POST['type']) {
		case 'icmp': {
			break;
		}
		case 'tcp': {
			break;
		}
		case 'http':
		case 'https': {
			if (is_array($pconfig['options'])) {
				if (isset($pconfig['options']['host']) && $pconfig['options']['host'] != "") {
					if (!is_hostname($pconfig['options']['host'])) {
						$input_errors[] = gettext("The hostname can only contain the characters A-Z, 0-9 and '-'.");
					}
				}
				if (isset($pconfig['options']['code']) && $pconfig['options']['code'] != "") {
					// Check code
					if(!is_rfc2616_code($pconfig['options']['code'])) {
						$input_errors[] = gettext("HTTP(s) codes must be from RFC2616.");
					}
				}
				if (!isset($pconfig['options']['path']) || $pconfig['options']['path'] == "") {
					$input_errors[] = gettext("The path to monitor must be set.");
				}
			}
			break;
		}
		case 'send': {
			if (is_array($pconfig['options'])) {
				if (isset($pconfig['options']['send']) && $pconfig['options']['send'] != "") {
					// Check send
				}
				if (isset($pconfig['options']['expect']) && $pconfig['options']['expect'] != "") {
					// Check expect
				}
			}
			break;
		}
	}

	if (!$input_errors) {
		$monent = array();
		if(isset($id) && $a_monitor[$id])
			$monent = $a_monitor[$id];
		if($monent['name'] != "")
			$changedesc .= " " . sprintf(gettext("modified '%s' monitor:"), $monent['name']);

		update_if_changed("name", $monent['name'], $pconfig['name']);
		update_if_changed("type", $monent['type'], $pconfig['type']);
		update_if_changed("description", $monent['descr'], $pconfig['descr']);
		if($pconfig['type'] == "http" || $pconfig['type'] == "https" ) {
			/* log updates, then clear array and reassign - dumb, but easiest way to have a clear array */
			update_if_changed("path", $monent['options']['path'], $pconfig['options']['path']);
			update_if_changed("host", $monent['options']['host'], $pconfig['options']['host']);
			update_if_changed("code", $monent['options']['code'], $pconfig['options']['code']);
			$monent['options'] = array();
			$monent['options']['path'] = $pconfig['options']['path'];
			$monent['options']['host'] = $pconfig['options']['host'];
			$monent['options']['code'] = $pconfig['options']['code'];
		}
		if($pconfig['type'] == "send" ) {
			/* log updates, then clear array and reassign - dumb, but easiest way to have a clear array */
			update_if_changed("send", $monent['options']['send'], $pconfig['options']['send']);
			update_if_changed("expect", $monent['options']['expect'], $pconfig['options']['expect']);
			$monent['options'] = array();
			$monent['options']['send'] = $pconfig['options']['send'];
			$monent['options']['expect'] = $pconfig['options']['expect'];
		}
		if($pconfig['type'] == "tcp" || $pconfig['type'] == "icmp") {
			$monent['options'] = array();
		}

		if (isset($id) && $a_monitor[$id]) {
			/* modify all pools with this name */
			for ($i = 0; isset($config['load_balancer']['lbpool'][$i]); $i++) {
				if ($config['load_balancer']['lbpool'][$i]['monitor'] == $a_monitor[$id]['name'])
					$config['load_balancer']['lbpool'][$i]['monitor'] = $monent['name'];
			}
			$a_monitor[$id] = $monent;
		} else
			$a_monitor[] = $monent;

		if ($changecount > 0) {
			/* Mark config dirty */
			mark_subsystem_dirty('loadbalancer');
			write_config($changedesc);
		}

		header("Location: load_balancer_monitor.php");
		exit;
	}
}

$pgtitle = array(gettext("Services"),gettext("Load Balancer"),gettext("Monitor"),gettext("Edit"));
$shortcut_section = "relayd";

include("head.inc");
$types = array("icmp" => gettext("ICMP"), "tcp" => gettext("TCP"), "http" => gettext("HTTP"), "https" => gettext("HTTPS"), "send" => gettext("Send/Expect"));

?>


<body>
<?php include("fbegin.inc"); ?>

	<script type="text/javascript">
	//<![CDATA[
	function updateType(t){
		switch(t) {
	<?php
		/* OK, so this is sick using php to generate javascript, but it needed to be done */
		foreach ($types as $key => $val) {
			echo "		case \"{$key}\": {\n";
			$t = $types;
			foreach ($t as $k => $v) {
				if ($k != $key) {
					echo "			jQuery('#{$k}').hide();\n";
				}
			}
			echo "		}\n";
		}
	?>
		}
		jQuery('#' + t).show();
	}
	//]]>
	</script>

	<section class="page-content-main">

		<div class="container-fluid">

			<div class="row">

				<?php if (isset($input_errors) && count($input_errors) > 0) print_input_errors($input_errors); ?>

			    <section class="col-xs-12">

				<div class="content-box">

                        <form action="load_balancer_monitor_edit.php" method="post" name="iform" id="iform">

				<div class="table-responsive">
					<table class="table table-striped table-sort">
						<tr>
										<td colspan="2" valign="top" class="listtopic"><?=gettext("Edit Load Balancer - Monitor entry"); ?></td>
							                </tr>
									<tr align="left">
										<td width="22%" valign="top" class="vncellreq"><?=gettext("Name"); ?></td>
										<td width="78%" class="vtable" colspan="2">
											<input name="name" type="text" <?if(isset($pconfig['name'])) echo "value=\"" . htmlspecialchars($pconfig['name']) . "\"";?> size="16" maxlength="16" />
										</td>
									</tr>
									<tr align="left">
										<td width="22%" valign="top" class="vncellreq"><?=gettext("Description"); ?></td>
										<td width="78%" class="vtable" colspan="2">
											<input name="descr" type="text" <?if(isset($pconfig['descr'])) echo "value=\"" . htmlspecialchars($pconfig['descr']) . "\"";?> size="64" />
										</td>
									</tr>
									<tr align="left">
										<td width="22%" valign="top" class="vncellreq"><?=gettext("Type"); ?></td>
										<td width="78%" class="vtable" colspan="2">
											<select id="type" name="type">
							<?
												foreach ($types as $key => $val) {
													if(isset($pconfig['type']) && $pconfig['type'] == $key) {
														$selected = " selected=\"selected\"";
													} else {
														$selected = "";
													}
													echo "<option value=\"{$key}\" onclick=\"updateType('{$key}');\"{$selected}>{$val}</option>\n";
												}
							?>
											</select>
										</td>
									</tr>
									<tr align="left" id="icmp"<?= $pconfig['type'] == "icmp" ? "" : " style=\"display:none;\""?>><td></td>
									</tr>
									<tr align="left" id="tcp"<?= $pconfig['type'] == "tcp" ? "" : " style=\"display:none;\""?>><td></td>
									</tr>
									<tr align="left" id="http"<?= $pconfig['type'] == "http" ? "" : " style=\"display:none;\""?>>
										<td width="22%" valign="top" class="vncellreq"><?=gettext("HTTP"); ?></td>
										<td width="78%" class="vtable" colspan="2">
											<table width="100%" border="0" cellpadding="6" cellspacing="0" summary="http">
												<tr align="left">
													<td valign="top" align="right" class="vtable"><?=gettext("Path"); ?></td>
													<td class="vtable" colspan="2">
														<input name="http_options_path" type="text" <?if(isset($pconfig['options']['path'])) echo "value=\"" . htmlspecialchars($pconfig['options']['path']) . "\"";?> size="64" />
													</td>
												</tr>
												<tr align="left">
													<td valign="top"  align="right" class="vtable"><?=gettext("Host"); ?></td>
													<td class="vtable" colspan="2">
														<input name="http_options_host" type="text" <?if(isset($pconfig['options']['host'])) echo "value=\"" . htmlspecialchars($pconfig['options']['host']) . "\"";?> size="64" /><br /><?=gettext("Hostname for Host: header if needed."); ?>
													</td>
												</tr>
												<tr align="left">
													<td valign="top"  align="right" class="vtable"><?=gettext("HTTP Code"); ?></td>
													<td class="vtable" colspan="2">
														<?= print_rfc2616_select("http_options_code", $pconfig['options']['code']); ?>
													</td>
												</tr>
							<!-- BILLM: XXX not supported digest checking just yet
												<tr align="left">
													<td width="22%" valign="top" class="vncell">MD5 Page Digest</td>
													<td width="78%" class="vtable" colspan="2">
														<input name="digest" type="text" <?if(isset($pconfig['digest'])) echo "value=\"" . htmlspecialchars($pconfig['digest']) . "\"";?>size="32"><br /><b>TODO: add fetch functionality here</b>
													</td>
												</tr>
							-->
											</table>
										</td>
									</tr>
									<tr align="left" id="https"<?= $pconfig['type'] == "https" ? "" : " style=\"display:none;\""?>>
										<td width="22%" valign="top" class="vncellreq"><?=gettext("HTTPS"); ?></td>
										<td width="78%" class="vtable" colspan="2">
											<table width="100%" border="0" cellpadding="6" cellspacing="0" summary="https">
												<tr align="left">
													<td valign="top"  align="right" class="vtable"><?=gettext("Path"); ?></td>
													<td class="vtable" colspan="2">
														<input name="https_options_path" type="text" <?if(isset($pconfig['options']['path'])) echo "value=\"" . htmlspecialchars($pconfig['options']['path']) ."\"";?> size="64" />
													</td>
												</tr>
												<tr align="left">
													<td valign="top"  align="right" class="vtable"><?=gettext("Host"); ?></td>
													<td class="vtable" colspan="2">
														<input name="https_options_host" type="text" <?if(isset($pconfig['options']['host'])) echo "value=\"" . htmlspecialchars($pconfig['options']['host']) . "\"";?> size="64" /><br /><?=gettext("Hostname for Host: header if needed."); ?>
													</td>
												</tr>
												<tr align="left">
													<td valign="top"  align="right" class="vtable"><?=gettext("HTTP Code"); ?></td>
													<td class="vtable" colspan="2">
														<?= print_rfc2616_select("https_options_code", $pconfig['options']['code']); ?>
													</td>
												</tr>
							<!-- BILLM: XXX not supported digest checking just yet

												<tr align="left">
													<td width="22%" valign="top" class="vncellreq">MD5 Page Digest</td>
													<td width="78%" class="vtable" colspan="2">
														<input name="digest" type="text" <?if(isset($pconfig['digest'])) echo "value=\"" . htmlspecialchars($pconfig['digest']) . "\"";?>size="32"><br /><b>TODO: add fetch functionality here</b>
													</td>
												</tr>
							-->
											</table>
										</td>
									</tr>
									<tr align="left" id="send"<?= $pconfig['type'] == "send" ? "" : " style=\"display:none;\""?>>
										<td width="22%" valign="top" class="vncellreq"><?=gettext("Send/Expect"); ?></td>
										<td width="78%" class="vtable" colspan="2">
											<table width="100%" border="0" cellpadding="6" cellspacing="0" summary="send expect">
												<tr align="left">
													<td valign="top"  align="right" class="vtable"><?=gettext("Send string"); ?></td>
													<td class="vtable" colspan="2">
														<input name="send_options_send" type="text" <?if(isset($pconfig['options']['send'])) echo "value=\"" . htmlspecialchars($pconfig['options']['send']) . "\"";?> size="64" />
													</td>
												</tr>
												<tr align="left">
													<td valign="top" align="right"  class="vtable"><?=gettext("Expect string"); ?></td>
													<td class="vtable" colspan="2">
														<input name="send_options_expect" type="text" <?if(isset($pconfig['options']['expect'])) echo "value=\"" . htmlspecialchars($pconfig['options']['expect']) . "\"";?> size="64" />
													</td>
												</tr>
											</table>
										</td>
									</tr>
									<tr align="left">
										<td width="22%" valign="top">&nbsp;</td>
										<td width="78%">
											<input name="Submit" type="submit" class="btn btn-primary" value="<?=gettext("Save"); ?>" />
							<input type="button" class="btn btn-default" value="<?=gettext("Cancel");?>" onclick="window.location.href='<?=$referer;?>'" />
											<?php if (isset($id) && $a_monitor[$id]): ?>
											<input name="id" type="hidden" value="<?=htmlspecialchars($id);?>" />
											<?php endif; ?>
										</td>
									</tr>
								</table>
				</div>
                        </form>
				</div>
			    </section>
			</div>
		</div>
	</section>

<?php include("foot.inc"); ?>
