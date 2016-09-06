<?php
namespace shpub;

class Config
{
    public $hosts = [];

    /**
     * Currently selected host.
     *
     * @var Host
     */
    public $host;

    protected function getConfigFilePath()
    {
        if (!isset($_SERVER['HOME'])) {
            Log::err('Cannot determine home directory');
            return false;
        }

        return $_SERVER['HOME'] . '/.config/shpub.ini';
    }

    public function load()
    {
        $cfgFile = $this->getConfigFilePath();
        if ($cfgFile == false) {
            return false;
        }

        if (!file_exists($cfgFile) || !is_readable($cfgFile)) {
            return false;
        }

        $data = parse_ini_file($cfgFile, true);
        foreach ($data as $key => $val) {
            if (!is_array($val)) {
                continue;
            }
            $host = new Config_Host();
            foreach ($val as $hostProp => $hostVal) {
                if (!property_exists($host, $hostProp)) {
                    Log::err('Invalid config key "' . $hostProp . '"');
                    exit(1);
                }
                $host->$hostProp = $hostVal;
            }
            $this->hosts[$key] = $host;
        }
    }

    public function save()
    {
        $str = '';
        foreach ($this->hosts as $hostName => $host) {
            if ($str != '') {
                $str .= "\n";
            }
            $str .= '[' . $hostName . "]\n";
            foreach ($host as $hostProp => $hostVal) {
                if ($hostProp == 'endpoints') {
                    continue;
                }
                if ($hostVal == '') {
                    continue;
                }
                $str .= $hostProp . '=' . $hostVal . "\n";
            }
        }
        $cfgFilePath = $this->getConfigFilePath();
        $cfgDir = dirname($cfgFilePath);
        if (!is_dir($cfgDir)) {
            mkdir($cfgDir, 0700);
        }
        file_put_contents($cfgFilePath, $str);
        //contains sensitive data; nobody else may read that
        chmod($cfgFilePath, 0600);
    }

    public function getDefaultHost()
    {
        if (!count($this->hosts)) {
            return null;
        }
        foreach ($this->hosts as $key => $host) {
            if ($host->default) {
                return $host;
            }
        }
        
        reset($this->hosts);
        return key($this->hosts);
    }

    public function getHostByName($keyOrServer)
    {
        if (!count($this->hosts)) {
            return null;
        }
        foreach ($this->hosts as $key => $host) {
            if ($key == $keyOrServer || $host->server == $keyOrServer) {
                return $key;
            }
        }
        return null;
    }
}
?>
