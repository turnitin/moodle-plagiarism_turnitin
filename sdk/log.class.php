<?php
/* @ignore
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

require_once( __DIR__.'logger.php' );

/**
 * @ignore
 */
class TurnitinLogger extends Klogger {

    const LOGLEVEL = 6;
    const KEEPLOGS = 10;

    public function __construct( $filepath ) {
        if ( $filepath == null ) return false;
        $this->rotateLogs( $filepath );
        parent::setDateFormat( 'Y-m-d G:i:s O' );
        parent::__construct($filepath, self::LOGLEVEL);
    }

    private function rotateLogs( $filepath ) {
        if ( !file_exists( $filepath ) ) {
            mkdir( $filepath, 0777, true );
        }
        $dir=opendir( $filepath );
        $files=array();
        while ($entry=readdir( $dir )) {
            if ( substr( basename( $entry ) ,0 ,1 )!='.' AND substr_count(basename( $entry ),parent::PREFIX ) > 0 ) {
                $files[]=basename( $entry );
            }
        }
        sort( $files );
        for ($i=0; $i<count( $files ) - self::KEEPLOGS; $i++ ) {
            unlink( $filepath . '/' . $files[$i] );
        }
    }

}

//?>