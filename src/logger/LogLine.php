<?php
/*
 * This file is part of the Snapshot plugin.
 *
 * (c) Alejandro Gama Castro <alex@copperforest.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace copperforest\snapshot\logger;

/**
 * @author Alejandro Gama Castro <alex@copperforest.org>
 */
class LogLine
{
    protected $time;
    protected $userId;
    protected $text;
 
    function __construct( $userId, $text )
    {
        $this->time = time();
        $this->userId = $userId;
        $this->text = $text;
    }
    
    
    function getTime()
    {
        return $this->time;
    }
    
    function getUserId()
    {
        return $this->userId;
    }
    
    function getText()
    {
        return $this->text;
    }
    
    function __toString()
    {
        return gmdate( 'Y-m-d H:i:s', $this->time ) . '@' . $this->userId.  ':' . "\n" . $this->text . "\n";
    }
}
