<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Self-contained autoloader for the plugin's bundled libraries.
 *
 * This replaces the bundled Composer vendor/autoload.php, whose bootstrap registers a
 * ClassLoader with prepend=true and so hijacks \Composer\InstalledVersions::getRootPackage().
 * See the note in classes/turnitin_comms.class.php and MDL-88682.
 *
 * @package   plagiarism_turnitin
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$vendordir = __DIR__ . '/vendor';

spl_autoload_register(function ($class) use ($vendordir) {
    // PSR-4: Integrations\PhpSdk\ => vendor/Integrations/phpsdk-package/src/.
    $prefix = 'Integrations\\PhpSdk\\';
    if (strncmp($class, $prefix, strlen($prefix)) === 0) {
        $relative = substr($class, strlen($prefix));
        $file = $vendordir . '/Integrations/phpsdk-package/src/' . str_replace('\\', '/', $relative) . '.php';
        if (is_file($file)) {
            require $file;
        }
        return;
    }

    // PSR-0: Httpful => vendor/nategood/httpful/src/.
    if ($class === 'Httpful' || strncmp($class, 'Httpful\\', 8) === 0) {
        $logicalpath = str_replace('\\', '/', $class);
        // PSR-0 converts underscores in the class-name segment to directory separators.
        $pos = strrpos($logicalpath, '/');
        if ($pos !== false) {
            $logicalpath = substr($logicalpath, 0, $pos + 1) . str_replace('_', '/', substr($logicalpath, $pos + 1));
        } else {
            $logicalpath = str_replace('_', '/', $logicalpath);
        }
        $file = $vendordir . '/nategood/httpful/src/' . $logicalpath . '.php';
        if (is_file($file)) {
            require $file;
        }
        return;
    }
}, true, false);
