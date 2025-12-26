<?php
/**
 |--------------------------------------------------------------------------|
 |   https://github.com/Bigjoos/                                            |
 |--------------------------------------------------------------------------|
 |   Licence Info: WTFPL                                                    |
 |--------------------------------------------------------------------------|
 |   Copyright (C) 2010 U-232 V5                                            |
 |--------------------------------------------------------------------------|
 |   A bittorrent tracker source based on TBDev.net/tbsource/bytemonsoon.   |
 |--------------------------------------------------------------------------|
 */
function xbt_install($root)
{
    // Attempt automatic XBT installation (Linux only). Show progress and fallbacks.
    $out = '<fieldset><legend>XBT Installation</legend>';
    if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
        $out .= '<div class="notreadable">Automatic XBT installation is not supported on Windows.</div>';
        $out .= '<div class="info">Please install XBT manually on Linux server.</div>';
        $out .= '</fieldset>';
        return $out;
    }
    // Detect distro
    $os_release = is_readable('/etc/os-release') ? file_get_contents('/etc/os-release') : '';
    $is_debian = (strpos($os_release, 'ID=debian') !== false) || (strpos($os_release, 'ID=ubuntu') !== false) || (strpos($os_release, 'ID=linuxmint') !== false);
    $is_centos = (strpos($os_release, 'ID="centos"') !== false) || (strpos($os_release, 'ID=centos') !== false) || (strpos($os_release, 'ID="rhel"') !== false) || (strpos($os_release, 'ID=rhel') !== false);

    $cmds = array();
    if ($is_debian) {
        $cmds[] = 'sudo apt update';
        $cmds[] = 'sudo apt install -y build-essential cmake git libssl-dev libmysqlclient-dev libboost-all-dev';
    } elseif ($is_centos) {
        $cmds[] = 'sudo yum groupinstall -y "Development Tools"';
        $cmds[] = 'sudo yum install -y cmake git openssl-devel mysql-devel boost-devel';
    } else {
        // Fallback generic
        $cmds[] = 'echo "Install build tools: cmake, git, OpenSSL dev, MySQL dev, Boost dev"';
    }
    $cmds[] = 'cd /tmp';
    $cmds[] = 'rm -rf xbt';
    $cmds[] = 'git clone https://github.com/OlafvdSpek/xbt.git';
    $cmds[] = 'cd xbt/Tracker';
    $cmds[] = 'cmake .';
    $cmds[] = 'make -j$(nproc)';
    $cmds[] = 'sudo cp xbt_tracker /usr/local/bin/xbt_tracker';

    $output = array();
    foreach ($cmds as $c) {
        $o = array();
        $ret = 0;
        @exec($c . ' 2>&1', $o, $ret);
        $output[] = ['$ ' . $c, implode("\n", $o), 'exit=' . $ret];
        if ($ret !== 0) {
            $out .= '<div class="notreadable">Command failed: '.htmlspecialchars($c, ENT_QUOTES).'</div>';
            break;
        }
    }

    // Try writing basic config file for XBT
    $conf_path = '/etc/xbt_tracker.conf';
    $installed_conf = false;
    if (is_writable('/etc') || (function_exists('posix_geteuid') && posix_geteuid() === 0)) {
        // Load app config
        require_once ($root.'include/config.php');
        $listen_ip = '0.0.0.0';
        $listen_port = 2710;
        $mysql_host = $INSTALLER09['mysql_host'];
        $mysql_user = $INSTALLER09['mysql_user'];
        $mysql_pass = $INSTALLER09['mysql_pass'];
        $mysql_db   = $INSTALLER09['mysql_db'];
        $conf = "listen_iptables=0\n".
                "listen\n".
                "{\n".
                "    ip = $listen_ip\n".
                "    port = $listen_port\n".
                "}\n".
                "mysql\n".
                "{\n".
                "    database = $mysql_db\n".
                "    host = $mysql_host\n".
                "    user = $mysql_user\n".
                "    password = $mysql_pass\n".
                "}\n";
        if (@file_put_contents($conf_path, $conf) !== false) {
            $installed_conf = true;
            $out .= '<div class="readable">Wrote XBT config to '.htmlspecialchars($conf_path, ENT_QUOTES).'</div>';
        }
    }

    // Try create systemd service
    $svc_path = '/etc/systemd/system/xbt_tracker.service';
    if ($installed_conf && (is_writable('/etc/systemd/system') || (function_exists('posix_geteuid') && posix_geteuid() === 0))) {
        $svc = "[Unit]\nDescription=XBT Tracker\nAfter=network.target\n\n[Service]\nExecStart=/usr/local/bin/xbt_tracker --conf /etc/xbt_tracker.conf\nRestart=always\n\n[Install]\nWantedBy=multi-user.target\n";
        if (@file_put_contents($svc_path, $svc) !== false) {
            @exec('sudo systemctl daemon-reload');
            @exec('sudo systemctl enable --now xbt_tracker');
            $out .= '<div class="readable">Installed and started xbt_tracker systemd service.</div>';
        }
    }

    // Show run log
    $out .= '<pre class="chmod-cmd">'.htmlspecialchars(implode("\n\n", array_map(function($e){ return implode("\n", $e); }, $output)), ENT_QUOTES).'</pre>';

    // Fallback instructions
    $out .= '<div class="info">If any step failed, run these commands manually as root:</div>';
    $out .= '<pre class="chmod-cmd">'.htmlspecialchars(implode("\n", $cmds), ENT_QUOTES).'</pre>';

    $out .= '</fieldset>';
    return $out;
}
