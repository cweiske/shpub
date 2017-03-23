<?php
/**
 * Part of shpub
 *
 * PHP version 5
 *
 * @author  Christian Weiske <cweiske@cweiske.de>
 * @license http://www.gnu.org/licenses/agpl.html GNU AGPL v3
 * @link    http://cweiske.de/shpub.htm
 */
namespace shpub;

/**
 * Class autoloader, PSR-0 compliant.
 *
 * @author  Christian Weiske <cweiske@cweiske.de>
 * @license http://www.gnu.org/licenses/agpl.html GNU AGPL v3
 * @version Release: @package_version@
 * @link    http://cweiske.de/shpub.htm
 */
class Autoloader
{
    /**
     * Load the given class
     *
     * @param string $class Class name
     *
     * @return void
     */
    public function load($class)
    {
        $file = strtr($class, '_\\', '//') . '.php';
        if (stream_resolve_include_path($file)) {
            include $file;
        }
    }

    /**
     * Register this autoloader
     *
     * @return void
     */
    public static function register()
    {
        set_include_path(
            get_include_path() . PATH_SEPARATOR . __DIR__ . '/../'
        );
        spl_autoload_register(array(new self(), 'load'));
    }
}
?>